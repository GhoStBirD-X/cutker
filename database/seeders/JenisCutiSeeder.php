<?php

namespace Database\Seeders;

use App\Enums\JenisKelamin;
use App\Models\JenisCuti;
use Illuminate\Database\Seeder;

class JenisCutiSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            [
                'nama_jenis' => 'Cuti Tahunan',
                'kuota_default' => 12,
                'masa_kerja_minimal_bulan' => 12,
                'minimal_hari_pengajuan' => 7,
                'keterangan' => 'Cuti tahunan sesuai Pasal 79 UU No. 13/2003, diberikan setelah masa kerja 12 bulan terus-menerus.',
            ],
            [
                'nama_jenis' => 'Cuti Besar',
                'kuota_default' => 21,
                'masa_kerja_minimal_bulan' => 60,
                'keterangan' => 'Cuti besar/panjang, diberikan setelah masa kerja 5 tahun terus-menerus.',
            ],
            [
                'nama_jenis' => 'Cuti Haid',
                'kuota_default' => 24,
                'masa_kerja_minimal_bulan' => null,
                'khusus_gender' => JenisKelamin::Perempuan,
                'keterangan' => 'Sesuai Pasal 81 UU No. 13/2003: 2 hari setiap bulan pada hari pertama dan kedua masa haid, tidak mengurangi cuti tahunan.',
            ],
            [
                'nama_jenis' => 'Cuti Hamil',
                'kuota_default' => 90,
                'masa_kerja_minimal_bulan' => null,
                'khusus_gender' => JenisKelamin::Perempuan,
                'keterangan' => 'Sesuai Pasal 82 UU No. 13/2003: 1,5 bulan sebelum dan 1,5 bulan sesudah melahirkan, termasuk keguguran kandungan.',
            ],
            [
                'nama_jenis' => 'Cuti Menunaikan Ibadah Haji',
                'kuota_default' => null,
                'masa_kerja_minimal_bulan' => null,
                'keterangan' => 'Cuti untuk menunaikan ibadah haji, berlangsung hingga selesai sesuai jadwal keberangkatan.',
            ],
            [
                'nama_jenis' => 'Cuti Lain-lain',
                'kuota_default' => null,
                'masa_kerja_minimal_bulan' => null,
                'keterangan' => 'Cuti karena alasan penting; jumlah hari mengikuti alasan yang dipilih.',
            ],
        ])->each(fn (array $data) => JenisCuti::query()->firstOrCreate(['nama_jenis' => $data['nama_jenis']], $data));
    }
}
