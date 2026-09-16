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

    public function test_hrd_can_process_many_kompensasi_cuti_at_once_with_a_shared_rate(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $jenisCuti = JenisCuti::factory()->create();
        $kompensasiSatu = KompensasiCuti::factory()->create([
            'jenis_cuti_id' => $jenisCuti->id,
            'jumlah_hari' => 3,
        ]);
        $kompensasiDua = KompensasiCuti::factory()->create([
            'jenis_cuti_id' => $jenisCuti->id,
            'jumlah_hari' => 4,
        ]);

        $response = $this->actingAs($hrd)->post(route('cuti.kompensasi.proses-massal'), [
            'kompensasi_cuti_ids' => [$kompensasiSatu->id, $kompensasiDua->id],
            'rate_per_hari' => 150000,
            'catatan' => 'Dibayarkan bersama gaji September.',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('kompensasi_cutis', [
            'id' => $kompensasiSatu->id,
            'rate_per_hari' => 150000,
            'total_rupiah' => 450000,
            'status' => 'diproses',
            'diproses_oleh_id' => $hrd->karyawan->id,
        ]);
        $this->assertDatabaseHas('kompensasi_cutis', [
            'id' => $kompensasiDua->id,
            'rate_per_hari' => 150000,
            'total_rupiah' => 600000,
            'status' => 'diproses',
            'diproses_oleh_id' => $hrd->karyawan->id,
        ]);
    }

    public function test_bulk_process_skips_kompensasi_that_is_already_processed(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $pending = KompensasiCuti::factory()->create(['jumlah_hari' => 2]);
        $sudahDiproses = KompensasiCuti::factory()->diproses()->create();

        $response = $this->actingAs($hrd)->post(route('cuti.kompensasi.proses-massal'), [
            'kompensasi_cuti_ids' => [$pending->id, $sudahDiproses->id],
            'rate_per_hari' => 100000,
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('kompensasi_cutis', [
            'id' => $pending->id,
            'status' => 'diproses',
            'total_rupiah' => 200000,
        ]);
        $this->assertDatabaseHas('kompensasi_cutis', [
            'id' => $sudahDiproses->id,
            'rate_per_hari' => $sudahDiproses->rate_per_hari,
        ]);
    }

    public function test_karyawan_cannot_access_kompensasi_cuti_page(): void
    {
        $karyawan = $this->karyawanUser('karyawan');

        $response = $this->actingAs($karyawan)->get(route('cuti.kompensasi.index'));

        $response->assertForbidden();
    }
}
