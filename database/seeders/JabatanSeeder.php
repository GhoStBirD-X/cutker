<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use Illuminate\Database\Seeder;

class JabatanSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            'Operator Produksi',
            'Staff Gudang',
            'Staff Quality Control',
            'Staff Cleaning Service',
            'Kepala Bagian',
            'Koordinator Shift',
            'Staff HRD',
            'Manager',
            'Administrator Sistem',
        ])->each(fn (string $nama) => Jabatan::query()->firstOrCreate(['nama_jabatan' => $nama]));
    }
}
