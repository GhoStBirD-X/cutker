<?php

namespace Tests\Feature\Auth;

use App\Enums\PerangkatLogin;
use App\Models\RiwayatLogin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatatRiwayatLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_records_the_device_used(): void
    {
        $user = User::factory()->create();
        $userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148';

        $this->withHeader('User-Agent', $userAgent)->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $riwayat = RiwayatLogin::sole();
        $this->assertSame($user->id, $riwayat->user_id);
        $this->assertSame(PerangkatLogin::Ios, $riwayat->perangkat);
        $this->assertSame($userAgent, $riwayat->user_agent);
    }

    public function test_failed_login_is_not_recorded(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'salah',
        ]);

        $this->assertDatabaseCount('riwayat_logins', 0);
    }
}
