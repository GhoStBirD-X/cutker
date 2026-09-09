<?php

namespace Database\Factories;

use App\Models\JenisCuti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JenisCuti>
 */
class JenisCutiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_jenis' => fake()->unique()->randomElement(['Cuti Tahunan', 'Cuti Sakit', 'Cuti Melahirkan', 'Cuti Alasan Penting']),
            'kuota_default' => fake()->numberBetween(3, 12),
            'masa_kerja_minimal_bulan' => null,
            'keterangan' => fake()->sentence(),
        ];
    }
}
