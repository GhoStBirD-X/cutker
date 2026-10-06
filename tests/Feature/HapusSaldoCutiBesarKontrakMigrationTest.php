<?php

namespace Tests\Feature;

use App\Enums\StatusKonfirmasiKontrak;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\KonfirmasiKontrakCuti;
use App\Models\SaldoCuti;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HapusSaldoCutiBesarKontrakMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_removes_unused_cuti_besar_saldo_of_kontrak_karyawan_only(): void
    {
        $cutiBesar = JenisCuti::factory()->create(['nama_jenis' => JenisCuti::NAMA_CUTI_BESAR, 'masa_kerja_minimal_bulan' => 60]);
        $cutiTahunan = JenisCuti::factory()->create(['nama_jenis' => JenisCuti::NAMA_CUTI_TAHUNAN, 'masa_kerja_minimal_bulan' => 12]);
        $kontrak = Karyawan::factory()->kontrak()->create();
        $tetap = Karyawan::factory()->create();

        $besarKontrakBelumDipakai = SaldoCuti::factory()->create(['karyawan_id' => $kontrak->id, 'jenis_cuti_id' => $cutiBesar->id, 'periode_ke' => 1, 'terpakai' => 0]);
        $konfirmasi = KonfirmasiKontrakCuti::query()->create([
            'karyawan_id' => $kontrak->id,
            'saldo_cuti_id' => $besarKontrakBelumDipakai->id,
            'periode_ke' => 1,
            'tanggal_batas' => now(),
            'status' => StatusKonfirmasiKontrak::Menunggu,
        ]);
        $besarKontrakSudahDipakai = SaldoCuti::factory()->create(['karyawan_id' => $kontrak->id, 'jenis_cuti_id' => $cutiBesar->id, 'periode_ke' => 2, 'terpakai' => 3]);
        $tahunanKontrak = SaldoCuti::factory()->create(['karyawan_id' => $kontrak->id, 'jenis_cuti_id' => $cutiTahunan->id, 'periode_ke' => 1, 'terpakai' => 0]);
        $besarTetap = SaldoCuti::factory()->create(['karyawan_id' => $tetap->id, 'jenis_cuti_id' => $cutiBesar->id, 'periode_ke' => 1, 'terpakai' => 0]);

        (require database_path('migrations/2026_10_06_175043_hapus_saldo_cuti_besar_karyawan_kontrak.php'))->up();

        $this->assertModelMissing($besarKontrakBelumDipakai);
        $this->assertModelMissing($konfirmasi);
        $this->assertModelExists($besarKontrakSudahDipakai);
        $this->assertModelExists($tahunanKontrak);
        $this->assertModelExists($besarTetap);
    }
}
