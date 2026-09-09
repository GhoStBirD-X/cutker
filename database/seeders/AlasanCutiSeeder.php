<?php

namespace Database\Seeders;

use App\Models\AlasanCuti;
use App\Models\JenisCuti;
use Illuminate\Database\Seeder;

class AlasanCutiSeeder extends Seeder
{
    public function run(): void
    {
        $cutiLainLain = JenisCuti::query()->where('nama_jenis', 'Cuti Lain-lain')->firstOrFail();

        collect([
            ['nama_alasan' => 'Pekerja yang bersangkutan menikah', 'jumlah_hari' => 3, 'keterangan' => null],
            ['nama_alasan' => 'Anak pekerja menikah', 'jumlah_hari' => 2, 'keterangan' => null],
            ['nama_alasan' => 'Pembaptisan anak pekerja', 'jumlah_hari' => 2, 'keterangan' => null],
            ['nama_alasan' => 'Istri pekerja melahirkan atau keguguran kandungan', 'jumlah_hari' => 2, 'keterangan' => null],
            ['nama_alasan' => 'Anggota keluarga dalam satu rumah meninggal dunia', 'jumlah_hari' => 1, 'keterangan' => null],
            ['nama_alasan' => 'Bencana', 'jumlah_hari' => null, 'keterangan' => 'Berlangsung hingga masalah selesai.'],
            ['nama_alasan' => 'Menjalankan/menunaikan ibadah haji', 'jumlah_hari' => null, 'keterangan' => 'Berlangsung hingga masalah selesai.'],
            ['nama_alasan' => 'Memenuhi panggilan pengadilan atau pihak berwajib', 'jumlah_hari' => null, 'keterangan' => 'Berlangsung hingga masalah selesai.'],
        ])->each(fn (array $data) => AlasanCuti::query()->firstOrCreate(
            ['jenis_cuti_id' => $cutiLainLain->id, 'nama_alasan' => $data['nama_alasan']],
            [...$data, 'jenis_cuti_id' => $cutiLainLain->id],
        ));
    }
}
