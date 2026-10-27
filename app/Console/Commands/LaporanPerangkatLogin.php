<?php

namespace App\Console\Commands;

use App\Enums\PerangkatLogin;
use App\Models\RiwayatLogin;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('laporan:perangkat-login {--hari=30 : Rentang hari ke belakang yang dihitung}')]
#[Description('Tampilkan jumlah user yang login per jenis perangkat (Android/iPhone/komputer)')]
class LaporanPerangkatLogin extends Command
{
    /**
     * Satu user yang login dari HP dan komputer terhitung di kedua baris,
     * jadi jumlah per perangkat bisa melebihi total user.
     */
    public function handle(): int
    {
        $hari = (int) $this->option('hari');

        $login = RiwayatLogin::query()
            ->where('created_at', '>=', now()->subDays($hari))
            ->get(['user_id', 'perangkat']);

        $baris = collect(PerangkatLogin::cases())->map(fn (PerangkatLogin $perangkat) => [
            $perangkat->label(),
            $login->where('perangkat', $perangkat)->pluck('user_id')->unique()->count(),
        ]);

        $this->info("Login {$hari} hari terakhir:");
        $this->table(['Perangkat', 'Jumlah user'], $baris->all());
        $this->line("Total user berbeda: {$login->pluck('user_id')->unique()->count()}");

        return self::SUCCESS;
    }
}
