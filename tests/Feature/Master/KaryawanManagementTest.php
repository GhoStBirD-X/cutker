<?php

namespace Tests\Feature\Master;

use App\Models\Departemen;
use App\Models\Jabatan;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_index_respects_per_page_query_param_within_allowed_options(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $departemen = Departemen::factory()->create();
        $jabatan = Jabatan::factory()->create();
        Karyawan::factory()->count(25)->create(['departemen_id' => $departemen->id, 'jabatan_id' => $jabatan->id]);

        $response = $this->actingAs($hrd)->get(route('master.karyawan.index', ['per_page' => 20]));

        $response->assertInertia(fn ($page) => $page
            ->where('karyawans.per_page', 20)
            ->has('karyawans.data', 20)
        );
    }

    public function test_index_ignores_a_per_page_value_outside_allowed_options(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $departemen = Departemen::factory()->create();
        $jabatan = Jabatan::factory()->create();
        Karyawan::factory()->count(15)->create(['departemen_id' => $departemen->id, 'jabatan_id' => $jabatan->id]);

        $response = $this->actingAs($hrd)->get(route('master.karyawan.index', ['per_page' => 999999]));

        $response->assertInertia(fn ($page) => $page
            ->where('karyawans.per_page', 10)
        );
    }

    public function test_new_karyawan_starts_with_zero_saldo_for_jenis_cuti_bertipe_periode(): void
    {
        // Periode ke-1 (masa kerja minimal, mis. 12 bulan pertama) belum
        // memberi hak cuti apa pun -- kuota/sisa harus 0 sampai periode ini
        // ditutup dan lanjut ke periode ke-2 dengan kuota penuh.
        $hrd = $this->karyawanUser('hrd');
        $departemen = Departemen::factory()->create();
        $jabatan = Jabatan::factory()->create();
        $jenisCuti = JenisCuti::factory()->create(['kuota_default' => 12, 'masa_kerja_minimal_bulan' => 12]);

        $response = $this->actingAs($hrd)->post(route('master.karyawan.store'), [
            'nip' => 'EMP-88888',
            'nama' => 'Karyawan Baru Periode',
            'email' => 'karyawan.periode@pabrik.test',
            'no_hp' => '081234567891',
            'jenis_kelamin' => 'laki_laki',
            'departemen_id' => $departemen->id,
            'jabatan_id' => $jabatan->id,
            'tanggal_masuk' => now()->toDateString(),
            'status' => 'aktif',
            'tipe_karyawan' => 'tetap',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $karyawan = Karyawan::query()->where('email', 'karyawan.periode@pabrik.test')->firstOrFail();

        $this->assertDatabaseHas('saldo_cutis', [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'periode_ke' => 1,
            'kuota' => 0,
            'sisa' => 0,
        ]);
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

    public function test_hrd_can_create_karyawan_with_login_account_at_the_same_time(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $departemen = Departemen::factory()->create();
        $jabatan = Jabatan::factory()->create();

        $response = $this->actingAs($hrd)->post(route('master.karyawan.store'), [
            'nip' => 'EMP-99997',
            'nama' => 'Karyawan Dengan Akun',
            'email' => 'karyawan.akun@pabrik.test',
            'jenis_kelamin' => 'laki_laki',
            'departemen_id' => $departemen->id,
            'jabatan_id' => $jabatan->id,
            'tanggal_masuk' => now()->toDateString(),
            'status' => 'aktif',
            'tipe_karyawan' => 'tetap',
            'buat_akun' => true,
            'akun_password' => 'password-aman-123',
            'akun_role' => 'kepala_bagian',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $karyawan = Karyawan::query()->where('email', 'karyawan.akun@pabrik.test')->firstOrFail();
        $user = User::query()->where('email', 'karyawan.akun@pabrik.test')->firstOrFail();

        $this->assertSame($karyawan->id, $user->karyawan_id);
        $this->assertTrue($user->hasRole('kepala_bagian'));
        $this->assertTrue(Hash::check('password-aman-123', $user->password));
    }

    public function test_creating_karyawan_without_buat_akun_does_not_create_login_account(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $departemen = Departemen::factory()->create();
        $jabatan = Jabatan::factory()->create();

        $this->actingAs($hrd)->post(route('master.karyawan.store'), [
            'nip' => 'EMP-99996',
            'nama' => 'Karyawan Tanpa Akun',
            'email' => 'karyawan.tanpaakun@pabrik.test',
            'jenis_kelamin' => 'laki_laki',
            'departemen_id' => $departemen->id,
            'jabatan_id' => $jabatan->id,
            'tanggal_masuk' => now()->toDateString(),
            'status' => 'aktif',
            'tipe_karyawan' => 'tetap',
        ]);

        $this->assertDatabaseMissing('users', ['email' => 'karyawan.tanpaakun@pabrik.test']);
    }

    public function test_buat_akun_requires_password_and_role(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $departemen = Departemen::factory()->create();
        $jabatan = Jabatan::factory()->create();

        $response = $this->actingAs($hrd)->post(route('master.karyawan.store'), [
            'nip' => 'EMP-99995',
            'nama' => 'Karyawan Gagal Akun',
            'email' => 'karyawan.gagalakun@pabrik.test',
            'jenis_kelamin' => 'laki_laki',
            'departemen_id' => $departemen->id,
            'jabatan_id' => $jabatan->id,
            'tanggal_masuk' => now()->toDateString(),
            'status' => 'aktif',
            'tipe_karyawan' => 'tetap',
            'buat_akun' => true,
        ]);

        $response->assertSessionHasErrors(['akun_password', 'akun_role']);
        $this->assertDatabaseMissing('karyawans', ['email' => 'karyawan.gagalakun@pabrik.test']);
    }

    public function test_buat_akun_rejects_email_already_used_by_existing_user_account(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $departemen = Departemen::factory()->create();
        $jabatan = Jabatan::factory()->create();
        User::factory()->create(['email' => 'sudah.dipakai@pabrik.test']);

        $response = $this->actingAs($hrd)->post(route('master.karyawan.store'), [
            'nip' => 'EMP-99994',
            'nama' => 'Karyawan Email Bentrok',
            'email' => 'sudah.dipakai@pabrik.test',
            'jenis_kelamin' => 'laki_laki',
            'departemen_id' => $departemen->id,
            'jabatan_id' => $jabatan->id,
            'tanggal_masuk' => now()->toDateString(),
            'status' => 'aktif',
            'tipe_karyawan' => 'tetap',
            'buat_akun' => true,
            'akun_password' => 'password-aman-123',
            'akun_role' => 'karyawan',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('karyawans', ['nip' => 'EMP-99994']);
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
