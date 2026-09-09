<?php

namespace Tests\Feature\Cuti;

use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\KompensasiCuti;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class KompensasiCutiTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_hrd_can_process_kompensasi_cuti_with_a_manual_rate(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = Karyawan::factory()->create();
        $jenisCuti = JenisCuti::factory()->create();
        $kompensasi = KompensasiCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'jumlah_hari' => 5,
        ]);

        $response = $this->actingAs($hrd)->post(route('cuti.kompensasi.proses', $kompensasi), [
            'rate_per_hari' => 150000,
            'catatan' => 'Dibayarkan bersama gaji September.',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('kompensasi_cutis', [
            'id' => $kompensasi->id,
            'rate_per_hari' => 150000,
            'total_rupiah' => 750000,
            'status' => 'diproses',
            'diproses_oleh_id' => $hrd->karyawan->id,
        ]);
    }

    public function test_kompensasi_already_processed_cannot_be_processed_again(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $kompensasi = KompensasiCuti::factory()->diproses()->create();

        $response = $this->actingAs($hrd)->post(route('cuti.kompensasi.proses', $kompensasi), [
            'rate_per_hari' => 200000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('kompensasi_cutis', ['id' => $kompensasi->id, 'rate_per_hari' => $kompensasi->rate_per_hari]);
    }

    public function test_karyawan_cannot_access_kompensasi_cuti_page(): void
    {
        $karyawan = $this->karyawanUser('karyawan');

        $response = $this->actingAs($karyawan)->get(route('cuti.kompensasi.index'));

        $response->assertForbidden();
    }
}
