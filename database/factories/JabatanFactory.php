<?php

namespace Database\Factories;

use App\Models\Jabatan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Jabatan>
 */
class JabatanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_jabatan' => fake()->unique()->jobTitle(),
        ];
    }
}
