<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['nama_shift' => 'Pagi', 'jam_mulai' => '07:00', 'jam_selesai' => '15:30'],
            ['nama_shift' => 'Siang', 'jam_mulai' => '15:00', 'jam_selesai' => '23:30'],
            ['nama_shift' => 'Malam', 'jam_mulai' => '23:00', 'jam_selesai' => '07:30'],
        ])->each(fn (array $data) => Shift::query()->firstOrCreate(['nama_shift' => $data['nama_shift']], $data));
    }
}
