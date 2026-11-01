<?php

namespace App\Services;

use App\Enums\StatusApproval;
use App\Enums\StatusCutiMassal;
use App\Enums\StatusKaryawan;
use App\Enums\StatusPengajuan;
use App\Enums\TipeKaryawan;
use App\Exceptions\CutiMassalSudahDibatalkanException;
use App\Models\Approval;
use App\Models\CutiMassal;
use App\Models\JadwalShift;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\SaldoCuti;
use App\Notifications\CutiMassalDibatalkan;
use App\Notifications\PengajuanCutiDisetujui;
use Carbon\CarbonInterface;
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
     * saldonya akan minus (atau dapat bonus kuota, untuk karyawan kontrak
     * pertama — lihat hitungPotongan()) jika cuti massal ini jadi diajukan,
     * untuk preview sebelum HRD submit.
     *
     * @return SupportCollection<int, array{karyawan: Karyawan, saldo: ?SaldoCuti, akan_minus: bool, akan_dapat_bonus: bool, akan_ditampung: bool, dari_cuti_besar: int}>
     */
    public function previewKaryawan(JenisCuti $jenisCuti, Carbon $mulai, Carbon $selesai): SupportCollection
    {
        $jumlahHari = $this->hitungJumlahHari($mulai, $selesai)['hari'];

        $preview = [];

        foreach ($this->eligibleKaryawan($jenisCuti) as $karyawan) {
            $saldo = $this->saldoCutiService->untukPeriodeAktif($karyawan, $jenisCuti);
            $akanDitampung = $saldo?->berakhirSebelum($selesai) ?? false;
            $dariCutiBesar = $saldo && ! $akanDitampung
                ? $this->pengalihanCutiBesar($karyawan, $jenisCuti, $saldo, $jumlahHari, $selesai)['hari']
                : 0;
            $hariDariSaldo = $jumlahHari - $dariCutiBesar;
            $potongan = $saldo && ! $akanDitampung ? $this->hitungPotongan($saldo, $karyawan, $hariDariSaldo) : null;

            $preview[] = [
                'karyawan' => $karyawan,
                'saldo' => $saldo,
                'akan_minus' => $potongan !== null && $saldo->kuota !== null && ($saldo->sisa - $hariDariSaldo) < 0 && $potongan['bonus'] === 0,
                'akan_dapat_bonus' => ($potongan['bonus'] ?? 0) > 0,
                'akan_ditampung' => $akanDitampung,
                'dari_cuti_besar' => $dariCutiBesar,
            ];
        }

        return SupportCollection::make($preview);
    }

    /**
     * Karyawan yang eligible untuk jenis cuti & tanggal batch ini tapi
     * belum disertakan (mis. baru masuk kerja setelah batch dibuat) —
     * dipakai halaman detail Cuti Massal untuk menyusulkan karyawan baru
     * ke batch yang sudah berjalan tanpa mengulang isi jenis cuti/
     * tanggal/alasan dari awal.
     *
     * @return SupportCollection<int, array{karyawan: Karyawan, saldo: ?SaldoCuti, akan_minus: bool, akan_dapat_bonus: bool, akan_ditampung: bool, dari_cuti_besar: int}>
     */
    public function previewKaryawanBaruUntukBatch(CutiMassal $cutiMassal): SupportCollection
    {
        $sudahIkutIds = $cutiMassal->pengajuanCutis()->pluck('karyawan_id');

        // previewKaryawan() mensyaratkan Illuminate\Support\Carbon (mutable),
        // sedangkan atribut tanggal model ini CarbonImmutable (konfigurasi
        // global Date::use()) — konversi eksplisit lewat Carbon::parse().
        return $this->previewKaryawan(
            $cutiMassal->jenisCuti,
            Carbon::parse($cutiMassal->tanggal_mulai),
            Carbon::parse($cutiMassal->tanggal_selesai),
        )
            ->reject(fn (array $baris) => $sudahIkutIds->contains($baris['karyawan']->id))
            ->values();
    }

    /**
     * @return array{kalender: int, hari: int}
     */
    protected function hitungJumlahHari(Carbon $mulai, Carbon $selesai): array
    {
        $jumlahHariKalender = (int) $mulai->diffInDays($selesai) + 1;
        $jumlahHariLibur = $this->hariLiburService->hitungHariLibur($mulai, $selesai);

        return [
            'kalender' => $jumlahHariKalender,
            'hari' => max(0, $jumlahHariKalender - $jumlahHariLibur),
        ];
    }

    /**
     * Karyawan kontrak yang masih di periode pertama (belum pernah
     * diperpanjang) belum benar-benar "punya" cuti tahunan — jadi kalau
     * cuti massal membuat saldonya minus, bukan dijadikan utang, tapi
     * kuotanya ditambah (bonus) secukupnya supaya sisa jadi 0. Karyawan
     * kontrak yang sudah masuk K2–K5 (siklus kontrak kembali ke K1 setelah
     * K5, dan K1 berikutnya ikut aturan ini lagi), dan karyawan tetap,
     * tetap boleh minus seperti biasa (sengaja — lihat buat()).
     *
     * @return array{kuota: ?int, terpakai: int, sisa: ?int, bonus: int}
     */
    private function hitungPotongan(SaldoCuti $saldo, Karyawan $karyawan, int $jumlahHari): array
    {
        $terpakaiBaru = $saldo->terpakai + $jumlahHari;

        if ($saldo->kuota === null) {
            return ['kuota' => null, 'terpakai' => $terpakaiBaru, 'sisa' => null, 'bonus' => 0];
        }

        $sisaBaru = $saldo->sisa - $jumlahHari;
        $kontrakPertama = $karyawan->tipe_karyawan === TipeKaryawan::Kontrak && $saldo->periode_ke !== null && $saldo->urutanKontrak() === 1;

        if ($sisaBaru < 0 && $kontrakPertama) {
            $bonus = -$sisaBaru;

            return ['kuota' => $saldo->kuota + $bonus, 'terpakai' => $terpakaiBaru, 'sisa' => 0, 'bonus' => $bonus];
        }

        return ['kuota' => $saldo->kuota, 'terpakai' => $terpakaiBaru, 'sisa' => $sisaBaru, 'bonus' => 0];
    }

    /**
     * Buat satu batch cuti massal. Tiap karyawan target mendapat satu
     * PengajuanCuti + 3 Approval yang langsung berstatus Disetujui (bukan
     * lewat alur approval berjenjang biasa), dan saldo cutinya dipotong
     * seketika — sengaja tanpa guard kecukupan saldo seperti
     * PengajuanCutiService::ajukan(), sehingga karyawan yang saldonya belum
     * cukup tetap diproses dan boleh menjadi minus, KECUALI karyawan
     * kontrak yang masih di periode pertama (belum pernah diperpanjang) —
     * lihat hitungPotongan(). Karyawan yang konflik (gender, overlap
     * pengajuan lain, bentrok jadwal shift, atau tidak punya baris saldo
     * aktif) dilewati dengan alasan tercatat, bukan menggagalkan seluruh
     * batch. Karyawan yang periode saldonya berakhir sebelum tanggal selesai
     * cuti massal tidak dipotong dari periode lama: pengajuannya ditampung
     * (status Ditampung) dan diproses HRD lewat prosesPenampungan() setelah
     * periode barunya aktif. Notifikasi hanya lewat database + email, tanpa WhatsApp —
     * kiriman ke puluhan nomor sekaligus membuat nomor WhatsApp kantor
     * di-suspend (kejadian 29 Okt 2026).
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
                    'status' => StatusPengajuan::Ditampung,
                    'tanggal_pengajuan' => now(),
                ]);

                if (! $saldo->berakhirSebelum($selesai)) {
                    $this->setujui($pengajuan, $saldo, $karyawan, $olehSiapa);
                }

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
            ->whereIn('status', [StatusPengajuan::Pending, StatusPengajuan::Disetujui, StatusPengajuan::Ditampung])
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
     * HRD memproses pengajuan yang ditampung di batch ini: yang periode
     * saldonya sudah diperbarui (periode aktif mencakup tanggal cuti)
     * disetujui & dipotong dari periode baru, sisanya tetap ditampung.
     *
     * @return int Jumlah pengajuan yang berhasil diproses
     */
    public function prosesPenampungan(CutiMassal $cutiMassal, Karyawan $olehSiapa): int
    {
        if ($cutiMassal->status !== StatusCutiMassal::Aktif) {
            throw new CutiMassalSudahDibatalkanException;
        }

        return DB::transaction(function () use ($cutiMassal, $olehSiapa) {
            $diproses = 0;

            $penampungan = $cutiMassal->pengajuanCutis()
                ->where('status', StatusPengajuan::Ditampung)
                ->with('karyawan.user')
                ->get();

            foreach ($penampungan as $pengajuan) {
                $saldo = $this->saldoSiapDipotong($pengajuan, lock: true);

                if ($saldo === null) {
                    continue;
                }

                $this->setujui($pengajuan, $saldo, $pengajuan->karyawan, $olehSiapa);
                $diproses++;
            }

            return $diproses;
        });
    }

    /**
     * Pengajuan yang ditampung di batch ini, masing-masing dengan penanda
     * `siap_diproses` (periode saldo barunya sudah aktif) untuk halaman
     * detail Cuti Massal — hanya untuk ditampilkan, jangan di-save().
     *
     * @return Collection<int, PengajuanCuti>
     */
    public function penampungan(CutiMassal $cutiMassal): Collection
    {
        return $cutiMassal->pengajuanCutis()
            ->where('status', StatusPengajuan::Ditampung)
            ->with('karyawan.user', 'jenisCuti')
            ->get()
            ->each(fn (PengajuanCuti $pengajuan) => $pengajuan->setAttribute('siap_diproses', $this->saldoSiapDipotong($pengajuan) !== null));
    }

    protected function saldoSiapDipotong(PengajuanCuti $pengajuan, bool $lock = false): ?SaldoCuti
    {
        $saldo = SaldoCuti::query()
            ->where('karyawan_id', $pengajuan->karyawan_id)
            ->where('jenis_cuti_id', $pengajuan->jenis_cuti_id)
            ->aktif()
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->latest('id')
            ->first();

        return $saldo && ! $saldo->berakhirSebelum($pengajuan->tanggal_selesai) ? $saldo : null;
    }

    /**
     * Setujui satu pengajuan cuti massal. Untuk karyawan tetap yang saldo
     * Cuti Tahunannya tidak cukup, kekurangannya dialihkan ke Cuti Besar
     * (sampai saldo Cuti Besar habis) sebagai pengajuan Cuti Besar terpisah
     * pada tanggal yang sama — sisanya tetap minus di Cuti Tahunan. Jika
     * seluruh hari dialihkan, pengajuan ini sendiri diubah jadi Cuti Besar.
     */
    protected function setujui(PengajuanCuti $pengajuan, SaldoCuti $saldo, Karyawan $karyawan, Karyawan $olehSiapa): void
    {
        ['saldo_besar' => $saldoBesar, 'hari' => $dariCutiBesar] = $this->pengalihanCutiBesar(
            $karyawan,
            $pengajuan->jenisCuti,
            $saldo,
            $pengajuan->jumlah_hari,
            $pengajuan->tanggal_selesai,
            lock: true,
        );

        if ($dariCutiBesar === $pengajuan->jumlah_hari) {
            $pengajuan->update(['jenis_cuti_id' => $saldoBesar->jenis_cuti_id]);
            $this->setujuiDariSaldo($pengajuan, $saldoBesar, $karyawan, $olehSiapa);

            return;
        }

        if ($dariCutiBesar > 0) {
            $pengajuan->update(['jumlah_hari' => $pengajuan->jumlah_hari - $dariCutiBesar]);

            $pengajuanCutiBesar = $pengajuan->replicate();
            $pengajuanCutiBesar->fill([
                'jenis_cuti_id' => $saldoBesar->jenis_cuti_id,
                'jumlah_hari' => $dariCutiBesar,
            ])->save();

            $this->setujuiDariSaldo($pengajuanCutiBesar, $saldoBesar, $karyawan, $olehSiapa);
        }

        $this->setujuiDariSaldo($pengajuan, $saldo, $karyawan, $olehSiapa);
    }

    /**
     * Berapa hari cuti massal Cuti Tahunan yang dialihkan ke Cuti Besar:
     * hanya untuk karyawan tetap, hanya kekurangan saldo Cuti Tahunan, dan
     * paling banyak sebesar sisa Cuti Besar yang periodenya mencakup
     * tanggal cuti.
     *
     * @return array{saldo_besar: ?SaldoCuti, hari: int}
     */
    protected function pengalihanCutiBesar(Karyawan $karyawan, JenisCuti $jenisCuti, SaldoCuti $saldo, int $jumlahHari, CarbonInterface $tanggalSelesai, bool $lock = false): array
    {
        $tidakDialihkan = ['saldo_besar' => null, 'hari' => 0];
        $kekurangan = $jumlahHari - max(0, $saldo->sisa ?? $jumlahHari);

        if ($karyawan->tipe_karyawan !== TipeKaryawan::Tetap
            || $jenisCuti->nama_jenis !== JenisCuti::NAMA_CUTI_TAHUNAN
            || $saldo->kuota === null
            || $kekurangan <= 0) {
            return $tidakDialihkan;
        }

        $saldoBesar = SaldoCuti::query()
            ->where('karyawan_id', $karyawan->id)
            ->whereHas('jenisCuti', fn ($query) => $query->where('nama_jenis', JenisCuti::NAMA_CUTI_BESAR))
            ->aktif()
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->latest('id')
            ->first();

        if (! $saldoBesar || $saldoBesar->berakhirSebelum($tanggalSelesai)) {
            return $tidakDialihkan;
        }

        $hari = $saldoBesar->kuota === null ? $kekurangan : min($kekurangan, max(0, $saldoBesar->sisa));

        return $hari > 0 ? ['saldo_besar' => $saldoBesar, 'hari' => $hari] : $tidakDialihkan;
    }

    /**
     * 3 Approval langsung Disetujui, saldo dipotong (lihat
     * hitungPotongan()), lalu karyawan diberi tahu.
     */
    protected function setujuiDariSaldo(PengajuanCuti $pengajuan, SaldoCuti $saldo, Karyawan $karyawan, Karyawan $olehSiapa): void
    {
        $potongan = $this->hitungPotongan($saldo, $karyawan, $pengajuan->jumlah_hari);

        $pengajuan->update([
            'status' => StatusPengajuan::Disetujui,
            'bonus_kuota_kontrak_pertama' => $potongan['bonus'],
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
            'kuota' => $potongan['kuota'],
            'terpakai' => $potongan['terpakai'],
            'sisa' => $potongan['sisa'],
        ]);

        $karyawan->user?->notify(new PengajuanCutiDisetujui($pengajuan->fresh('jenisCuti'), tanpaWhatsapp: true));
    }

    /**
     * Batalkan satu batch cuti massal: kembalikan saldo & tandai
     * Dibatalkan untuk setiap PengajuanCuti+Approval yang masih Disetujui.
     * Saldo dikembalikan ke baris yang sedang aktif saat ini (bukan
     * dilacak per baris asli) — jika saldo karyawan sudah pindah
     * periode/disesuaikan manual sejak batch dibuat, koreksi diterapkan ke
     * baris yang aktif sekarang, bukan baris asli. Tiap karyawan diberi tahu
     * lewat database + email (tanpa WhatsApp) setelah transaksi selesai.
     * Pengajuan yang masih ditampung ikut dibatalkan tanpa notifikasi —
     * saldonya belum pernah dipotong.
     */
    public function batalkan(CutiMassal $cutiMassal, Karyawan $olehSiapa, ?string $catatan = null): void
    {
        if ($cutiMassal->status !== StatusCutiMassal::Aktif) {
            throw new CutiMassalSudahDibatalkanException;
        }

        $pengajuanCutis = DB::transaction(function () use ($cutiMassal, $olehSiapa, $catatan) {
            $pengajuanCutis = $cutiMassal->pengajuanCutis()
                ->where('status', StatusPengajuan::Disetujui)
                ->with('karyawan.user', 'jenisCuti')
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
                    // sisa dikembalikan dengan jumlah_hari lalu dikurangi
                    // bonus yang ikut ditarik kembali, supaya kuota,
                    // terpakai, dan sisa tetap konsisten (kuota - terpakai
                    // = sisa) setelah pembatalan.
                    $saldo->update([
                        'kuota' => $saldo->kuota === null ? null : max(0, $saldo->kuota - $pengajuan->bonus_kuota_kontrak_pertama),
                        'terpakai' => max(0, $saldo->terpakai - $pengajuan->jumlah_hari),
                        'sisa' => $saldo->kuota === null ? null : $saldo->sisa + $pengajuan->jumlah_hari - $pengajuan->bonus_kuota_kontrak_pertama,
                    ]);
                }

                $pengajuan->update(['status' => StatusPengajuan::Dibatalkan]);
                $pengajuan->approvals()->update(['status' => StatusApproval::Dibatalkan]);
            }

            $cutiMassal->pengajuanCutis()
                ->where('status', StatusPengajuan::Ditampung)
                ->update(['status' => StatusPengajuan::Dibatalkan]);

            $cutiMassal->update([
                'status' => StatusCutiMassal::Dibatalkan,
                'dibatalkan_oleh_id' => $olehSiapa->id,
                'dibatalkan_pada' => now(),
                'catatan_pembatalan' => $catatan,
            ]);

            return $pengajuanCutis;
        });

        foreach ($pengajuanCutis as $pengajuan) {
            $pengajuan->karyawan->user?->notify(new CutiMassalDibatalkan($pengajuan, $catatan));
        }
    }
}
