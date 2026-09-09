<?php

namespace Database\Factories;

use App\Enums\SumberHariLibur;
use App\Models\HariLibur;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HariLibur>
 */
class HariLiburFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tanggal' => fake()->unique()->dateTimeBetween('-1 year', '+1 year'),
            'keterangan' => fake()->sentence(3),
            'sumber' => SumberHariLibur::Nasional,
        ];
    }
}
