<?php

namespace App\Console\Commands;

use App\Services\PengajuanCutiService;
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
    public function handle(PeriodeCutiService $periodeCutiService, PengajuanCutiService $pengajuanCutiService): int
    {
        $diproses = $periodeCutiService->prosesSemuaKaryawan();

        $this->info("Siklus cuti tahunan diproses: {$diproses} periode ditutup.");

        $kedaluwarsa = $pengajuanCutiService->batalkanPenampunganKedaluwarsa();

        $this->info("Pengajuan di penampungan yang tanggalnya sudah lewat dibatalkan: {$kedaluwarsa}.");

        return self::SUCCESS;
    }
}
