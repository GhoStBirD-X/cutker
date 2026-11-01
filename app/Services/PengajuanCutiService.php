<?php

namespace App\Services;

use App\Enums\StatusApproval;
use App\Enums\StatusPengajuan;
use App\Exceptions\PengajuanDitampungBelumBisaDiajukanException;
use App\Exceptions\SaldoCutiTidakCukupException;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\SaldoCuti;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PengajuanCutiService
{
    public function __construct(
        protected SaldoCutiService $saldoCutiService,
        protected HariLiburService $hariLiburService,
        protected ApprovalService $approvalService,
    ) {}

    /**
     * Karyawan mengajukan cuti. Sisa saldo cuti dicek sebelum pengajuan dibuat,
     * lalu alur approval dimulai lewat ApprovalService::mulaiAlur() —
     * disesuaikan dengan wewenang approval yang sudah dimiliki si pengaju
     * sendiri (lihat docblock method itu untuk aturan HRD/Manager).
     * jumlah_hari yang memotong saldo sudah dikurangi akhir pekan (Sabtu &
     * Minggu) dan hari libur terdaftar yang bertabrakan dengan rentang
     * tanggal; jumlah_hari_kalender tetap menyimpan rentang kalender
     * aslinya untuk transparansi.
     *
     * Tanggal selesai yang melewati akhir periode saldo aktif tidak bisa
     * diajukan: pengajuan masuk penampungan (status Ditampung) tanpa alur
     * approval & tanpa cek saldo, lalu diajukan ulang oleh karyawan lewat
     * ajukanUlang() setelah periode saldonya diperbarui.
     *
     * @param  array{jenis_cuti_id: int, alasan_cuti_id?: int|null, tanggal_mulai: string, tanggal_selesai: string, alasan: string, mendadak?: bool, alasan_mendadak?: string|null}  $data
     */
    public function ajukan(Karyawan $karyawan, array $data, ?UploadedFile $lampiran = null): PengajuanCuti
    {
        $jenisCuti = JenisCuti::query()->findOrFail($data['jenis_cuti_id']);
        $saldo = $this->saldoCutiService->untukPeriodeAktif($karyawan, $jenisCuti);

        if ($saldo && $this->melewatiAkhirPeriode($saldo, Carbon::parse($data['tanggal_selesai']))) {
            return $this->buat($karyawan, $data, $lampiran, StatusPengajuan::Ditampung);
        }

        return $this->buat($karyawan, $data, $lampiran, StatusPengajuan::Pending, fn (PengajuanCuti $pengajuan) => $this->approvalService->mulaiAlur($pengajuan, $karyawan, $this->cariKepalaBagian($karyawan)));
    }

    /**
     * Ajukan ulang pengajuan dari penampungan ke alur approval biasa.
     * jumlah_hari dihitung ulang (hari libur bisa saja bertambah sejak
     * ditampung) dan saldo periode yang baru dicek seperti pengajuan biasa.
     *
     * @throws PengajuanDitampungBelumBisaDiajukanException
     * @throws SaldoCutiTidakCukupException
     */
    public function ajukanUlang(PengajuanCuti $pengajuan): void
    {
        $alasan = $this->alasanBelumBisaDiajukanUlang($pengajuan);

        if ($alasan !== null) {
            throw new PengajuanDitampungBelumBisaDiajukanException($alasan);
        }

        $karyawan = $pengajuan->karyawan;
        $saldo = $this->saldoCutiService->untukPeriodeAktif($karyawan, $pengajuan->jenisCuti);
        ['hari' => $jumlahHari] = $this->hitungJumlahHari($pengajuan->tanggal_mulai, $pengajuan->tanggal_selesai);

        if ($saldo->kuota !== null && $saldo->sisa < $jumlahHari) {
            throw new SaldoCutiTidakCukupException($saldo->sisa ?? 0, $jumlahHari);
        }

        DB::transaction(function () use ($pengajuan, $karyawan, $jumlahHari) {
            $pengajuan->update([
                'status' => StatusPengajuan::Pending,
                'jumlah_hari' => $jumlahHari,
                'tanggal_pengajuan' => now(),
            ]);

            $this->approvalService->mulaiAlur($pengajuan, $karyawan, $this->cariKepalaBagian($karyawan));
        });
    }

    /**
     * Alasan pengajuan di penampungan belum bisa diajukan ulang, atau null
     * jika sudah bisa. Dipakai juga halaman detail untuk menampilkan
     * keterangan di samping tombol "Ajukan Sekarang".
     */
    public function alasanBelumBisaDiajukanUlang(PengajuanCuti $pengajuan): ?string
    {
        if ($pengajuan->status !== StatusPengajuan::Ditampung) {
            return 'Pengajuan ini tidak berada di penampungan.';
        }

        if (! $pengajuan->tanggal_mulai->isAfter(today())) {
            return 'Tanggal mulai cuti sudah lewat, pengajuan ini tidak bisa diajukan lagi.';
        }

        $saldo = $this->saldoCutiService->untukPeriodeAktif($pengajuan->karyawan, $pengajuan->jenisCuti);

        if (! $saldo || $this->melewatiAkhirPeriode($saldo, $pengajuan->tanggal_selesai)) {
            return 'Periode saldo cuti belum diperbarui. Pengajuan bisa diajukan setelah periode saldo yang baru aktif.';
        }

        $bentrok = PengajuanCuti::query()
            ->where('karyawan_id', $pengajuan->karyawan_id)
            ->whereKeyNot($pengajuan->id)
            ->whereIn('status', [StatusPengajuan::Pending, StatusPengajuan::Disetujui])
            ->whereDate('tanggal_mulai', '<=', $pengajuan->tanggal_selesai)
            ->whereDate('tanggal_selesai', '>=', $pengajuan->tanggal_mulai)
            ->exists();

        return $bentrok ? 'Rentang tanggal bertabrakan dengan pengajuan cuti aktif lain milik Anda.' : null;
    }

    /**
     * Batalkan pengajuan di penampungan yang tanggal mulainya sudah lewat —
     * tidak mungkin lagi diajukan, mis. karena periode saldo tidak pernah
     * diperbarui (kontrak tidak diperpanjang). Dipanggil harian lewat
     * scheduler.
     */
    public function batalkanPenampunganKedaluwarsa(): int
    {
        return PengajuanCuti::query()
            ->where('status', StatusPengajuan::Ditampung)
            ->whereDate('tanggal_mulai', '<=', today())
            ->update(['status' => StatusPengajuan::Dibatalkan]);
    }

    public function melewatiAkhirPeriode(SaldoCuti $saldo, CarbonInterface $tanggalSelesai): bool
    {
        return $saldo->periode_selesai !== null && $tanggalSelesai->greaterThan($saldo->periode_selesai);
    }

    /**
     * HRD/Admin mencatat cuti atas nama karyawan (boleh backdate jauh).
     * Cutinya dianggap sudah disetujui: tanpa alur approval, saldo periode
     * aktif langsung dipotong, dan yang mencatat tercatat sebagai approver.
     *
     * @param  array{jenis_cuti_id: int, alasan_cuti_id?: int|null, tanggal_mulai: string, tanggal_selesai: string, alasan: string}  $data
     */
    public function catatOlehHrd(Karyawan $karyawan, array $data, Karyawan $dicatatOleh, ?UploadedFile $lampiran = null): PengajuanCuti
    {
        return $this->buat($karyawan, $data, $lampiran, StatusPengajuan::Pending, fn (PengajuanCuti $pengajuan) => $this->approvalService->setujuiLangsung($pengajuan, $dicatatOleh));
    }

    /**
     * Pengajuan berstatus Ditampung tidak dicek saldonya — saldo periode
     * yang baru belum ada, dicek nanti saat diajukan ulang.
     *
     * @param  array{jenis_cuti_id: int, alasan_cuti_id?: int|null, tanggal_mulai: string, tanggal_selesai: string, alasan: string, mendadak?: bool, alasan_mendadak?: string|null}  $data
     * @param  (callable(PengajuanCuti): void)|null  $lanjutkan
     */
    protected function buat(Karyawan $karyawan, array $data, ?UploadedFile $lampiran, StatusPengajuan $status, ?callable $lanjutkan = null): PengajuanCuti
    {
        $tanggalMulai = Carbon::parse($data['tanggal_mulai']);
        $tanggalSelesai = Carbon::parse($data['tanggal_selesai']);
        ['kalender' => $jumlahHariKalender, 'hari' => $jumlahHari] = $this->hitungJumlahHari($tanggalMulai, $tanggalSelesai);

        if ($status !== StatusPengajuan::Ditampung) {
            $jenisCuti = JenisCuti::query()->findOrFail($data['jenis_cuti_id']);
            $saldo = $this->saldoCutiService->untukPeriodeAktif($karyawan, $jenisCuti);

            if (! $saldo || ($saldo->kuota !== null && $saldo->sisa < $jumlahHari)) {
                throw new SaldoCutiTidakCukupException($saldo->sisa ?? 0, $jumlahHari);
            }
        }

        return DB::transaction(function () use ($karyawan, $data, $lampiran, $status, $lanjutkan, $tanggalMulai, $tanggalSelesai, $jumlahHari, $jumlahHariKalender) {
            $pengajuan = PengajuanCuti::query()->create([
                'karyawan_id' => $karyawan->id,
                'jenis_cuti_id' => $data['jenis_cuti_id'],
                'alasan_cuti_id' => $data['alasan_cuti_id'] ?? null,
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
                'jumlah_hari' => $jumlahHari,
                'jumlah_hari_kalender' => $jumlahHariKalender,
                'alasan' => $data['alasan'],
                'status' => $status,
                'tanggal_pengajuan' => now(),
                'lampiran' => $lampiran?->store('lampiran-cuti', 'public'),
                'is_mendadak' => $data['mendadak'] ?? false,
                'alasan_mendadak' => $data['alasan_mendadak'] ?? null,
            ]);

            if ($lanjutkan) {
                $lanjutkan($pengajuan);
            }

            return $pengajuan;
        });
    }

    /**
     * @return array{kalender: int, hari: int}
     */
    protected function hitungJumlahHari(CarbonInterface $tanggalMulai, CarbonInterface $tanggalSelesai): array
    {
        $jumlahHariKalender = (int) $tanggalMulai->diffInDays($tanggalSelesai) + 1;
        $jumlahHariLibur = $this->hariLiburService->hitungHariLibur(Carbon::instance($tanggalMulai), Carbon::instance($tanggalSelesai));

        return ['kalender' => $jumlahHariKalender, 'hari' => max(0, $jumlahHariKalender - $jumlahHariLibur)];
    }

    /**
     * Karyawan membatalkan pengajuannya sendiri. Approval level yang masih
     * pending ikut ditandai dibatalkan supaya tidak lagi muncul di daftar
     * approval milik approver-nya dan tidak bisa diproses lebih lanjut,
     * lalu approver-nya dikabari lewat ApprovalService::kabariPembatalan().
     */
    public function batalkan(PengajuanCuti $pengajuanCuti): void
    {
        $approvalPending = $pengajuanCuti->approvals()
            ->where('status', StatusApproval::Pending)
            ->with('approver.user')
            ->get();

        DB::transaction(function () use ($pengajuanCuti) {
            $pengajuanCuti->update(['status' => StatusPengajuan::Dibatalkan]);

            $pengajuanCuti->approvals()
                ->where('status', StatusApproval::Pending)
                ->update(['status' => StatusApproval::Dibatalkan]);
        });

        $this->approvalService->kabariPembatalan($pengajuanCuti, $approvalPending);
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
