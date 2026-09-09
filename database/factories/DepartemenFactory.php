<?php

namespace Database\Factories;

use App\Models\Departemen;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Departemen>
 */
class DepartemenFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_departemen' => fake()->unique()->randomElement(['Produksi', 'Gudang', 'Quality Control', 'Maintenance', 'HRD']),
            'kode' => fake()->unique()->lexify('???'),
        ];
    }
}
