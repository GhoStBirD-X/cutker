<?php

namespace App\Console\Commands;

use App\Models\SaldoCuti;
use App\Services\PeriodeCutiService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('kontrak:tinjau-siklus {--simulasi : Hanya tampilkan daftar karyawan, tanpa membuat konfirmasi}')]
#[Description('Masukkan karyawan kontrak yang telanjur melewati K5 (periode ke-6 dst.) ke daftar Konfirmasi Kontrak untuk diputuskan HRD')]
class TinjauSiklusKontrak extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(PeriodeCutiService $periodeCutiService): int
    {
        $kandidat = $periodeCutiService->kandidatTinjauanSiklus();

        if ($kandidat->isEmpty()) {
            $this->info('Tidak ada karyawan kontrak yang telanjur melewati K5.');

            return self::SUCCESS;
        }

        $this->table(
            ['NPK', 'Nama', 'Periode ke', 'Posisi sekarang', 'Kuota', 'Sisa'],
            $kandidat->map(fn (SaldoCuti $saldo) => [
                $saldo->karyawan->nip,
                $saldo->karyawan->nama,
                $saldo->periode_ke,
                'K'.$saldo->urutanKontrak(),
                $saldo->kuota ?? '∞',
                $saldo->sisa ?? '∞',
            ])->all(),
        );

        if ($this->option('simulasi')) {
            $this->info("Simulasi: {$kandidat->count()} karyawan akan dimasukkan ke Konfirmasi Kontrak. Jalankan tanpa --simulasi untuk menerapkan.");

            return self::SUCCESS;
        }

        $kandidat->each(fn (SaldoCuti $saldo) => $periodeCutiService->buatTinjauanSiklus($saldo));

        $this->info("{$kandidat->count()} karyawan dimasukkan ke Konfirmasi Kontrak. HRD dapat memutuskan: angkat tetap, kontrak ulang ke K1 (wajib alasan), atau tidak diperpanjang.");

        return self::SUCCESS;
    }
}
