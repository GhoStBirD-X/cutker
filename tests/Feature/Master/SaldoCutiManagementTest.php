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
        $karyawan = Karyawan::factory()->create(['tanggal_masuk' => '2023-01-10']);
        $jenisCuti = JenisCuti::factory()->create(['masa_kerja_minimal_bulan' => 12]);

        $response = $this->actingAs($hrd)->post(route('master.saldo-cuti.store'), [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'periode_ke' => 2,
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
            'periode_mulai' => '2024-01-10 00:00:00',
            'periode_selesai' => '2025-01-09 00:00:00',
        ]);
    }

    public function test_periode_ke_yang_sudah_tercatat_tidak_bisa_dipakai_lagi(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = Karyawan::factory()->create(['tanggal_masuk' => '2023-01-10']);
        $jenisCuti = JenisCuti::factory()->create(['masa_kerja_minimal_bulan' => 12]);
        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'periode_ke' => 1,
        ]);

        $response = $this->actingAs($hrd)->post(route('master.saldo-cuti.store'), [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'periode_ke' => 1,
            'kuota' => 12,
            'terpakai' => 0,
            'sisa' => 12,
            'catatan' => 'Salah pilih periode.',
        ]);

        $response->assertSessionHasErrors('periode_ke');
    }

    public function test_periode_tersedia_endpoint_mengembalikan_nomor_dan_tanggal_yang_belum_terpakai(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = Karyawan::factory()->create(['tanggal_masuk' => '2023-01-10']);
        $jenisCuti = JenisCuti::factory()->create(['masa_kerja_minimal_bulan' => 12]);
        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'periode_ke' => 1,
        ]);

        $response = $this->actingAs($hrd)->getJson(route('master.saldo-cuti.periode-tersedia', [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
        ]));

        $response->assertOk();
        $response->assertExactJson([
            'periodes' => [
                [
                    'periode_ke' => 2,
                    'periode_mulai' => '2024-01-10',
                    'periode_selesai' => '2025-01-09',
                ],
            ],
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

    public function test_hrd_can_delete_a_saldo_cuti_row_entered_by_mistake(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $saldo = SaldoCuti::factory()->create();

        $response = $this->actingAs($hrd)->delete(route('master.saldo-cuti.destroy', $saldo));

        $response->assertRedirect();
        $this->assertDatabaseMissing('saldo_cutis', ['id' => $saldo->id]);
    }

    public function test_karyawan_cannot_delete_saldo_cuti(): void
    {
        $karyawan = $this->karyawanUser('karyawan');
        $saldo = SaldoCuti::factory()->create();

        $response = $this->actingAs($karyawan)->delete(route('master.saldo-cuti.destroy', $saldo));

        $response->assertForbidden();
        $this->assertDatabaseHas('saldo_cutis', ['id' => $saldo->id]);
    }

    public function test_karyawan_cannot_access_saldo_cuti_management(): void
    {
        $karyawan = $this->karyawanUser('karyawan');

        $response = $this->actingAs($karyawan)->get(route('master.saldo-cuti.index'));

        $response->assertForbidden();
    }
}
