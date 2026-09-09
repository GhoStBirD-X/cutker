<?php

namespace Tests\Feature\Master;

use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\SaldoCuti;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class SaldoCutiManagementTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_hrd_can_create_manual_saldo_for_a_karyawan_without_an_active_row(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = Karyawan::factory()->create();
        $jenisCuti = JenisCuti::factory()->create(['masa_kerja_minimal_bulan' => null]);

        $response = $this->actingAs($hrd)->post(route('master.saldo-cuti.store'), [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'tahun' => now()->year,
            'kuota' => 12,
            'terpakai' => 3,
            'sisa' => 9,
            'catatan' => 'Migrasi data dari sistem lama.',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('saldo_cutis', [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'kuota' => 12,
            'sisa' => 9,
            'diubah_oleh_id' => $hrd->karyawan->id,
        ]);
    }

    public function test_hrd_can_create_manual_periode_saldo_for_jenis_cuti_bertipe_periode(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = Karyawan::factory()->create();
        $jenisCuti = JenisCuti::factory()->create(['masa_kerja_minimal_bulan' => 12]);

        $response = $this->actingAs($hrd)->post(route('master.saldo-cuti.store'), [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'periode_ke' => 2,
            'periode_mulai' => now()->subMonths(3)->toDateString(),
            'periode_selesai' => now()->addMonths(9)->toDateString(),
            'kuota' => 12,
            'terpakai' => 1,
            'sisa' => 11,
            'catatan' => 'Migrasi periode berjalan dari sistem lama.',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('saldo_cutis', [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'periode_ke' => 2,
            'sisa' => 11,
        ]);
    }

    public function test_correcting_existing_saldo_requires_catatan(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $saldo = SaldoCuti::factory()->create(['sisa' => 5, 'kuota' => 12, 'terpakai' => 7]);

        $response = $this->actingAs($hrd)->put(route('master.saldo-cuti.update', $saldo), [
            'kuota' => 12,
            'terpakai' => 5,
            'sisa' => 7,
        ]);

        $response->assertSessionHasErrors('catatan');
        $this->assertDatabaseHas('saldo_cutis', ['id' => $saldo->id, 'sisa' => 5]);
    }

    public function test_hrd_can_correct_existing_saldo_with_catatan(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $saldo = SaldoCuti::factory()->create(['sisa' => 5, 'kuota' => 12, 'terpakai' => 7]);

        $response = $this->actingAs($hrd)->put(route('master.saldo-cuti.update', $saldo), [
            'kuota' => 12,
            'terpakai' => 5,
            'sisa' => 7,
            'catatan' => 'Koreksi salah input terpakai.',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('saldo_cutis', [
            'id' => $saldo->id,
            'sisa' => 7,
            'diubah_oleh_id' => $hrd->karyawan->id,
        ]);
    }

    public function test_karyawan_cannot_access_saldo_cuti_management(): void
    {
        $karyawan = $this->karyawanUser('karyawan');

        $response = $this->actingAs($karyawan)->get(route('master.saldo-cuti.index'));

        $response->assertForbidden();
    }
}
