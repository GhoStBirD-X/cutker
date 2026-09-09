<?php

namespace Tests\Feature\Master;

use App\Enums\StatusKaryawan;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Services\SaldoCutiService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class JenisCutiManagementTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_hrd_can_create_jenis_cuti_with_unlimited_kuota_and_masa_kerja_syarat(): void
    {
        $hrd = $this->karyawanUser('hrd');

        $response = $this->actingAs($hrd)->post(route('master.jenis-cuti.store'), [
            'nama_jenis' => 'Cuti Menunaikan Ibadah Haji',
            'kuota_default' => null,
            'masa_kerja_minimal_bulan' => null,
            'keterangan' => 'Hingga selesai',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('jenis_cutis', [
            'nama_jenis' => 'Cuti Menunaikan Ibadah Haji',
            'kuota_default' => null,
        ]);
    }

    public function test_hrd_can_create_jenis_cuti_with_masa_kerja_minimal_bulan(): void
    {
        $hrd = $this->karyawanUser('hrd');

        $response = $this->actingAs($hrd)->post(route('master.jenis-cuti.store'), [
            'nama_jenis' => 'Cuti Besar',
            'kuota_default' => 21,
            'masa_kerja_minimal_bulan' => 60,
            'keterangan' => null,
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('jenis_cutis', [
            'nama_jenis' => 'Cuti Besar',
            'kuota_default' => 21,
            'masa_kerja_minimal_bulan' => 60,
        ]);
    }

    public function test_creating_jenis_cuti_immediately_generates_saldo_for_active_karyawan_only(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawanAktif = Karyawan::factory()->create(['status' => StatusKaryawan::Aktif]);
        $karyawanNonaktif = Karyawan::factory()->create(['status' => StatusKaryawan::Nonaktif]);

        $response = $this->actingAs($hrd)->post(route('master.jenis-cuti.store'), [
            'nama_jenis' => 'Cuti Bencana',
            'kuota_default' => null,
            'masa_kerja_minimal_bulan' => null,
            'keterangan' => 'Hingga masalah selesai',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $jenisCuti = JenisCuti::query()->where('nama_jenis', 'Cuti Bencana')->firstOrFail();

        $this->assertDatabaseHas('saldo_cutis', [
            'karyawan_id' => $karyawanAktif->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'tahun' => now()->year,
            'kuota' => null,
            'sisa' => null,
        ]);
        $this->assertDatabaseMissing('saldo_cutis', [
            'karyawan_id' => $karyawanNonaktif->id,
            'jenis_cuti_id' => $jenisCuti->id,
        ]);
    }

    public function test_creating_jenis_cuti_twice_does_not_duplicate_saldo_for_the_same_year(): void
    {
        Karyawan::factory()->create(['status' => StatusKaryawan::Aktif]);
        $jenisCuti = JenisCuti::factory()->create(['kuota_default' => 5]);

        $saldoCutiService = $this->app->make(SaldoCutiService::class);
        $saldoCutiService->generateUntukJenisCuti($jenisCuti, now()->year);
        $dibuatKeduaKali = $saldoCutiService->generateUntukJenisCuti($jenisCuti, now()->year);

        $this->assertSame(0, $dibuatKeduaKali);
        $this->assertDatabaseCount('saldo_cutis', 1);
    }

    public function test_hrd_can_mark_jenis_cuti_as_khusus_gender(): void
    {
        $hrd = $this->karyawanUser('hrd');

        $response = $this->actingAs($hrd)->post(route('master.jenis-cuti.store'), [
            'nama_jenis' => 'Cuti Haid',
            'kuota_default' => 24,
            'masa_kerja_minimal_bulan' => null,
            'khusus_gender' => 'perempuan',
            'keterangan' => null,
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('jenis_cutis', [
            'nama_jenis' => 'Cuti Haid',
            'khusus_gender' => 'perempuan',
        ]);
    }

    public function test_karyawan_cannot_access_master_jenis_cuti_page(): void
    {
        $karyawan = $this->karyawanUser('karyawan');

        $response = $this->actingAs($karyawan)->get(route('master.jenis-cuti.index'));

        $response->assertForbidden();
    }

    public function test_jenis_cuti_already_used_in_a_pengajuan_cannot_be_deleted(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $jenisCuti = JenisCuti::factory()->create();
        PengajuanCuti::factory()->create(['jenis_cuti_id' => $jenisCuti->id]);

        $response = $this->actingAs($hrd)->delete(route('master.jenis-cuti.destroy', $jenisCuti));

        $response->assertRedirect();
        $this->assertDatabaseHas('jenis_cutis', ['id' => $jenisCuti->id]);
    }
}
