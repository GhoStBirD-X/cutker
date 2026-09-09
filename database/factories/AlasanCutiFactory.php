<?php

namespace Database\Factories;

use App\Models\AlasanCuti;
use App\Models\JenisCuti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlasanCuti>
 */
class AlasanCutiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'jenis_cuti_id' => JenisCuti::factory(),
            'nama_alasan' => fake()->unique()->sentence(3),
            'jumlah_hari' => fake()->numberBetween(1, 5),
            'keterangan' => null,
        ];
    }
}
