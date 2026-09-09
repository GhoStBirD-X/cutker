<?php

namespace Database\Seeders;

use App\Models\Departemen;
use Illuminate\Database\Seeder;

class DepartemenSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['nama_departemen' => 'Produksi', 'kode' => 'PRD'],
            ['nama_departemen' => 'Gudang', 'kode' => 'GDG'],
            ['nama_departemen' => 'Quality Control', 'kode' => 'QC'],
            ['nama_departemen' => 'Cleaning Service', 'kode' => 'CLS'],
        ])->each(fn (array $data) => Departemen::query()->firstOrCreate(['kode' => $data['kode']], $data));
    }
}
