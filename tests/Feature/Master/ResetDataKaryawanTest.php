<?php

namespace Tests\Feature\Master;

use App\Http\Requests\Master\ResetDataKaryawanRequest;
use App\Models\CutiMassal;
use App\Models\Departemen;
use App\Models\Jabatan;
use App\Models\JadwalShift;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\SaldoCuti;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class ResetDataKaryawanTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function buatDataKaryawanLengkap(): Karyawan
    {
        $karyawan = Karyawan::factory()->create();
        $jenisCuti = JenisCuti::factory()->create();

        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
        ]);

        PengajuanCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
        ]);

        JadwalShift::factory()->create([
            'karyawan_id' => $karyawan->id,
            'shift_id' => Shift::factory(),
        ]);

        CutiMassal::factory()->create(['dibuat_oleh_id' => $karyawan->id]);

        return $karyawan;
    }

    public function test_admin_can_reset_all_karyawan_data_with_correct_confirmation(): void
    {
        $admin = $this->karyawanUser('admin');
        $karyawanLain = $this->buatDataKaryawanLengkap();
        $userLain = User::factory()->create(['karyawan_id' => $karyawanLain->id]);

        $response = $this->actingAs($admin)->post(route('master.karyawan.reset-data'), [
            'konfirmasi' => ResetDataKaryawanRequest::FRASA_KONFIRMASI,
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('karyawans', ['id' => $karyawanLain->id]);
        $this->assertDatabaseMissing('users', ['id' => $userLain->id]);
        $this->assertDatabaseCount('pengajuan_cutis', 0);
        $this->assertDatabaseCount('saldo_cutis', 0);
        $this->assertDatabaseCount('jadwal_shifts', 0);
        $this->assertDatabaseCount('cuti_massals', 0);
    }

    public function test_reset_preserves_acting_admins_own_karyawan_and_login(): void
    {
        $admin = $this->karyawanUser('admin');
        $karyawanLain = $this->buatDataKaryawanLengkap();

        $this->actingAs($admin)->post(route('master.karyawan.reset-data'), [
            'konfirmasi' => ResetDataKaryawanRequest::FRASA_KONFIRMASI,
        ]);

        $this->assertDatabaseHas('karyawans', ['id' => $admin->karyawan_id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseMissing('karyawans', ['id' => $karyawanLain->id]);
    }

    public function test_reset_does_not_touch_master_data(): void
    {
        $admin = $this->karyawanUser('admin');
        $this->buatDataKaryawanLengkap();
        $departemen = Departemen::factory()->create();
        $jabatan = Jabatan::factory()->create();

        $this->actingAs($admin)->post(route('master.karyawan.reset-data'), [
            'konfirmasi' => ResetDataKaryawanRequest::FRASA_KONFIRMASI,
        ]);

        $this->assertDatabaseHas('departemens', ['id' => $departemen->id]);
        $this->assertDatabaseHas('jabatans', ['id' => $jabatan->id]);
    }

    public function test_reset_is_rejected_when_confirmation_phrase_is_wrong(): void
    {
        $admin = $this->karyawanUser('admin');
        $karyawanLain = $this->buatDataKaryawanLengkap();

        $response = $this->actingAs($admin)->post(route('master.karyawan.reset-data'), [
            'konfirmasi' => 'hapus semua karyawan',
        ]);

        $response->assertSessionHasErrors('konfirmasi');
        $this->assertDatabaseHas('karyawans', ['id' => $karyawanLain->id]);
    }

    public function test_non_admin_cannot_reset_data(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawanLain = $this->buatDataKaryawanLengkap();

        $response = $this->actingAs($hrd)->post(route('master.karyawan.reset-data'), [
            'konfirmasi' => ResetDataKaryawanRequest::FRASA_KONFIRMASI,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('karyawans', ['id' => $karyawanLain->id]);
    }
}
