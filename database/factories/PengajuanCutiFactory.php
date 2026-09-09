<?php

namespace Database\Factories;

use App\Enums\StatusPengajuan;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PengajuanCuti>
 */
class PengajuanCutiFactory extends Factory
{
    public function definition(): array
    {
        $mulai = fake()->dateTimeBetween('-1 month', '+1 month');
        $selesai = (clone $mulai)->modify('+'.fake()->numberBetween(0, 4).' days');

        return [
            'karyawan_id' => Karyawan::factory(),
            'jenis_cuti_id' => JenisCuti::factory(),
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $selesai,
            'jumlah_hari' => $mulai->diff($selesai)->days + 1,
            'jumlah_hari_kalender' => $mulai->diff($selesai)->days + 1,
            'alasan' => fake()->sentence(),
            'status' => StatusPengajuan::Pending,
            'tanggal_pengajuan' => now(),
            'lampiran' => null,
        ];
    }

    public function disetujui(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusPengajuan::Disetujui,
        ]);
    }

    public function ditolak(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusPengajuan::Ditolak,
        ]);
    }
}
