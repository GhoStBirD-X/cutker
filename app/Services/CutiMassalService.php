<?php

namespace App\Services;

use App\Enums\StatusApproval;
use App\Enums\StatusCutiMassal;
use App\Enums\StatusKaryawan;
use App\Enums\StatusPengajuan;
use App\Exceptions\CutiMassalSudahDibatalkanException;
use App\Models\Approval;
use App\Models\CutiMassal;
use App\Models\JadwalShift;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\SaldoCuti;
use App\Notifications\PengajuanCutiDisetujui;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class CutiMassalService
{
    public function __construct(
        protected SaldoCutiService $saldoCutiService,
        protected HariLiburService $hariLiburService,
    ) {}

    /**
     * Karyawan aktif yang eligible untuk jenis cuti tertentu. Karyawan baru
     * (masa kerja belum cukup) sengaja tetap dimasukkan — itu justru yang
     * boleh saldonya minus, bukan dikecualikan. Hanya `khusus_gender` yang
     * memfilter daftar ini.
     *
     * @return Collection<int, Karyawan>
     */
    public function eligibleKaryawan(JenisCuti $jenisCuti): Collection
    {
        return Karyawan::query()
            ->where('status', StatusKaryawan::Aktif)
            ->when($jenisCuti->khusus_gender, fn ($query) => $query->where('jenis_kelamin', $jenisCuti->khusus_gender))
            ->with('departemen')
            ->orderBy('nama')
            ->get();
    }

    /**
     * Daftar karyawan eligible dilengkapi info saldo aktif & penanda apakah
     * saldonya akan minus jika cuti massal ini jadi diajukan, untuk preview
     * sebelum HRD submit.
     *
     * @return SupportCollection<int, array{karyawan: Karyawan, saldo: ?SaldoCuti, akan_minus: bool}>
     */
    public function previewKaryawan(JenisCuti $jenisCuti, Carbon $mulai, Carbon $selesai): SupportCollection
    {
        $jumlahHari = $this->hitungJumlahHari($mulai, $selesai)['hari'];

        $preview = [];

        foreach ($this->eligibleKaryawan($jenisCuti) as $karyawan) {
            $saldo = $this->saldoCutiService->untukPeriodeAktif($karyawan, $jenisCuti);

            $preview[] = [
                'karyawan' => $karyawan,
                'saldo' => $saldo,
                'akan_minus' => $saldo !== null && $saldo->kuota !== null && ($saldo->sisa - $jumlahHari) < 0,
            ];
        }

        return SupportCollection::make($preview);
    }

    /**
     * @return array{kalender: int, hari: int}
     */
    protected function hitungJumlahHari(Carbon $mulai, Carbon $selesai): array
    {
        $jumlahHariKalender = (int) $mulai->diffInDays($selesai) + 1;
        $jumlahHariLibur = $this->hariLiburService->countBetween($mulai, $selesai);

        return [
            'kalender' => $jumlahHariKalender,
            'hari' => max(0, $jumlahHariKalender - $jumlahHariLibur),
        ];
    }

    /**
     * Buat satu batch cuti massal. Tiap karyawan target mendapat satu
     * PengajuanCuti + 3 Approval yang langsung berstatus Disetujui (bukan
     * lewat alur approval berjenjang biasa), dan saldo cutinya dipotong
     * seketika — sengaja tanpa guard kecukupan saldo seperti
     * PengajuanCutiService::ajukan(), sehingga karyawan baru yang saldonya
     * belum cukup tetap diproses dan boleh menjadi minus. Karyawan yang
     * konflik (gender, overlap pengajuan lain, bentrok jadwal shift, atau
     * tidak punya baris saldo aktif) dilewati dengan alasan tercatat,
     * bukan menggagalkan seluruh batch.
     *
     * @param  array{jenis_cuti_id: int, tanggal_mulai: string, tanggal_selesai: string, alasan: string, karyawan_ids: int[]}  $data
     */
    public function buat(array $data, Karyawan $olehSiapa): CutiMassal
    {
        $jenisCuti = JenisCuti::query()->findOrFail($data['jenis_cuti_id']);
        $mulai = Carbon::parse($data['tanggal_mulai']);
        $selesai = Carbon::parse($data['tanggal_selesai']);
        ['kalender' => $jumlahHariKalender, 'hari' => $jumlahHari] = $this->hitungJumlahHari($mulai, $selesai);

        return DB::transaction(function () use ($data, $jenisCuti, $mulai, $selesai, $jumlahHari, $jumlahHariKalender, $olehSiapa) {
            $cutiMassal = CutiMassal::query()->create([
                'jenis_cuti_id' => $jenisCuti->id,
                'tanggal_mulai' => $mulai,
                'tanggal_selesai' => $selesai,
                'jumlah_hari' => $jumlahHari,
                'jumlah_hari_kalender' => $jumlahHariKalender,
                'alasan' => $data['alasan'],
                'dibuat_oleh_id' => $olehSiapa->id,
                'jumlah_karyawan' => 0,
                'dilewati' => [],
                'status' => StatusCutiMassal::Aktif,
            ]);

            $karyawans = Karyawan::query()->whereIn('id', $data['karyawan_ids'])->get()->keyBy('id');
            $diproses = 0;
            $dilewati = [];

            foreach ($data['karyawan_ids'] as $karyawanId) {
                $karyawan = $karyawans->get($karyawanId);

                if (! $karyawan) {
                    continue;
                }

                $alasanLewat = $this->alasanTidakEligible($karyawan, $jenisCuti, $mulai, $selesai);

                if ($alasanLewat !== null) {
                    $dilewati[] = ['karyawan_id' => $karyawan->id, 'nama' => $karyawan->nama, 'alasan' => $alasanLewat];

                    continue;
                }

                $saldo = SaldoCuti::query()
                    ->where('karyawan_id', $karyawan->id)
                    ->where('jenis_cuti_id', $jenisCuti->id)
                    ->aktif()
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                if (! $saldo) {
                    $dilewati[] = ['karyawan_id' => $karyawan->id, 'nama' => $karyawan->nama, 'alasan' => 'Tidak memiliki baris saldo cuti aktif untuk jenis cuti ini'];

                    continue;
                }

                $pengajuan = PengajuanCuti::query()->create([
                    'cuti_massal_id' => $cutiMassal->id,
                    'karyawan_id' => $karyawan->id,
                    'jenis_cuti_id' => $jenisCuti->id,
                    'tanggal_mulai' => $mulai,
                    'tanggal_selesai' => $selesai,
                    'jumlah_hari' => $jumlahHari,
                    'jumlah_hari_kalender' => $jumlahHariKalender,
                    'alasan' => $data['alasan'],
                    'status' => StatusPengajuan::Disetujui,
                    'tanggal_pengajuan' => now(),
                ]);

                foreach ([1, 2, 3] as $level) {
                    Approval::query()->create([
                        'pengajuan_cuti_id' => $pengajuan->id,
                        'approver_id' => $olehSiapa->id,
                        'level' => $level,
                        'status' => StatusApproval::Disetujui,
                        'tanggal_approval' => now(),
                        'catatan' => 'Disetujui otomatis melalui Cuti Massal',
                    ]);
                }

                $saldo->update([
                    'terpakai' => $saldo->terpakai + $jumlahHari,
                    'sisa' => $saldo->kuota === null ? null : $saldo->sisa - $jumlahHari,
                ]);

                $karyawan->user?->notify(new PengajuanCutiDisetujui($pengajuan));

                $diproses++;
            }

            $cutiMassal->update(['jumlah_karyawan' => $diproses, 'dilewati' => $dilewati]);

            return $cutiMassal;
        });
    }

    /**
     * Alasan karyawan dilewati dari cuti massal, atau null jika eligible.
     * Gender dicek ulang sebagai defense-in-depth (jangan percaya ID dari
     * client mentah-mentah); overlap & bentrok jadwal shift meniru query
     * TidakOverlapPengajuanCuti / TidakBentrokJadwalShift.
     */
    protected function alasanTidakEligible(Karyawan $karyawan, JenisCuti $jenisCuti, Carbon $mulai, Carbon $selesai): ?string
    {
        if ($jenisCuti->khusus_gender !== null && $karyawan->jenis_kelamin !== $jenisCuti->khusus_gender) {
            return 'Jenis cuti ini khusus untuk gender tertentu';
        }

        $overlap = PengajuanCuti::query()
            ->where('karyawan_id', $karyawan->id)
            ->whereIn('status', [StatusPengajuan::Pending, StatusPengajuan::Disetujui])
            ->whereDate('tanggal_mulai', '<=', $selesai)
            ->whereDate('tanggal_selesai', '>=', $mulai)
            ->exists();

        if ($overlap) {
            return 'Sudah memiliki pengajuan cuti aktif pada rentang tanggal ini';
        }

        $bentrokShift = JadwalShift::query()
            ->where('karyawan_id', $karyawan->id)
            ->whereBetween('tanggal', [$mulai->toDateString(), $selesai->toDateString()])
            ->exists();

        if ($bentrokShift) {
            return 'Bentrok dengan jadwal shift';
        }

        return null;
    }

    /**
     * Batalkan satu batch cuti massal: kembalikan saldo & tandai
     * Dibatalkan untuk setiap PengajuanCuti+Approval yang masih Disetujui.
     * Saldo dikembalikan ke baris yang sedang aktif saat ini (bukan
     * dilacak per baris asli) — jika saldo karyawan sudah pindah
     * periode/disesuaikan manual sejak batch dibuat, koreksi diterapkan ke
     * baris yang aktif sekarang, bukan baris asli.
     */
    public function batalkan(CutiMassal $cutiMassal, Karyawan $olehSiapa, ?string $catatan = null): void
    {
        if ($cutiMassal->status !== StatusCutiMassal::Aktif) {
            throw new CutiMassalSudahDibatalkanException;
        }

        DB::transaction(function () use ($cutiMassal, $olehSiapa, $catatan) {
            $pengajuanCutis = $cutiMassal->pengajuanCutis()
                ->where('status', StatusPengajuan::Disetujui)
                ->with('karyawan', 'jenisCuti')
                ->get();

            foreach ($pengajuanCutis as $pengajuan) {
                $saldo = SaldoCuti::query()
                    ->where('karyawan_id', $pengajuan->karyawan_id)
                    ->where('jenis_cuti_id', $pengajuan->jenis_cuti_id)
                    ->aktif()
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                if ($saldo) {
                    $saldo->update([
                        'terpakai' => max(0, $saldo->terpakai - $pengajuan->jumlah_hari),
                        'sisa' => $saldo->kuota === null ? null : $saldo->sisa + $pengajuan->jumlah_hari,
                    ]);
                }

                $pengajuan->update(['status' => StatusPengajuan::Dibatalkan]);
                $pengajuan->approvals()->update(['status' => StatusApproval::Dibatalkan]);
            }

            $cutiMassal->update([
                'status' => StatusCutiMassal::Dibatalkan,
                'dibatalkan_oleh_id' => $olehSiapa->id,
                'dibatalkan_pada' => now(),
                'catatan_pembatalan' => $catatan,
            ]);
        });
    }
}
