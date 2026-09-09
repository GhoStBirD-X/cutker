<?php

namespace Tests\Feature\Master;

use App\Models\AlasanCuti;
use App\Models\JenisCuti;
use App\Models\PengajuanCuti;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class AlasanCutiManagementTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_hrd_can_create_alasan_cuti_for_a_jenis_cuti(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $jenisCuti = JenisCuti::factory()->create();

        $response = $this->actingAs($hrd)->post(route('master.alasan-cuti.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'nama_alasan' => 'Pekerja yang bersangkutan menikah',
            'jumlah_hari' => 3,
            'keterangan' => null,
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('alasan_cutis', [
            'jenis_cuti_id' => $jenisCuti->id,
            'nama_alasan' => 'Pekerja yang bersangkutan menikah',
            'jumlah_hari' => 3,
        ]);
    }

    public function test_hrd_can_create_alasan_cuti_with_unlimited_days(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $jenisCuti = JenisCuti::factory()->create();

        $response = $this->actingAs($hrd)->post(route('master.alasan-cuti.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'nama_alasan' => 'Bencana',
            'jumlah_hari' => null,
            'keterangan' => 'Berlangsung hingga masalah selesai.',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('alasan_cutis', [
            'jenis_cuti_id' => $jenisCuti->id,
            'nama_alasan' => 'Bencana',
            'jumlah_hari' => null,
        ]);
    }

    public function test_karyawan_cannot_access_master_alasan_cuti_page(): void
    {
        $karyawan = $this->karyawanUser('karyawan');

        $response = $this->actingAs($karyawan)->get(route('master.alasan-cuti.index'));

        $response->assertForbidden();
    }

    public function test_alasan_cuti_already_used_in_a_pengajuan_cannot_be_deleted(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $alasanCuti = AlasanCuti::factory()->create();
        PengajuanCuti::factory()->create(['alasan_cuti_id' => $alasanCuti->id]);

        $response = $this->actingAs($hrd)->delete(route('master.alasan-cuti.destroy', $alasanCuti));

        $response->assertRedirect();
        $this->assertDatabaseHas('alasan_cutis', ['id' => $alasanCuti->id]);
    }
}
