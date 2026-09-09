<?php

namespace Database\Factories;

use App\Enums\JenisKelamin;
use App\Enums\StatusKaryawan;
use App\Enums\TipeKaryawan;
use App\Models\Departemen;
use App\Models\Jabatan;
use App\Models\Karyawan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Karyawan>
 */
class KaryawanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nip' => fake()->unique()->numerify('EMP-#####'),
            'nama' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'no_hp' => fake()->numerify('08##########'),
            'jenis_kelamin' => fake()->randomElement(JenisKelamin::cases()),
            'departemen_id' => Departemen::factory(),
            'jabatan_id' => Jabatan::factory(),
            'tanggal_masuk' => fake()->dateTimeBetween('-8 years', '-1 month'),
            'status' => StatusKaryawan::Aktif,
            'tipe_karyawan' => TipeKaryawan::Tetap,
            'tanggal_akhir_kontrak' => null,
        ];
    }

    public function nonaktif(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusKaryawan::Nonaktif,
        ]);
    }

    public function lakiLaki(): static
    {
        return $this->state(fn (array $attributes) => [
            'jenis_kelamin' => JenisKelamin::LakiLaki,
        ]);
    }

    public function perempuan(): static
    {
        return $this->state(fn (array $attributes) => [
            'jenis_kelamin' => JenisKelamin::Perempuan,
        ]);
    }

    public function kontrak(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipe_karyawan' => TipeKaryawan::Kontrak,
            'tanggal_akhir_kontrak' => fake()->dateTimeBetween('+1 month', '+1 year'),
        ]);
    }
}
