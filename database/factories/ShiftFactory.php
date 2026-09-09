<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_shift' => fake()->randomElement(['Pagi', 'Siang', 'Malam']),
            'jam_mulai' => '07:00',
            'jam_selesai' => '15:00',
        ];
    }
}
