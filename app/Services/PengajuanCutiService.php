<?php

namespace App\Services;

use App\Enums\StatusApproval;
use App\Enums\StatusPengajuan;
use App\Exceptions\SaldoCutiTidakCukupException;
use App\Models\Approval;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\User;
use App\Notifications\PengajuanCutiDiajukan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PengajuanCutiService
{
    public function __construct(
        protected SaldoCutiService $saldoCutiService,
        protected HariLiburService $hariLiburService,
    ) {}

    /**
     * Karyawan mengajukan cuti. Sisa saldo cuti dicek sebelum pengajuan dibuat,
     * lalu record Approval level 1 (atasan langsung) otomatis dibuat.
     * jumlah_hari yang memotong saldo sudah dikurangi hari libur nasional
     * yang bertabrakan dengan rentang tanggal; jumlah_hari_kalender tetap
     * menyimpan rentang kalender aslinya untuk transparansi.
     *
     * @param  array{jenis_cuti_id: int, alasan_cuti_id?: int|null, tanggal_mulai: string, tanggal_selesai: string, alasan: string, mendadak?: bool, alasan_mendadak?: string|null}  $data
     */
    public function ajukan(Karyawan $karyawan, array $data, ?UploadedFile $lampiran = null): PengajuanCuti
    {
        $tanggalMulai = Carbon::parse($data['tanggal_mulai']);
        $tanggalSelesai = Carbon::parse($data['tanggal_selesai']);
        $jumlahHariKalender = (int) $tanggalMulai->diffInDays($tanggalSelesai) + 1;
        $jumlahHariLibur = $this->hariLiburService->countBetween($tanggalMulai, $tanggalSelesai);
        $jumlahHari = max(0, $jumlahHariKalender - $jumlahHariLibur);

        $jenisCuti = JenisCuti::query()->findOrFail($data['jenis_cuti_id']);
        $saldo = $this->saldoCutiService->untukPeriodeAktif($karyawan, $jenisCuti);

        if (! $saldo || ($saldo->kuota !== null && $saldo->sisa < $jumlahHari)) {
            throw new SaldoCutiTidakCukupException($saldo->sisa ?? 0, $jumlahHari);
        }

        return DB::transaction(function () use ($karyawan, $data, $lampiran, $tanggalMulai, $tanggalSelesai, $jumlahHari, $jumlahHariKalender) {
            $pengajuan = PengajuanCuti::query()->create([
                'karyawan_id' => $karyawan->id,
                'jenis_cuti_id' => $data['jenis_cuti_id'],
                'alasan_cuti_id' => $data['alasan_cuti_id'] ?? null,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
                'jumlah_hari' => $jumlahHari,
                'jumlah_hari_kalender' => $jumlahHariKalender,
                'alasan' => $data['alasan'],
                'status' => StatusPengajuan::Pending,
                'tanggal_pengajuan' => now(),
                'lampiran' => $lampiran?->store('lampiran-cuti', 'public'),
                'is_mendadak' => $data['mendadak'] ?? false,
                'alasan_mendadak' => $data['alasan_mendadak'] ?? null,
            ]);

            $kepalaBagian = $this->cariKepalaBagian($karyawan);

            $approval = Approval::query()->create([
                'pengajuan_cuti_id' => $pengajuan->id,
                'approver_id' => $kepalaBagian?->id,
                'level' => 1,
                'status' => StatusApproval::Pending,
            ]);

            if ($kepalaBagian?->user) {
                $kepalaBagian->user->notify(new PengajuanCutiDiajukan($pengajuan));
            }

            return $pengajuan;
        });
    }

    /**
     * Karyawan membatalkan pengajuannya sendiri. Approval level yang masih
     * pending ikut ditandai dibatalkan supaya tidak lagi muncul di daftar
     * approval milik approver-nya dan tidak bisa diproses lebih lanjut.
     */
    public function batalkan(PengajuanCuti $pengajuanCuti): void
    {
        DB::transaction(function () use ($pengajuanCuti) {
            $pengajuanCuti->update(['status' => StatusPengajuan::Dibatalkan]);

            $pengajuanCuti->approvals()
                ->where('status', StatusApproval::Pending)
                ->update(['status' => StatusApproval::Dibatalkan]);
        });
    }

    /**
     * Kepala Bagian ditentukan dari karyawan lain di departemen yang sama
     * yang memegang role "kepala_bagian".
     */
    public function cariKepalaBagian(Karyawan $karyawan): ?Karyawan
    {
        $kepalaBagianKaryawanIds = User::role('kepala_bagian')->pluck('karyawan_id');

        return Karyawan::query()
            ->where('departemen_id', $karyawan->departemen_id)
            ->where('id', '!=', $karyawan->id)
            ->whereIn('id', $kepalaBagianKaryawanIds)
            ->first();
    }
}
