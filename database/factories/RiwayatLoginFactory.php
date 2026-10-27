<?php

namespace Database\Factories;

use App\Enums\PerangkatLogin;
use App\Models\RiwayatLogin;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiwayatLogin>
 */
class RiwayatLoginFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'perangkat' => PerangkatLogin::Komputer,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'ip_address' => fake()->ipv4(),
        ];
    }
}
