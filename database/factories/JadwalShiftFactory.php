<?php

namespace Database\Factories;

use App\Models\JadwalShift;
use App\Models\Karyawan;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JadwalShift>
 */
class JadwalShiftFactory extends Factory
{
    public function definition(): array
    {
        return [
            'karyawan_id' => Karyawan::factory(),
            'shift_id' => Shift::factory(),
            'tanggal' => fake()->dateTimeBetween('-1 week', '+2 weeks'),
        ];
    }
}
