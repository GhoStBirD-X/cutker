<?php

namespace App\Services;

use App\Enums\StatusKaryawan;
use App\Enums\StatusKonfirmasiKontrak;
use App\Enums\TipeKaryawan;
use App\Models\Karyawan;
use App\Models\KompensasiCuti;
use App\Models\KonfirmasiKontrakCuti;
use App\Models\RiwayatSaldoCuti;
use App\Models\SaldoCuti;
use Illuminate\Support\Facades\DB;

class PeriodeCutiService
{
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
    public function konfirmasiPerpanjangan(KonfirmasiKontrakCuti $konfirmasi, Karyawan $olehSiapa, bool $diperpanjang, ?string $catatan): void
    {
        DB::transaction(function () use ($konfirmasi, $olehSiapa, $diperpanjang, $catatan) {
            $konfirmasi->update([
                'status' => $diperpanjang ? StatusKonfirmasiKontrak::Diperpanjang : StatusKonfirmasiKontrak::TidakDiperpanjang,
                'dikonfirmasi_oleh_id' => $olehSiapa->id,
                'dikonfirmasi_pada' => now(),
                'catatan' => $catatan,
            ]);

            $saldo = $konfirmasi->saldoCuti;

            if ($diperpanjang) {
                $riwayat = RiwayatSaldoCuti::query()
                    ->where('karyawan_id', $saldo->karyawan_id)
                    ->where('jenis_cuti_id', $saldo->jenis_cuti_id)
                    ->where('periode_ke', $saldo->periode_ke)
                    ->first();

                $this->lanjutkanPeriode($saldo, $riwayat);

                return;
            }

            if ($saldo->sisa > 0) {
                $this->buatKompensasi($saldo, $this->riwayatUntuk($saldo));
            }

            $konfirmasi->karyawan->update(['status' => StatusKaryawan::Nonaktif]);
        });
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
     */
    protected function lanjutkanPeriode(SaldoCuti $saldoLama, ?RiwayatSaldoCuti $riwayat): void
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

        SaldoCuti::query()->create([
            'karyawan_id' => $saldoLama->karyawan_id,
            'jenis_cuti_id' => $saldoLama->jenis_cuti_id,
            'tahun' => $periodeMulaiBaru->year,
            'periode_ke' => $saldoLama->periode_ke + 1,
            'periode_mulai' => $periodeMulaiBaru,
            'periode_selesai' => $periodeSelesaiBaru,
            'kuota' => $jenisCuti->kuota_default,
            'terpakai' => 0,
            'sisa' => $jenisCuti->kuota_default,
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
