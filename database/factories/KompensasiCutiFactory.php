<?php

namespace Database\Factories;

use App\Enums\StatusKompensasiCuti;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\KompensasiCuti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KompensasiCuti>
 */
class KompensasiCutiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'karyawan_id' => Karyawan::factory(),
            'jenis_cuti_id' => JenisCuti::factory(),
            'riwayat_saldo_cuti_id' => null,
            'jumlah_hari' => fake()->numberBetween(1, 12),
            'rate_per_hari' => null,
            'total_rupiah' => null,
            'status' => StatusKompensasiCuti::MenungguDiproses,
            'diproses_oleh_id' => null,
            'diproses_pada' => null,
            'catatan' => null,
        ];
    }

    public function diproses(): static
    {
        return $this->state(fn (array $attributes) => [
            'rate_per_hari' => 150000,
            'total_rupiah' => 150000 * $attributes['jumlah_hari'],
            'status' => StatusKompensasiCuti::Diproses,
            'diproses_oleh_id' => Karyawan::factory(),
            'diproses_pada' => now(),
        ]);
    }
}
