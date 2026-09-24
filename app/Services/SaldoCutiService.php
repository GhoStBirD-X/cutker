<?php

namespace App\Services;

use App\Enums\StatusKaryawan;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\SaldoCuti;
use Illuminate\Support\Carbon;

class SaldoCutiService
{
    /**
     * Generate SaldoCuti baru untuk setiap karyawan aktif & setiap jenis
     * cuti bertipe kalender (masa_kerja_minimal_bulan null) pada tahun
     * tertentu, berdasarkan kuota_default jenis cuti. Jenis cuti bertipe
     * periode (Cuti Tahunan, Cuti Besar) tidak disentuh di sini — siklusnya
     * ditangani PeriodeCutiService berdasarkan tanggal_masuk tiap karyawan.
     * Karyawan yang sudah punya saldo di tahun tersebut dilewati (idempotent).
     */
    public function resetTahunan(int $tahun): int
    {
        $dibuat = 0;

        foreach (JenisCuti::query()->whereNull('masa_kerja_minimal_bulan')->get() as $jenisCuti) {
            $dibuat += $this->generateUntukJenisCuti($jenisCuti, $tahun);
        }

        return $dibuat;
    }

    /**
     * Generate SaldoCuti untuk satu jenis cuti kalender ke semua karyawan
     * aktif pada tahun tertentu. Dipakai saat jenis cuti baru dibuat lewat
     * Master Data di tengah tahun, supaya karyawan langsung punya saldo
     * tanpa menunggu reset tahunan berikutnya. Idempotent seperti
     * resetTahunan(). Baris tahun sebelumnya (bila ada) ditutup agar query
     * "saldo aktif" tetap konsisten.
     */
    public function generateUntukJenisCuti(JenisCuti $jenisCuti, int $tahun): int
    {
        $dibuat = 0;

        Karyawan::query()
            ->where('status', StatusKaryawan::Aktif)
            ->chunkById(100, function ($karyawans) use ($jenisCuti, $tahun, &$dibuat) {
                foreach ($karyawans as $karyawan) {
                    $sudahAda = SaldoCuti::query()
                        ->where('karyawan_id', $karyawan->id)
                        ->where('jenis_cuti_id', $jenisCuti->id)
                        ->where('tahun', $tahun)
                        ->exists();

                    if ($sudahAda) {
                        continue;
                    }

                    SaldoCuti::query()
                        ->where('karyawan_id', $karyawan->id)
                        ->where('jenis_cuti_id', $jenisCuti->id)
                        ->aktif()
                        ->update(['ditutup_pada' => now()]);

                    SaldoCuti::query()->create([
                        'karyawan_id' => $karyawan->id,
                        'jenis_cuti_id' => $jenisCuti->id,
                        'tahun' => $tahun,
                        'kuota' => $jenisCuti->kuota_default,
                        'terpakai' => 0,
                        'sisa' => $jenisCuti->kuota_default,
                    ]);

                    $dibuat++;
                }
            });

        return $dibuat;
    }

    /**
     * Generate periode ke-1 (berbasis tanggal_masuk masing-masing) untuk
     * satu jenis cuti bertipe periode ke semua karyawan aktif yang belum
     * punya baris aktif untuk jenis cuti tsb. Dipakai saat jenis cuti
     * bertipe periode baru dibuat lewat Master Data di tengah jalan.
     *
     * Periode ke-1 adalah masa kerja minimal yang harus dipenuhi dulu
     * (mis. 12 bulan pertama) sebelum karyawan berhak atas kuota cuti —
     * sesuai UU Ketenagakerjaan — jadi kuota/sisa-nya 0, bukan
     * kuota_default. Kuota penuh baru diberikan mulai periode ke-2 lewat
     * PeriodeCutiService::lanjutkanPeriode() saat periode ke-1 ditutup.
     */
    public function generatePeriodeAwalUntukJenisCuti(JenisCuti $jenisCuti): int
    {
        $dibuat = 0;

        Karyawan::query()
            ->where('status', StatusKaryawan::Aktif)
            ->chunkById(100, function ($karyawans) use ($jenisCuti, &$dibuat) {
                foreach ($karyawans as $karyawan) {
                    $mulai = $karyawan->tanggal_masuk->copy();
                    $selesai = $mulai->copy()->addMonths($jenisCuti->masa_kerja_minimal_bulan)->subDay();

                    $saldo = SaldoCuti::query()->firstOrCreate(
                        ['karyawan_id' => $karyawan->id, 'jenis_cuti_id' => $jenisCuti->id, 'periode_ke' => 1],
                        [
                            'tahun' => $mulai->year,
                            'periode_mulai' => $mulai,
                            'periode_selesai' => $selesai,
                            'kuota' => 0,
                            'terpakai' => 0,
                            'sisa' => 0,
                        ],
                    );

                    if ($saldo->wasRecentlyCreated) {
                        $dibuat++;
                    }
                }
            });

        return $dibuat;
    }

