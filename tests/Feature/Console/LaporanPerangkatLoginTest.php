<?php

namespace Tests\Feature\Console;

use App\Enums\PerangkatLogin;
use App\Models\RiwayatLogin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanPerangkatLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_counts_distinct_users_per_device_within_the_requested_days(): void
    {
        $penggunaAndroid = User::factory()->create();
        $penggunaCampuran = User::factory()->create();
        $penggunaLama = User::factory()->create();

        RiwayatLogin::factory()->count(3)->create(['user_id' => $penggunaAndroid->id, 'perangkat' => PerangkatLogin::Android]);
        RiwayatLogin::factory()->create(['user_id' => $penggunaCampuran->id, 'perangkat' => PerangkatLogin::Ios]);
        RiwayatLogin::factory()->create(['user_id' => $penggunaCampuran->id, 'perangkat' => PerangkatLogin::Komputer]);
        RiwayatLogin::factory()->create([
            'user_id' => $penggunaLama->id,
            'perangkat' => PerangkatLogin::Android,
            'created_at' => now()->subDays(8),
        ]);

        $this->artisan('laporan:perangkat-login', ['--hari' => 7])
            ->expectsTable(['Perangkat', 'Jumlah user'], [
                ['Android', 1],
                ['iPhone/iPad', 1],
                ['Komputer/lainnya', 1],
            ])
            ->expectsOutput('Total user berbeda: 2')
            ->assertExitCode(0);
    }
}
