<?php

namespace Tests\Feature\Master;

use App\Models\Departemen;
use App\Models\Jabatan;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class KaryawanManagementTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_hrd_can_create_karyawan_assigned_to_a_departemen(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $departemen = Departemen::factory()->create();
        $jabatan = Jabatan::factory()->create();

        $response = $this->actingAs($hrd)->post(route('master.karyawan.store'), [
            'nip' => 'EMP-99999',
            'nama' => 'Karyawan Baru',
            'email' => 'karyawan.baru@pabrik.test',
            'no_hp' => '081234567890',
            'jenis_kelamin' => 'laki_laki',
            'departemen_id' => $departemen->id,
            'jabatan_id' => $jabatan->id,
            'tanggal_masuk' => now()->toDateString(),
            'status' => 'aktif',
            'tipe_karyawan' => 'tetap',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('karyawans', [
            'email' => 'karyawan.baru@pabrik.test',
            'departemen_id' => $departemen->id,
        ]);
    }

    public function test_tanggal_akhir_kontrak_is_required_when_tipe_karyawan_is_kontrak(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $departemen = Departemen::factory()->create();
        $jabatan = Jabatan::factory()->create();

        $response = $this->actingAs($hrd)->post(route('master.karyawan.store'), [
            'nip' => 'EMP-99998',
            'nama' => 'Karyawan Kontrak',
            'email' => 'karyawan.kontrak@pabrik.test',
            'jenis_kelamin' => 'perempuan',
            'departemen_id' => $departemen->id,
            'jabatan_id' => $jabatan->id,
            'tanggal_masuk' => now()->toDateString(),
            'status' => 'aktif',
            'tipe_karyawan' => 'kontrak',
        ]);

        $response->assertSessionHasErrors('tanggal_akhir_kontrak');
        $this->assertDatabaseMissing('karyawans', ['email' => 'karyawan.kontrak@pabrik.test']);
    }

    public function test_karyawan_cannot_access_master_karyawan_page(): void
    {
        $karyawan = $this->karyawanUser('karyawan');

        $response = $this->actingAs($karyawan)->get(route('master.karyawan.index'));

        $response->assertForbidden();
    }

    public function test_karyawan_with_linked_user_account_cannot_be_deleted(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawanDenganAkun = $this->karyawanUser('karyawan');

        $response = $this->actingAs($hrd)->delete(route('master.karyawan.destroy', $karyawanDenganAkun->karyawan));

        $response->assertRedirect();
        $this->assertDatabaseHas('karyawans', ['id' => $karyawanDenganAkun->karyawan->id]);
    }
}