    /**
     * Bootstrap saldo cuti awal untuk karyawan yang baru dibuat. Jenis cuti
     * bertipe periode (masa_kerja_minimal_bulan terisi) langsung mendapat
     * periode ke-1 mengikuti tanggal_masuk; jenis cuti bertipe kalender
     * mendapat baris tahun berjalan seperti karyawan lama.
     *
     * Periode ke-1 adalah masa kerja minimal yang harus dipenuhi dulu
     * sebelum karyawan berhak cuti (sesuai UU Ketenagakerjaan), jadi
     * kuota/sisa-nya 0 sampai periode ini ditutup dan lanjut ke periode
     * ke-2 (lihat PeriodeCutiService::lanjutkanPeriode()).
     */
    public function bootstrapUntukKaryawanBaru(Karyawan $karyawan): void
    {
        foreach (JenisCuti::all() as $jenisCuti) {
            if ($jenisCuti->masa_kerja_minimal_bulan !== null) {
                $mulai = $karyawan->tanggal_masuk->copy();
                $selesai = $mulai->copy()->addMonths($jenisCuti->masa_kerja_minimal_bulan)->subDay();

                SaldoCuti::query()->firstOrCreate(
                    ['karyawan_id' => $karyawan->id, 'jenis_cuti_id' => $jenisCuti->id, 'periode_ke' => 1],
                    [
                        'tahun' => $mulai->year,
                        'periode_mulai' => $mulai,
                        'periode_selesai' => $selesai,
                        'kuota' => 0,
                        'terpakai' => 0,
                        'sisa' => 0,
                    ],
                );

                continue;
            }

            SaldoCuti::query()->firstOrCreate(
                ['karyawan_id' => $karyawan->id, 'jenis_cuti_id' => $jenisCuti->id, 'tahun' => (int) now()->year],
                ['kuota' => $jenisCuti->kuota_default, 'terpakai' => 0, 'sisa' => $jenisCuti->kuota_default],
            );
        }
    }

    /**
     * Baris SaldoCuti yang sedang berlaku untuk kombinasi karyawan+jenis
     * cuti tertentu, baik tipe kalender maupun tipe periode. Menggantikan
     * lookup lama berbasis `where('tahun', ...)` yang tidak berlaku lagi
     * untuk jenis cuti bertipe periode.
     */
    public function untukPeriodeAktif(Karyawan $karyawan, JenisCuti $jenisCuti): ?SaldoCuti
    {
        return SaldoCuti::query()
            ->where('karyawan_id', $karyawan->id)
            ->where('jenis_cuti_id', $jenisCuti->id)
            ->aktif()
            ->latest('id')
            ->first();
    }

    /**
     * HRD membuat baris saldo baru secara manual untuk kombinasi karyawan +
     * jenis cuti yang belum punya baris aktif — dipakai saat migrasi data
     * karyawan lama ke sistem ini, atau kasus lain di luar alur otomatis.
     *
     * Untuk jenis cuti bertipe periode, periode_mulai/periode_selesai
     * SELALU dihitung dari tanggal_masuk karyawan (lihat hitungTanggalPeriode())
     * dan tidak pernah dipercaya dari input pengguna — supaya tanggal
     * periode tidak bisa salah ketik.
     *
     * @param  array{karyawan_id: int, jenis_cuti_id: int, tahun: int|null, periode_ke: int|null, kuota: int|null, terpakai: int, sisa: int|null, catatan: string}  $data
     */
    public function buatManual(array $data, Karyawan $olehSiapa): SaldoCuti
    {
        $karyawan = Karyawan::query()->findOrFail($data['karyawan_id']);
        $jenisCuti = JenisCuti::query()->findOrFail($data['jenis_cuti_id']);

        $periodeMulai = null;
        $periodeSelesai = null;

        if ($jenisCuti->masa_kerja_minimal_bulan !== null) {
            [$periodeMulai, $periodeSelesai] = $this->hitungTanggalPeriode($karyawan, $jenisCuti, $data['periode_ke']);
        }

        return SaldoCuti::query()->create([
            'karyawan_id' => $data['karyawan_id'],
            'jenis_cuti_id' => $data['jenis_cuti_id'],
            'tahun' => $data['tahun'] ?? $periodeMulai->year,
            'periode_ke' => $data['periode_ke'],
            'periode_mulai' => $periodeMulai,
            'periode_selesai' => $periodeSelesai,
            'kuota' => $data['kuota'],
            'terpakai' => $data['terpakai'],
            'sisa' => $data['sisa'],
            'catatan' => $data['catatan'],
            'diubah_oleh_id' => $olehSiapa->id,
            'diubah_pada' => now(),
        ]);
    }

