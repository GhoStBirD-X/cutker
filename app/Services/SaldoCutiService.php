<?php

namespace App\Services;

use App\Enums\AksiMassalSaldoCuti;
use App\Enums\StatusKaryawan;
use App\Enums\StatusKompensasiCuti;
use App\Enums\TipeKaryawan;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\KompensasiCuti;
use App\Models\KonfirmasiKontrakCuti;
use App\Models\RiwayatSaldoCuti;
use App\Models\SaldoCuti;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
            ->berhakJenisCuti($jenisCuti)
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
            ->berhakJenisCuti($jenisCuti)
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
     * ke-2 (lihat PeriodeCutiService::lanjutkanPeriode()). Karyawan kontrak
     * tidak dibuatkan saldo Cuti Besar.
     */
    public function bootstrapUntukKaryawanBaru(Karyawan $karyawan): void
    {
        foreach (JenisCuti::query()->sesuaiGender($karyawan->jenis_kelamin)->berlakuUntukTipe($karyawan->tipe_karyawan)->get() as $jenisCuti) {
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
     * Saat karyawan kontrak diangkat menjadi tetap, jenis cuti khusus
     * karyawan tetap (Cuti Besar) baru mulai dihitung sejak tanggal
     * pengangkatan. Periode ke-1-nya adalah masa kerja minimal dengan kuota
     * 0, kecuali $langsungPenuh (diangkat tetap di akhir K5 — masa kerja 5
     * tahun sudah terpenuhi selama kontrak) yang langsung berkuota penuh.
     * Karyawan yang sudah punya baris aktif untuk jenis cuti itu dilewati.
     */
    public function mulaiSaldoKhususKaryawanTetap(Karyawan $karyawan, CarbonInterface $tanggalPengangkatan, bool $langsungPenuh = false): void
    {
        $jenisCutis = JenisCuti::query()
            ->sesuaiGender($karyawan->jenis_kelamin)
            ->whereNotNull('masa_kerja_minimal_bulan')
            ->get()
            ->filter(fn (JenisCuti $jenisCuti) => $jenisCuti->khususKaryawanTetap());

        foreach ($jenisCutis as $jenisCuti) {
            $sudahAktif = SaldoCuti::query()
                ->where('karyawan_id', $karyawan->id)
                ->where('jenis_cuti_id', $jenisCuti->id)
                ->aktif()
                ->exists();

            if ($sudahAktif) {
                continue;
            }

            $mulai = Carbon::parse($tanggalPengangkatan)->startOfDay();
            $periodeKe = (int) SaldoCuti::query()
                ->where('karyawan_id', $karyawan->id)
                ->where('jenis_cuti_id', $jenisCuti->id)
                ->max('periode_ke') + 1;

            SaldoCuti::query()->create([
                'karyawan_id' => $karyawan->id,
                'jenis_cuti_id' => $jenisCuti->id,
                'tahun' => $mulai->year,
                'periode_ke' => $periodeKe,
                'periode_mulai' => $mulai,
                'periode_selesai' => $mulai->copy()->addMonths($jenisCuti->masa_kerja_minimal_bulan)->subDay(),
                'kuota' => $langsungPenuh ? $jenisCuti->kuota_default : 0,
                'terpakai' => 0,
                'sisa' => $langsungPenuh ? $jenisCuti->kuota_default : 0,
            ]);
        }
    }

    /**
     * Koreksi salah input Tanggal Masuk: periode berjalan setiap jenis cuti
     * bertipe periode (kecuali Cuti Besar, yang dihitung sejak pengangkatan
     * tetap) dipindah ke posisi yang benar menurut tanggal_masuk baru, mis.
     * masuk 2019 → hari ini periode ke-8 (K3 siklus kedua).
     *
     * Kuota mengikuti posisi baru (periode ke-1 dan K1 ulang setelah K5 = 0,
     * selain itu kuota_default); hari yang sudah terpakai di periode
     * berjalan tetap dihitung. Riwayat dari tanggal masuk yang salah
     * (periode tertutup, arsip, konfirmasi kontrak, kompensasi yang belum
     * diproses) dihapus supaya tidak bentrok. Kompensasi yang sudah
     * diproses tetap disimpan.
     */
    public function sesuaikanPeriodeDenganTanggalMasuk(Karyawan $karyawan): void
    {
        $jenisCutis = JenisCuti::query()
            ->sesuaiGender($karyawan->jenis_kelamin)
            ->berlakuUntukTipe($karyawan->tipe_karyawan)
            ->whereNotNull('masa_kerja_minimal_bulan')
            ->get()
            ->reject(fn (JenisCuti $jenisCuti) => $jenisCuti->khususKaryawanTetap());

        DB::transaction(function () use ($karyawan, $jenisCutis) {
            foreach ($jenisCutis as $jenisCuti) {
                $this->pindahkanPeriodeBerjalan($karyawan, $jenisCuti);
            }
        });
    }

    protected function pindahkanPeriodeBerjalan(Karyawan $karyawan, JenisCuti $jenisCuti): void
    {
        $periodeKe = 1;
        [$mulai, $selesai] = $this->hitungTanggalPeriode($karyawan, $jenisCuti, $periodeKe);

        while ($selesai->lt(today())) {
            $periodeKe++;
            [$mulai, $selesai] = $this->hitungTanggalPeriode($karyawan, $jenisCuti, $periodeKe);
        }

        $kuota = $periodeKe === 1
            || ($karyawan->tipe_karyawan === TipeKaryawan::Kontrak && SaldoCuti::urutanKontrakDariPeriode($periodeKe) === 1)
            ? 0
            : $jenisCuti->kuota_default;

        $saldoBerjalan = $this->untukPeriodeAktif($karyawan, $jenisCuti);
        $saldoIds = SaldoCuti::query()
            ->where('karyawan_id', $karyawan->id)
            ->where('jenis_cuti_id', $jenisCuti->id)
            ->pluck('id');

        KonfirmasiKontrakCuti::query()->whereIn('saldo_cuti_id', $saldoIds)->delete();
        KompensasiCuti::query()
            ->where('karyawan_id', $karyawan->id)
            ->where('jenis_cuti_id', $jenisCuti->id)
            ->where('status', StatusKompensasiCuti::MenungguDiproses)
            ->delete();
        RiwayatSaldoCuti::query()
            ->where('karyawan_id', $karyawan->id)
            ->where('jenis_cuti_id', $jenisCuti->id)
            ->delete();
        SaldoCuti::query()
            ->whereIn('id', $saldoIds)
            ->when($saldoBerjalan, fn ($query) => $query->whereKeyNot($saldoBerjalan->id))
            ->delete();

        $terpakai = $saldoBerjalan?->terpakai ?? 0;
        $atribut = [
            'tahun' => $mulai->year,
            'periode_ke' => $periodeKe,
            'periode_mulai' => $mulai,
            'periode_selesai' => $selesai,
            'kuota' => $kuota,
            'terpakai' => $terpakai,
            'sisa' => $kuota - $terpakai,
            'ditutup_pada' => null,
        ];

        if ($saldoBerjalan) {
            $saldoBerjalan->update($atribut);

            return;
        }

        SaldoCuti::query()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            ...$atribut,
        ]);
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
     * Versi massal sesuaikanManual() untuk edit langsung di tabel & import
     * Excel: semua baris disimpan dalam satu transaksi dengan satu catatan
     * yang sama, supaya kalau satu baris gagal tidak ada yang setengah jadi.
     *
     * @param  list<array{id: int, kuota: int|null, terpakai: int, sisa: int|null}>  $perubahan
     */
    public function sesuaikanMassal(array $perubahan, string $catatan, Karyawan $olehSiapa): int
    {
        return DB::transaction(function () use ($perubahan, $catatan, $olehSiapa): int {
            $saldos = SaldoCuti::query()->whereKey(array_column($perubahan, 'id'))->get()->keyBy('id');

            foreach ($perubahan as $baris) {
                $this->sesuaikanManual($saldos[$baris['id']], [
                    'kuota' => $baris['kuota'],
                    'terpakai' => $baris['terpakai'],
                    'sisa' => $baris['sisa'],
                    'catatan' => $catatan,
                ], $olehSiapa);
            }

            return count($perubahan);
        });
    }

    /**
     * Buat saldo satu jenis cuti untuk banyak karyawan sekaligus. Karyawan
     * yang sudah punya baris aktif untuk jenis cuti tsb dilewati (bukan
     * ditimpa) supaya tidak ada dua saldo aktif untuk kombinasi yang sama.
     * Untuk jenis cuti bertipe periode, nomor periode diambil dari periode
     * yang sedang berjalan untuk masing-masing karyawan (berbeda-beda
     * tergantung tanggal_masuk) — tanggalnya tetap dihitung otomatis.
     *
     * @param  list<int>  $karyawanIds
     * @return array{dibuat: int, dilewati: list<string>}
     */
    public function buatMassal(array $karyawanIds, JenisCuti $jenisCuti, ?int $tahun, ?int $kuota, int $terpakai, string $catatan, Karyawan $olehSiapa): array
    {
        $bertipePeriode = $jenisCuti->masa_kerja_minimal_bulan !== null;
        $dibuat = 0;
        $dilewati = [];

        DB::transaction(function () use ($karyawanIds, $jenisCuti, $tahun, $kuota, $terpakai, $catatan, $olehSiapa, $bertipePeriode, &$dibuat, &$dilewati): void {
            foreach (Karyawan::query()->whereKey($karyawanIds)->orderBy('nama')->get() as $karyawan) {
                $barisLama = SaldoCuti::query()
                    ->where('karyawan_id', $karyawan->id)
                    ->where('jenis_cuti_id', $jenisCuti->id);

                if ($jenisCuti->khusus_gender !== null && $jenisCuti->khusus_gender !== $karyawan->jenis_kelamin) {
                    $dilewati[] = "{$karyawan->nama} (khusus karyawan {$jenisCuti->khusus_gender->label()})";

                    continue;
                }

                if ($jenisCuti->khususKaryawanTetap() && $karyawan->tipe_karyawan === TipeKaryawan::Kontrak) {
                    $dilewati[] = "{$karyawan->nama} ({$jenisCuti->nama_jenis} khusus karyawan tetap)";

                    continue;
                }

                if ((clone $barisLama)->aktif()->exists()) {
                    $dilewati[] = "{$karyawan->nama} (sudah punya saldo aktif)";

                    continue;
                }

                $periodeKe = $bertipePeriode ? $this->periodeBerjalanKe($karyawan, $jenisCuti) : null;
                $sudahTercatat = $bertipePeriode
                    ? (clone $barisLama)->where('periode_ke', $periodeKe)->exists()
                    : (clone $barisLama)->where('tahun', $tahun)->exists();

                if ($sudahTercatat) {
                    $dilewati[] = $bertipePeriode
                        ? "{$karyawan->nama} (periode ke-{$periodeKe} sudah tercatat)"
                        : "{$karyawan->nama} (tahun {$tahun} sudah tercatat)";

                    continue;
                }

                $this->buatManual([
                    'karyawan_id' => $karyawan->id,
                    'jenis_cuti_id' => $jenisCuti->id,
                    'tahun' => $bertipePeriode ? null : $tahun,
                    'periode_ke' => $periodeKe,
                    'kuota' => $kuota,
                    'terpakai' => $terpakai,
                    'sisa' => $kuota === null ? null : $kuota - $terpakai,
                    'catatan' => $catatan,
                ], $olehSiapa);

                $dibuat++;
            }
        });

        return ['dibuat' => $dibuat, 'dilewati' => $dilewati];
    }

    /**
     * Nomor periode yang memuat hari ini untuk karyawan + jenis cuti bertipe
     * periode, mengikuti rumus hitungTanggalPeriode(). Karyawan yang
     * tanggal_masuk-nya masih di masa depan dianggap di periode ke-1.
     */
    public function periodeBerjalanKe(Karyawan $karyawan, JenisCuti $jenisCuti): int
    {
        $hariIni = now()->startOfDay();

        if ($karyawan->tanggal_masuk->greaterThan($hariIni)) {
            return 1;
        }

        $periodeKe = intdiv((int) $karyawan->tanggal_masuk->diffInMonths($hariIni), $jenisCuti->masa_kerja_minimal_bulan) + 1;

        while ($this->hitungTanggalPeriode($karyawan, $jenisCuti, $periodeKe)[1]->lessThan($hariIni)) {
            $periodeKe++;
        }

        return $periodeKe;
    }

    /**
     * Hitung hasil aksi massal untuk tiap baris tanpa menyimpan apa pun —
     * dipakai pratinjau (sebelum → sesudah) dan juga oleh
     * terapkanAksiMassal() supaya yang dilihat HRD sama persis dengan yang
     * disimpan. Baris yang hasilnya tidak valid diberi `galat` dan dilewati
     * saat diterapkan (baris lain tetap jalan).
     *
     * @param  Collection<int, SaldoCuti>  $saldos
     * @return list<array{saldo: SaldoCuti, kuota: int|null, terpakai: int, sisa: int|null, galat: string|null}>
     */
    public function hitungAksiMassal(Collection $saldos, AksiMassalSaldoCuti $aksi, ?int $nilai): array
    {
        return $saldos->map(function (SaldoCuti $saldo) use ($aksi, $nilai): array {
            $kuota = $saldo->kuota;
            $sisa = $saldo->sisa;
            $galat = null;

            switch ($aksi) {
                case AksiMassalSaldoCuti::SetKuota:
                    $kuota = $nilai;
                    $sisa = $nilai - $saldo->terpakai;
                    break;
                case AksiMassalSaldoCuti::TambahKuota:
                case AksiMassalSaldoCuti::KurangiKuota:
                    if ($saldo->kuota === null || $saldo->sisa === null) {
                        $galat = 'Saldo tanpa batas, tidak bisa ditambah/dikurangi.';
                        break;
                    }

                    $selisih = $aksi === AksiMassalSaldoCuti::TambahKuota ? $nilai : -$nilai;
                    $kuota = $saldo->kuota + $selisih;
                    $sisa = $saldo->sisa + $selisih;
                    break;
                case AksiMassalSaldoCuti::Hapus:
                    break;
            }

            if ($galat === null && $kuota !== null && ($kuota < 0 || $kuota > 365)) {
                $galat = 'Kuota hasil harus di antara 0 dan 365.';
            } elseif ($galat === null && $sisa !== null && $sisa < 0) {
                $galat = 'Sisa hasil menjadi minus.';
            }

            return ['saldo' => $saldo, 'kuota' => $kuota, 'terpakai' => $saldo->terpakai, 'sisa' => $sisa, 'galat' => $galat];
        })->values()->all();
    }

    /**
     * @param  Collection<int, SaldoCuti>  $saldos
     * @return array{diterapkan: int, dilewati: int}
     */
    public function terapkanAksiMassal(Collection $saldos, AksiMassalSaldoCuti $aksi, ?int $nilai, string $catatan, Karyawan $olehSiapa): array
    {
        $hasil = $this->hitungAksiMassal($saldos, $aksi, $nilai);
        $valid = array_values(array_filter($hasil, fn (array $baris): bool => $baris['galat'] === null));

        DB::transaction(function () use ($valid, $aksi, $catatan, $olehSiapa): void {
            foreach ($valid as $baris) {
                if ($aksi === AksiMassalSaldoCuti::Hapus) {
                    $baris['saldo']->delete();

                    continue;
                }

                $this->sesuaikanManual($baris['saldo'], [
                    'kuota' => $baris['kuota'],
                    'terpakai' => $baris['terpakai'],
                    'sisa' => $baris['sisa'],
                    'catatan' => $catatan,
                ], $olehSiapa);
            }
        });

        return ['diterapkan' => count($valid), 'dilewati' => count($hasil) - count($valid)];
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
