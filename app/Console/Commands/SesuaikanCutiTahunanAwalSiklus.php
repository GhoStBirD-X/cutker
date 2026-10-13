<?php

namespace App\Console\Commands;

use App\Enums\TipeKaryawan;
use App\Models\JenisCuti;
use App\Models\SaldoCuti;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

#[Signature('cuti:sesuaikan-tahunan-awal-siklus
    {--terapkan : Ubah saldonya; tanpa opsi ini hanya menampilkan daftar}')]
#[Description('Nolkan Cuti Tahunan karyawan tetap yang periode berjalannya jatuh di tahun setelah kelipatan lima tahun (periode ke-6, 11, …) tetapi masih berkuota')]
class SesuaikanCutiTahunanAwalSiklus extends Command
{
    public const CATATAN = 'Tahun setelah kelipatan lima tahun masa kerja: Cuti Tahunan tidak didapat.';

    /**
     * Data lama dari sebelum aturan ini berlaku. Hari yang sudah terpakai
     * tetap dihormati (kuota = terpakai, sisa 0) seperti koreksi tinjauan
     * siklus kontrak, supaya cuti yang sudah disetujui tidak jadi minus.
     */
    public function handle(): int
    {
        $saldos = SaldoCuti::query()
            ->with('karyawan')
            ->aktif()
            ->whereNotNull('periode_ke')
            ->where('periode_ke', '>', 1)
            ->whereRaw('(periode_ke - 1) % ? = 0', [SaldoCuti::PANJANG_SIKLUS_KONTRAK])
            ->whereColumn('kuota', '>', 'terpakai')
            ->whereHas('jenisCuti', fn (Builder $query) => $query->where('nama_jenis', JenisCuti::NAMA_CUTI_TAHUNAN))
            ->whereHas('karyawan', fn (Builder $query) => $query->where('tipe_karyawan', TipeKaryawan::Tetap))
            ->orderBy('karyawan_id')
            ->get();

        if ($saldos->isEmpty()) {
            $this->info('Tidak ada saldo Cuti Tahunan yang perlu disesuaikan.');

            return self::SUCCESS;
        }

        $this->table(['Karyawan', 'NPK', 'Periode', 'Kuota', 'Terpakai', 'Sisa', 'Menjadi kuota/sisa'], $saldos->map(fn (SaldoCuti $saldo) => [
            $saldo->karyawan->nama,
            $saldo->karyawan->nip,
            "ke-{$saldo->periode_ke} ({$saldo->periode_mulai->toDateString()} s/d {$saldo->periode_selesai->toDateString()})",
            $saldo->kuota,
            $saldo->terpakai,
            $saldo->sisa,
            "{$saldo->terpakai}/0",
        ]));

        if (! $this->option('terapkan')) {
            $this->warn("{$saldos->count()} saldo ditemukan. Jalankan lagi dengan --terapkan untuk mengubahnya.");

            return self::SUCCESS;
        }

        DB::transaction(function () use ($saldos) {
            foreach ($saldos as $saldo) {
                $saldo->update([
                    'kuota' => $saldo->terpakai,
                    'sisa' => 0,
                    'catatan' => self::CATATAN,
                    'diubah_oleh_id' => null,
                    'diubah_pada' => now(),
                ]);
            }
        });

        $this->info("{$saldos->count()} saldo Cuti Tahunan disesuaikan.");

        return self::SUCCESS;
    }
}