    /**
     * HRD mengoreksi kuota/terpakai/sisa pada baris saldo yang sudah ada.
     * Tanggal periode tidak bisa dikoreksi di sini secara sengaja — kalau
     * periodenya sendiri salah, baris ini dihapus lalu dibuat ulang lewat
     * buatManual() supaya tanggalnya tetap konsisten dengan tanggal_masuk.
     * Alasan koreksi wajib diisi setiap kali sebagai jejak audit minimal.
     *
     * @param  array{kuota: int|null, terpakai: int, sisa: int|null, catatan: string}  $data
     */
    public function sesuaikanManual(SaldoCuti $saldo, array $data, Karyawan $olehSiapa): SaldoCuti
    {
        $saldo->update([
            'kuota' => $data['kuota'],
            'terpakai' => $data['terpakai'],
            'sisa' => $data['sisa'],
            'catatan' => $data['catatan'],
            'diubah_oleh_id' => $olehSiapa->id,
            'diubah_pada' => now(),
        ]);

        return $saldo;
    }

    /**
     * Daftar periode_ke yang masih tersedia (belum tercatat di saldo_cutis,
     * baik yang aktif maupun yang sudah ditutup) untuk kombinasi karyawan +
     * jenis cuti bertipe periode tertentu, lengkap dengan tanggal mulai/
     * selesai yang dihitung otomatis dari tanggal_masuk. Dipakai untuk
     * mengisi dropdown "Periode Ke-" di form tambah saldo manual supaya
     * HRD tidak bisa memilih nomor periode yang sudah ada atau salah ketik
     * tanggalnya.
     *
     * @return list<array{periode_ke: int, periode_mulai: string, periode_selesai: string}>
     */
    public function periodeTersediaUntuk(Karyawan $karyawan, JenisCuti $jenisCuti): array
    {
        if ($jenisCuti->masa_kerja_minimal_bulan === null) {
            return [];
        }

        $sudahAda = SaldoCuti::query()
            ->where('karyawan_id', $karyawan->id)
            ->where('jenis_cuti_id', $jenisCuti->id)
            ->pluck('periode_ke')
            ->all();

        $periodeTertinggi = $sudahAda === [] ? 1 : max($sudahAda) + 1;

        $tersedia = [];

        for ($periodeKe = 1; $periodeKe <= $periodeTertinggi; $periodeKe++) {
            if (in_array($periodeKe, $sudahAda, true)) {
                continue;
            }

            [$mulai, $selesai] = $this->hitungTanggalPeriode($karyawan, $jenisCuti, $periodeKe);

            $tersedia[] = [
                'periode_ke' => $periodeKe,
                'periode_mulai' => $mulai->toDateString(),
                'periode_selesai' => $selesai->toDateString(),
            ];
        }

        return $tersedia;
    }

    /**
     * Hitung tanggal mulai & selesai periode ke-N berdasarkan tanggal_masuk
     * karyawan dan masa kerja minimal (dalam bulan) jenis cuti — rumus yang
     * sama dengan yang dipakai PeriodeCutiService::lanjutkanPeriode() saat
     * merangkai periode secara berantai, supaya tanggal periode manapun
     * (baik dibuat otomatis maupun manual) selalu konsisten satu sama lain.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function hitungTanggalPeriode(Karyawan $karyawan, JenisCuti $jenisCuti, int $periodeKe): array
    {
        $bulan = $jenisCuti->masa_kerja_minimal_bulan;

        $mulai = $karyawan->tanggal_masuk->copy()->addMonths(($periodeKe - 1) * $bulan);
        $selesai = $karyawan->tanggal_masuk->copy()->addMonths($periodeKe * $bulan)->subDay();

        return [$mulai, $selesai];
    }
}
