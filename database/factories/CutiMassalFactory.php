<?php

namespace Database\Factories;

use App\Enums\StatusCutiMassal;
use App\Models\CutiMassal;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CutiMassal>
 */
class CutiMassalFactory extends Factory
{
    public function definition(): array
    {
        $mulai = fake()->dateTimeBetween('+1 day', '+1 month');
        $selesai = (clone $mulai)->modify('+'.fake()->numberBetween(1, 3).' days');
        $jumlahHari = $mulai->diff($selesai)->days + 1;

        return [
            'jenis_cuti_id' => JenisCuti::factory(),
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $selesai,
            'jumlah_hari' => $jumlahHari,
            'jumlah_hari_kalender' => $jumlahHari,
            'alasan' => fake()->sentence(),
            'dibuat_oleh_id' => Karyawan::factory(),
            'jumlah_karyawan' => 0,
            'dilewati' => [],
            'status' => StatusCutiMassal::Aktif,
        ];
    }

    public function dibatalkan(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusCutiMassal::Dibatalkan,
            'dibatalkan_oleh_id' => Karyawan::factory(),
            'dibatalkan_pada' => now(),
        ]);
    }
}
