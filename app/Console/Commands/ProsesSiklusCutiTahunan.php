<?php

namespace App\Console\Commands;

use App\Services\PeriodeCutiService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('cuti:proses-siklus-tahunan')]
#[Description('Tutup periode cuti tahunan/besar yang sudah lewat berdasarkan tanggal_masuk tiap karyawan')]
class ProsesSiklusCutiTahunan extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(PeriodeCutiService $periodeCutiService): int
    {
        $diproses = $periodeCutiService->prosesSemuaKaryawan();

        $this->info("Siklus cuti tahunan diproses: {$diproses} periode ditutup.");

        return self::SUCCESS;
    }
}
