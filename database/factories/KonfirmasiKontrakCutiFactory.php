<?php

namespace Database\Factories;

use App\Enums\StatusKonfirmasiKontrak;
use App\Models\Karyawan;
use App\Models\KonfirmasiKontrakCuti;
use App\Models\SaldoCuti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KonfirmasiKontrakCuti>
 */
class KonfirmasiKontrakCutiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'karyawan_id' => Karyawan::factory(),
            'saldo_cuti_id' => SaldoCuti::factory(),
            'periode_ke' => 1,
            'tanggal_batas' => now()->subDay(),
            'status' => StatusKonfirmasiKontrak::Menunggu,
            'dikonfirmasi_oleh_id' => null,
            'dikonfirmasi_pada' => null,
            'catatan' => null,
        ];
    }
}
