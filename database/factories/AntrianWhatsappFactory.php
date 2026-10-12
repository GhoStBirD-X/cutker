<?php

namespace Database\Factories;

use App\Enums\StatusAntrianWhatsapp;
use App\Models\AntrianWhatsapp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AntrianWhatsapp>
 */
class AntrianWhatsappFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nomor' => '62812'.fake()->numerify('########'),
            'pesan' => fake()->sentence(),
            'status' => StatusAntrianWhatsapp::Pending,
            'percobaan' => 1,
            'error_terakhir' => 'HTTP 500',
            'terkirim_pada' => null,
        ];
    }

    public function terkirim(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StatusAntrianWhatsapp::Terkirim,
            'error_terakhir' => null,
            'terkirim_pada' => now(),
        ]);
    }
}
