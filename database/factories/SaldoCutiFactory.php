<?php

namespace Database\Factories;

use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\SaldoCuti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaldoCuti>
 */
class SaldoCutiFactory extends Factory
{
    public function definition(): array
    {
        $kuota = fake()->numberBetween(3, 12);
        $terpakai = fake()->numberBetween(0, $kuota);

        return [
            'karyawan_id' => Karyawan::factory(),
            'jenis_cuti_id' => JenisCuti::factory(),
            'tahun' => (int) now()->format('Y'),
            'kuota' => $kuota,
            'terpakai' => $terpakai,
            'sisa' => $kuota - $terpakai,
        ];
    }
}
