<?php

namespace App\Services;

use App\Enums\StatusKaryawan;
use App\Enums\StatusKonfirmasiKontrak;
use App\Enums\TipeKaryawan;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\KompensasiCuti;
use App\Models\KonfirmasiKontrakCuti;
use App\Models\RiwayatSaldoCuti;
use App\Models\SaldoCuti;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PeriodeCutiService
{
    public function __construct(
        protected SaldoCutiService $saldoCutiService,
    ) {}

    /**
     * Proses semua baris SaldoCuti bertipe periode yang periodenya sudah
     * lewat: arsipkan ke riwayat, lalu lanjutkan otomatis (karyawan tetap)
     * atau tahan menunggu konfirmasi perpanjangan kontrak (karyawan
     * kontrak). Dipanggil harian lewat scheduler.
     *
     * Diulang sampai tidak ada lagi periode yang lewat, supaya karyawan
     * dengan tanggal_masuk lama (mis. migrasi data) atau cron yang sempat
     * berhenti tetap bisa mengejar ke periode yang benar dalam satu kali
     * jalan, bukan satu periode per hari.
     */
    public function prosesSemuaKaryawan(): int
    {
        $diproses = 0;

        do {
            $diprosesPutaranIni = 0;

            SaldoCuti::query()
                ->with(['karyawan', 'jenisCuti'])
                ->aktif()
                ->whereNotNull('periode_ke')
                ->where('periode_selesai', '<', now()->toDateString())
                ->chunkById(100, function ($saldos) use (&$diprosesPutaranIni) {
                    foreach ($saldos as $saldo) {
                        $this->tutupPeriode($saldo);
                        $diprosesPutaranIni++;
                    }
                });

            $diproses += $diprosesPutaranIni;
        } while ($diprosesPutaranIni > 0);

        return $diproses;
    }

    /**
     * Tutup satu periode: arsipkan ke RiwayatSaldoCuti, lalu langsung
     * lanjutkan ke periode berikutnya (karyawan tetap) atau buat
     * KonfirmasiKontrakCuti menunggu (karyawan kontrak).
     */
    public function tutupPeriode(SaldoCuti $saldo): void
    {
        DB::transaction(function () use ($saldo) {
            $riwayat = $this->arsipkan($saldo);

            if ($saldo->karyawan->tipe_karyawan === TipeKaryawan::Tetap) {
                $this->lanjutkanPeriode($saldo, $riwayat);

                return;
            }

            $saldo->update(['ditutup_pada' => now()]);

            KonfirmasiKontrakCuti::query()->create([
                'karyawan_id' => $saldo->karyawan_id,
                'saldo_cuti_id' => $saldo->id,
                'periode_ke' => $saldo->periode_ke,
                'tanggal_batas' => $saldo->periode_selesai,
                'status' => StatusKonfirmasiKontrak::Menunggu,
            ]);
        });
    }

    /**
     * HRD mengonfirmasi status perpanjangan kontrak karyawan yang
     * periodenya sedang tertahan menunggu.
     */
    public function konfirmasiPerpanjangan(KonfirmasiKontrakCuti $konfirmasi, Karyawan $olehSiapa, bool $diperpanjang, ?string $catatan, ?string $tanggalAkhirKontrakBaru = null): void
    {
        DB::transaction(function () use ($konfirmasi, $olehSiapa, $diperpanjang, $catatan, $tanggalAkhirKontrakBaru) {
            $konfirmasi->update([
                'status' => $diperpanjang ? StatusKonfirmasiKontrak::Diperpanjang : StatusKonfirmasiKontrak::TidakDiperpanjang,
                'dikonfirmasi_oleh_id' => $olehSiapa->id,
                'dikonfirmasi_pada' => now(),
                'catatan' => $catatan,
            ]);

            $saldo = $konfirmasi->saldoCuti;

            if ($konfirmasi->tinjauan_siklus) {
                $this->terapkanTinjauanSiklus($konfirmasi, $saldo, $olehSiapa, $diperpanjang, $catatan, $tanggalAkhirKontrakBaru);

                return;
            }

            if ($diperpanjang) {
                $riwayat = RiwayatSaldoCuti::query()
                    ->where('karyawan_id', $saldo->karyawan_id)
                    ->where('jenis_cuti_id', $saldo->jenis_cuti_id)
                    ->where('periode_ke', $saldo->periode_ke)
                    ->first();

                $this->lanjutkanPeriode($saldo, $riwayat);

                if ($tanggalAkhirKontrakBaru !== null) {
                    $konfirmasi->karyawan->update(['tanggal_akhir_kontrak' => $tanggalAkhirKontrakBaru]);
                }

                return;
            }

            if ($saldo->sisa > 0) {
                $this->buatKompensasi($saldo, $this->riwayatUntuk($saldo));
            }

            $konfirmasi->karyawan->update(['status' => StatusKaryawan::Nonaktif]);
        });
    }

    /**
     * Akhir K5: karyawan diangkat menjadi karyawan tetap. Cuti Besar
     * langsung didapat penuh sejak tanggal pengangkatan (hari setelah
     * tanggal_batas), sedangkan Cuti Tahunan tahun pertama setelah
     * pengangkatan tidak didapat (kuota 0) — tahun berikutnya normal lagi.
     */
    public function angkatKaryawanTetap(KonfirmasiKontrakCuti $konfirmasi, Karyawan $olehSiapa, ?string $catatan): void
    {
        DB::transaction(function () use ($konfirmasi, $olehSiapa, $catatan) {
            $konfirmasi->update([
                'status' => StatusKonfirmasiKontrak::DiangkatTetap,
                'dikonfirmasi_oleh_id' => $olehSiapa->id,
                'dikonfirmasi_pada' => now(),
                'catatan' => $catatan,
            ]);

            $karyawan = $konfirmasi->karyawan;
            $karyawan->update([
                'tipe_karyawan' => TipeKaryawan::Tetap,
                'tanggal_akhir_kontrak' => null,
            ]);

            $saldo = $konfirmasi->saldoCuti;
            $saldo->setRelation('karyawan', $karyawan);

            // Tinjauan siklus menunjuk saldo yang masih berjalan — tidak perlu
            // membuka periode baru, cukup dikoreksi jadi 0 bila saldo itu
            // masih tahun pertama setelah akhir K5.
            if ($konfirmasi->tinjauan_siklus) {
                if ($saldo->periode_ke === $konfirmasi->periode_ke + 1 && $saldo->kuota !== null) {
                    $this->saldoCutiService->sesuaikanManual($saldo, [
                        'kuota' => $saldo->terpakai,
                        'terpakai' => $saldo->terpakai,
                        'sisa' => 0,
                        'catatan' => 'Tinjauan siklus kontrak: diangkat tetap setelah K5, Cuti Tahunan tahun pertama tidak didapat.',
                    ], $olehSiapa);
                }
            } else {
                $this->lanjutkanPeriode($saldo, $this->riwayatUntuk($saldo), tanpaKuota: true);
            }

            $this->saldoCutiService->mulaiSaldoKhususKaryawanTetap($karyawan, $konfirmasi->tanggal_batas->copy()->addDay(), langsungPenuh: true);
        });
    }

    /**
     * Karyawan kontrak yang telanjur melewati K5 sebelum aturan siklus
     * K1–K5 berlaku (saldo Cuti Tahunan aktif di periode ke-6 dst.) dan
     * belum punya konfirmasi menunggu. Mereka dulu diperpanjang otomatis
     * tanpa keputusan "angkat tetap atau kontrak ulang ke K1". Saldo yang
     * sudah pernah ditinjau tidak diambil lagi (aman dijalankan ulang).
     *
     * @return Collection<int, SaldoCuti>
     */
    public function kandidatTinjauanSiklus(): Collection
    {
        return SaldoCuti::query()
            ->with(['karyawan', 'jenisCuti'])
            ->aktif()
            ->where('periode_ke', '>', SaldoCuti::PANJANG_SIKLUS_KONTRAK)
            ->whereHas('jenisCuti', fn ($query) => $query->where('nama_jenis', JenisCuti::NAMA_CUTI_TAHUNAN))
            ->whereHas('karyawan', fn ($query) => $query
                ->where('tipe_karyawan', TipeKaryawan::Kontrak)
                ->where('status', StatusKaryawan::Aktif))
            ->whereDoesntHave('karyawan.konfirmasiKontrakCutis', fn ($query) => $query->where('status', StatusKonfirmasiKontrak::Menunggu))
            ->whereNotIn('id', KonfirmasiKontrakCuti::query()->where('tinjauan_siklus', true)->select('saldo_cuti_id'))
            ->orderBy('karyawan_id')
            ->get();
    }

    /**
     * Buat konfirmasi susulan "akhir K5" untuk satu kandidat tinjauan, supaya
     * HRD memutuskan lewat halaman Konfirmasi Kontrak seperti biasa.
     * periode_ke & tanggal_batas menunjuk K5 terakhir yang sudah dilewati
     * (mis. saldo periode ke-7 → periode ke-5), sehingga pilihan K5 muncul.
     */
    public function buatTinjauanSiklus(SaldoCuti $saldo): KonfirmasiKontrakCuti
    {
        $periodeAkhirK5 = $saldo->periode_ke - $saldo->urutanKontrak();

        $tanggalAkhirK5 = $this->tanggalAkhirPeriode($saldo, $periodeAkhirK5)
            ?? $saldo->periode_mulai?->copy()->subDay()
            ?? today();

        return KonfirmasiKontrakCuti::query()->create([
            'karyawan_id' => $saldo->karyawan_id,
            'saldo_cuti_id' => $saldo->id,
            'periode_ke' => $periodeAkhirK5,
            'tanggal_batas' => $tanggalAkhirK5,
            'status' => StatusKonfirmasiKontrak::Menunggu,
            'tinjauan_siklus' => true,
        ]);
    }

    protected function tanggalAkhirPeriode(SaldoCuti $saldo, int $periodeKe): ?CarbonInterface
    {
        $riwayat = RiwayatSaldoCuti::query()
            ->where('karyawan_id', $saldo->karyawan_id)
            ->where('jenis_cuti_id', $saldo->jenis_cuti_id)
            ->where('periode_ke', $periodeKe)
            ->first();

        if ($riwayat?->periode_selesai) {
            return $riwayat->periode_selesai;
        }

        return SaldoCuti::query()
            ->where('karyawan_id', $saldo->karyawan_id)
            ->where('jenis_cuti_id', $saldo->jenis_cuti_id)
            ->where('periode_ke', $periodeKe)
            ->value('periode_selesai');
    }

    /**
     * Keputusan atas konfirmasi tinjauan siklus. Saldo yang ditunjuk masih
     * berjalan, jadi tidak ada periode baru yang dibuka:
     * - kontrak ulang ke K1: bila saldo berjalan adalah K1, sisa kuotanya
     *   dikoreksi jadi 0 (hari yang sudah terpakai tidak dijadikan utang);
     * - tidak diperpanjang: saldo berjalan diarsipkan & ditutup, sisanya
     *   jadi kompensasi, dan karyawan dinonaktifkan.
     */
    protected function terapkanTinjauanSiklus(KonfirmasiKontrakCuti $konfirmasi, SaldoCuti $saldo, Karyawan $olehSiapa, bool $diperpanjang, ?string $catatan, ?string $tanggalAkhirKontrakBaru): void
    {
        if ($diperpanjang) {
            if ($saldo->urutanKontrak() === 1 && $saldo->kuota !== null) {
                $this->saldoCutiService->sesuaikanManual($saldo, [
                    'kuota' => $saldo->terpakai,
                    'terpakai' => $saldo->terpakai,
                    'sisa' => 0,
                    'catatan' => 'Tinjauan siklus kontrak: kontrak ulang ke K1 setelah K5, saldo dimulai dari 0. Alasan: '.$catatan,
                ], $olehSiapa);
            }

            if ($tanggalAkhirKontrakBaru !== null) {
                $konfirmasi->karyawan->update(['tanggal_akhir_kontrak' => $tanggalAkhirKontrakBaru]);
            }

            return;
        }

        $riwayat = $this->arsipkan($saldo);
        $saldo->update(['ditutup_pada' => now()]);

        if ($saldo->sisa > 0) {
            $this->buatKompensasi($saldo, $riwayat);
        }

        $konfirmasi->karyawan->update(['status' => StatusKaryawan::Nonaktif]);
    }

    /**
     * Perpanjang kontrak banyak karyawan sekaligus, masing-masing tepat 1
     * tahun dari tanggal_batas konfirmasinya sendiri (sama dengan tombol
     * "1 Tahun" di form per orang). Konfirmasi yang sudah diproses dilewati,
     * begitu juga konfirmasi di akhir K5 yang wajib diputuskan satu per satu
     * (angkat tetap, atau kontrak ulang ke K1 dengan alasan).
     * Satu transaksi supaya tidak ada yang setengah jadi bila satu gagal.
     *
     * @param  Collection<int, KonfirmasiKontrakCuti>  $konfirmasis
     */
    public function perpanjangSatuTahunMassal(Collection $konfirmasis, Karyawan $olehSiapa, ?string $catatan): int
    {
        $menunggu = $konfirmasis->filter(
            fn (KonfirmasiKontrakCuti $k): bool => $k->status === StatusKonfirmasiKontrak::Menunggu && ! $k->diAkhirSiklusKontrak(),
        );

        DB::transaction(function () use ($menunggu, $olehSiapa, $catatan): void {
            foreach ($menunggu as $konfirmasi) {
                $this->konfirmasiPerpanjangan(
                    $konfirmasi,
                    $olehSiapa,
                    true,
                    $catatan,
                    $konfirmasi->tanggal_batas->copy()->addYearNoOverflow()->toDateString(),
                );
            }
        });

        return $menunggu->count();
    }

    protected function arsipkan(SaldoCuti $saldo): RiwayatSaldoCuti
    {
        return RiwayatSaldoCuti::query()->firstOrCreate(
            [
                'karyawan_id' => $saldo->karyawan_id,
                'jenis_cuti_id' => $saldo->jenis_cuti_id,
                'periode_ke' => $saldo->periode_ke,
            ],
            [
                'periode_mulai' => $saldo->periode_mulai,
                'periode_selesai' => $saldo->periode_selesai,
                'kuota' => $saldo->kuota,
                'terpakai' => $saldo->terpakai,
                'sisa' => $saldo->sisa,
            ],
        );
    }

    protected function riwayatUntuk(SaldoCuti $saldo): ?RiwayatSaldoCuti
    {
        return RiwayatSaldoCuti::query()
            ->where('karyawan_id', $saldo->karyawan_id)
            ->where('jenis_cuti_id', $saldo->jenis_cuti_id)
            ->where('periode_ke', $saldo->periode_ke)
            ->first();
    }

    /**
     * Tutup baris lama (bila belum), buat periode berikutnya, dan
     * konversi sisa cuti jadi record kompensasi bila masih ada sisa.
     *
     * Karyawan kontrak berjalan dalam siklus K1–K5: periode yang jatuh
     * kembali ke K1 (setelah K5 diperpanjang) diperlakukan seperti masa
     * kerja minimal karyawan baru, jadi kuota/sisa-nya 0. $tanpaKuota
     * memaksa periode baru berkuota 0 (Cuti Tahunan tahun pertama setelah
     * diangkat tetap di akhir K5).
     */
    protected function lanjutkanPeriode(SaldoCuti $saldoLama, ?RiwayatSaldoCuti $riwayat, bool $tanpaKuota = false): void
    {
        if ($saldoLama->ditutup_pada === null) {
            $saldoLama->update(['ditutup_pada' => now()]);
        }

        if ($saldoLama->sisa > 0) {
            $this->buatKompensasi($saldoLama, $riwayat);
        }

        $jenisCuti = $saldoLama->jenisCuti;
        $periodeMulaiBaru = $saldoLama->periode_selesai->copy()->addDay();
        $periodeSelesaiBaru = $periodeMulaiBaru->copy()->addMonths($jenisCuti->masa_kerja_minimal_bulan)->subDay();
        $periodeKeBaru = $saldoLama->periode_ke + 1;
        $kembaliKeK1 = $saldoLama->karyawan->tipe_karyawan === TipeKaryawan::Kontrak
            && SaldoCuti::urutanKontrakDariPeriode($periodeKeBaru) === 1;
        $kuotaBaru = $tanpaKuota || $kembaliKeK1 ? 0 : $jenisCuti->kuota_default;

        SaldoCuti::query()->create([
            'karyawan_id' => $saldoLama->karyawan_id,
            'jenis_cuti_id' => $saldoLama->jenis_cuti_id,
            'tahun' => $periodeMulaiBaru->year,
            'periode_ke' => $periodeKeBaru,
            'periode_mulai' => $periodeMulaiBaru,
            'periode_selesai' => $periodeSelesaiBaru,
            'kuota' => $kuotaBaru,
            'terpakai' => 0,
            'sisa' => $kuotaBaru,
        ]);
    }

    protected function buatKompensasi(SaldoCuti $saldo, ?RiwayatSaldoCuti $riwayat): void
    {
        KompensasiCuti::query()->create([
            'karyawan_id' => $saldo->karyawan_id,
            'jenis_cuti_id' => $saldo->jenis_cuti_id,
            'riwayat_saldo_cuti_id' => $riwayat?->id,
            'jumlah_hari' => $saldo->sisa,
        ]);
    }
}
