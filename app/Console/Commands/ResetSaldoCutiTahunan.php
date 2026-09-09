<?php

namespace App\Console\Commands;

use App\Services\SaldoCutiService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('cuti:reset-saldo-tahunan {--tahun= : Tahun saldo yang digenerate, default tahun berjalan}')]
#[Description('Generate saldo cuti tahunan baru untuk setiap karyawan aktif berdasarkan kuota_default jenis cuti')]
class ResetSaldoCutiTahunan extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SaldoCutiService $saldoCutiService): int
    {
        $tahun = (int) ($this->option('tahun') ?? now()->year);

        $dibuat = $saldoCutiService->resetTahunan($tahun);

        $this->info("Saldo cuti tahun {$tahun} berhasil digenerate: {$dibuat} record baru dibuat.");

        return self::SUCCESS;
    }
}
