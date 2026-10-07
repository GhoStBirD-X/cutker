<?php

namespace Tests\Feature\Master;

use App\Enums\StatusKompensasiCuti;
use App\Enums\StatusKonfirmasiKontrak;
use App\Models\Departemen;
use App\Models\Jabatan;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\KompensasiCuti;
use App\Models\KonfirmasiKontrakCuti;
use App\Models\RiwayatSaldoCuti;
use App\Models\SaldoCuti;
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

    public function test_index_is_sorted_by_npk_by_default(): void
    {
        $hrd = $this->karyawanUser('hrd');
        Karyawan::factory()->create(['nama' => 'Andi', 'nip' => 'NPK-900']);
        Karyawan::factory()->create(['nama' => 'Zaki', 'nip' => 'NPK-100']);

        $response = $this->actingAs($hrd)->get(route('master.karyawan.index', ['search' => 'NPK-']));

        $response->assertInertia(fn ($page) => $page
            ->where('karyawans.data.0.nama', 'Zaki')
            ->where('karyawans.data.1.nama', 'Andi'));
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

    public function test_kontrak_karyawan_promoted_to_tetap_starts_cuti_besar_from_promotion_date(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $cutiBesar = JenisCuti::factory()->create(['nama_jenis' => JenisCuti::NAMA_CUTI_BESAR, 'masa_kerja_minimal_bulan' => 60]);
        $karyawan = Karyawan::factory()->kontrak()->create(['tanggal_masuk' => now()->subYears(2)]);

        $response = $this->actingAs($hrd)->put(route('master.karyawan.update', $karyawan), [
            'nip' => $karyawan->nip,
            'nama' => $karyawan->nama,
            'email' => $karyawan->email,
            'no_hp' => $karyawan->no_hp,
            'jenis_kelamin' => $karyawan->jenis_kelamin->value,
            'departemen_id' => $karyawan->departemen_id,
            'jabatan_id' => $karyawan->jabatan_id,
            'tanggal_masuk' => $karyawan->tanggal_masuk->toDateString(),
            'status' => 'aktif',
            'tipe_karyawan' => 'tetap',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $saldoCutiBesar = SaldoCuti::query()->where('karyawan_id', $karyawan->id)->where('jenis_cuti_id', $cutiBesar->id)->firstOrFail();
        $this->assertSame(1, $saldoCutiBesar->periode_ke);
        $this->assertSame(today()->toDateString(), $saldoCutiBesar->periode_mulai->toDateString());
        $this->assertSame(0, $saldoCutiBesar->kuota);
    }

    /**
     * @return array<string, mixed>
     */
    private function dataEditKaryawan(Karyawan $karyawan, array $perubahan): array
    {
        return [
            'nip' => $karyawan->nip,
            'nama' => $karyawan->nama,
            'email' => $karyawan->email,
            'no_hp' => $karyawan->no_hp,
            'jenis_kelamin' => $karyawan->jenis_kelamin->value,
            'departemen_id' => $karyawan->departemen_id,
            'jabatan_id' => $karyawan->jabatan_id,
            'tanggal_masuk' => $karyawan->tanggal_masuk->toDateString(),
            'status' => 'aktif',
            'tipe_karyawan' => $karyawan->tipe_karyawan->value,
            'tanggal_akhir_kontrak' => $karyawan->tanggal_akhir_kontrak?->toDateString(),
            ...$perubahan,
        ];
    }

    public function test_koreksi_tanggal_masuk_memindahkan_periode_berjalan_ke_posisi_yang_benar_dan_menghapus_riwayat_lama(): void
    {
        $this->travelTo('2026-10-07');
        $hrd = $this->karyawanUser('hrd');
        $cutiTahunan = JenisCuti::factory()->create(['nama_jenis' => JenisCuti::NAMA_CUTI_TAHUNAN, 'kuota_default' => 12, 'masa_kerja_minimal_bulan' => 12]);
        $karyawan = Karyawan::factory()->kontrak()->create(['tanggal_masuk' => '2021-03-26', 'tanggal_akhir_kontrak' => '2027-03-25']);
        $saldoK5 = SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id, 'jenis_cuti_id' => $cutiTahunan->id, 'periode_ke' => 5,
            'periode_mulai' => '2025-03-26', 'periode_selesai' => '2026-03-25', 'kuota' => 12, 'terpakai' => 10, 'sisa' => 2, 'ditutup_pada' => '2026-03-26',
        ]);
        $riwayatK5 = RiwayatSaldoCuti::factory()->create(['karyawan_id' => $karyawan->id, 'jenis_cuti_id' => $cutiTahunan->id, 'periode_ke' => 5]);
        KonfirmasiKontrakCuti::factory()->create(['karyawan_id' => $karyawan->id, 'saldo_cuti_id' => $saldoK5->id, 'periode_ke' => 5, 'status' => StatusKonfirmasiKontrak::Diperpanjang]);
        KompensasiCuti::factory()->create(['karyawan_id' => $karyawan->id, 'jenis_cuti_id' => $cutiTahunan->id, 'riwayat_saldo_cuti_id' => $riwayatK5->id, 'jumlah_hari' => 2, 'status' => StatusKompensasiCuti::MenungguDiproses]);
        $kompensasiDiproses = KompensasiCuti::factory()->create(['karyawan_id' => $karyawan->id, 'jenis_cuti_id' => $cutiTahunan->id, 'jumlah_hari' => 1, 'status' => StatusKompensasiCuti::Diproses]);
        $saldoK1 = SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id, 'jenis_cuti_id' => $cutiTahunan->id, 'periode_ke' => 6,
            'periode_mulai' => '2026-03-26', 'periode_selesai' => '2027-03-25', 'kuota' => 0, 'terpakai' => 2, 'sisa' => -2,
        ]);

        $this->actingAs($hrd)
            ->put(route('master.karyawan.update', $karyawan), $this->dataEditKaryawan($karyawan, ['tanggal_masuk' => '2019-03-26']))
            ->assertSessionDoesntHaveErrors();

        $saldo = SaldoCuti::query()->where('karyawan_id', $karyawan->id)->where('jenis_cuti_id', $cutiTahunan->id)->sole();
        $this->assertSame($saldoK1->id, $saldo->id);
        $this->assertSame(8, $saldo->periode_ke);
        $this->assertSame(3, $saldo->urutanKontrak());
        $this->assertSame('2026-03-26', $saldo->periode_mulai->toDateString());
        $this->assertSame('2027-03-25', $saldo->periode_selesai->toDateString());
        $this->assertSame(12, $saldo->kuota);
        $this->assertSame(2, $saldo->terpakai);
        $this->assertSame(10, $saldo->sisa);
        $this->assertDatabaseCount('konfirmasi_kontrak_cutis', 0);
        $this->assertDatabaseCount('riwayat_saldo_cutis', 0);
        $this->assertSame([$kompensasiDiproses->id], KompensasiCuti::query()->pluck('id')->all());
    }

    public function test_edit_karyawan_tanpa_mengubah_tanggal_masuk_tidak_menghitung_ulang_periode(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $cutiTahunan = JenisCuti::factory()->create(['kuota_default' => 12, 'masa_kerja_minimal_bulan' => 12]);
        $karyawan = Karyawan::factory()->kontrak()->create(['tanggal_masuk' => now()->subYears(3)]);
        $saldo = SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id, 'jenis_cuti_id' => $cutiTahunan->id, 'periode_ke' => 2,
            'periode_mulai' => now()->subMonths(2), 'periode_selesai' => now()->addMonths(10), 'kuota' => 12, 'terpakai' => 0, 'sisa' => 12,
        ]);

        $this->actingAs($hrd)
            ->put(route('master.karyawan.update', $karyawan), $this->dataEditKaryawan($karyawan, ['nama' => 'Nama Baru']))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(2, $saldo->fresh()->periode_ke);
    }
}
