<?php

namespace Database\Factories;

use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\RiwayatSaldoCuti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiwayatSaldoCuti>
 */
class RiwayatSaldoCutiFactory extends Factory
{
    public function definition(): array
    {
        $kuota = fake()->numberBetween(3, 12);
        $terpakai = fake()->numberBetween(0, $kuota);
        $mulai = fake()->dateTimeBetween('-2 years', '-1 year');
        $selesai = (clone $mulai)->modify('+1 year -1 day');

        return [
            'karyawan_id' => Karyawan::factory(),
            'jenis_cuti_id' => JenisCuti::factory(),
            'periode_ke' => 1,
            'periode_mulai' => $mulai,
            'periode_selesai' => $selesai,
            'kuota' => $kuota,
            'terpakai' => $terpakai,
            'sisa' => $kuota - $terpakai,
        ];
    }
}
