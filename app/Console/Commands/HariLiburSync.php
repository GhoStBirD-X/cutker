<?php

namespace App\Console\Commands;

use App\Services\HariLiburService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('libur:sync-nasional {--tahun= : Tahun hari libur yang disinkronkan, default tahun berjalan}')]
#[Description('Sinkronkan hari libur nasional dari kalender Indonesia ke tabel hari_liburs')]
class HariLiburSync extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(HariLiburService $hariLiburService): int
    {
        $tahun = (int) ($this->option('tahun') ?? now()->year);

        $disinkron = $hariLiburService->sync($tahun);

        $this->info("Hari libur nasional tahun {$tahun} berhasil disinkronkan: {$disinkron} tanggal.");

        return self::SUCCESS;
    }
}
