<?php

namespace Tests\Feature\Cuti;

use App\Enums\JenisKelamin;
use App\Models\AlasanCuti;
use App\Models\HariLibur;
use App\Models\JadwalShift;
use App\Models\JenisCuti;
use App\Models\PengajuanCuti;
use App\Models\SaldoCuti;
use App\Models\Shift;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class PengajuanCutiTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guest_is_redirected_to_login_when_submitting_leave_request(): void
    {
        $response = $this->post(route('cuti.store'), []);

        $response->assertRedirect(route('login'));
    }

    public function test_karyawan_can_submit_leave_request_when_balance_is_sufficient(): void
    {
        $karyawan = $this->karyawanUser('karyawan')->karyawan;
        $this->karyawanUser('kepala_bagian', ['departemen_id' => $karyawan->departemen_id]);

        $jenisCuti = JenisCuti::factory()->create();
        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'tahun' => now()->year,
            'kuota' => 12,
            'terpakai' => 0,
            'sisa' => 12,
        ]);

        $response = $this->actingAs($karyawan->user)->post(route('cuti.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(7)->toDateString(),
            'tanggal_selesai' => now()->addDays(8)->toDateString(),
            'alasan' => 'Acara keluarga',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('pengajuan_cutis', [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'jumlah_hari' => 2,
            'status' => 'pending',
        ]);

        $pengajuan = PengajuanCuti::query()->where('karyawan_id', $karyawan->id)->firstOrFail();

        $this->assertDatabaseHas('approvals', [
            'pengajuan_cuti_id' => $pengajuan->id,
            'level' => 1,
            'status' => 'pending',
        ]);
    }

    public function test_leave_request_is_rejected_when_balance_is_insufficient(): void
    {
        $karyawan = $this->karyawanUser('karyawan')->karyawan;
        $this->karyawanUser('kepala_bagian', ['departemen_id' => $karyawan->departemen_id]);

        $jenisCuti = JenisCuti::factory()->create();
        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'tahun' => now()->year,
            'kuota' => 12,
            'terpakai' => 11,
            'sisa' => 1,
        ]);

        $response = $this->actingAs($karyawan->user)->post(route('cuti.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(7)->toDateString(),
            'tanggal_selesai' => now()->addDays(9)->toDateString(),
            'alasan' => 'Liburan',
        ]);

        $response->assertSessionHasErrors('tanggal_selesai');
        $this->assertDatabaseCount('pengajuan_cutis', 0);
    }

    public function test_leave_request_is_rejected_when_it_overlaps_an_active_request(): void
    {
        $karyawan = $this->karyawanUser('karyawan')->karyawan;
        $this->karyawanUser('kepala_bagian', ['departemen_id' => $karyawan->departemen_id]);

        $jenisCuti = JenisCuti::factory()->create();
        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'tahun' => now()->year,
            'kuota' => 12,
            'terpakai' => 0,
            'sisa' => 12,
        ]);

        PengajuanCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(5),
            'tanggal_selesai' => now()->addDays(10),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($karyawan->user)->post(route('cuti.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(7)->toDateString(),
            'tanggal_selesai' => now()->addDays(9)->toDateString(),
            'alasan' => 'Acara lain',
        ]);

        $response->assertSessionHasErrors('tanggal_selesai');
        $this->assertDatabaseCount('pengajuan_cutis', 1);
    }

    public function test_leave_request_is_rejected_when_masa_kerja_is_not_enough(): void
    {
        $karyawan = $this->karyawanUser('karyawan', ['tanggal_masuk' => now()->subMonths(3)])->karyawan;
        $this->karyawanUser('kepala_bagian', ['departemen_id' => $karyawan->departemen_id]);

        $jenisCuti = JenisCuti::factory()->create(['masa_kerja_minimal_bulan' => 12]);
        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'tahun' => now()->year,
            'kuota' => 12,
            'terpakai' => 0,
            'sisa' => 12,
        ]);

        $response = $this->actingAs($karyawan->user)->post(route('cuti.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(7)->toDateString(),
            'tanggal_selesai' => now()->addDays(8)->toDateString(),
            'alasan' => 'Liburan',
        ]);

        $response->assertSessionHasErrors('jenis_cuti_id');
        $this->assertDatabaseCount('pengajuan_cutis', 0);
    }

    public function test_leave_request_is_accepted_regardless_of_days_when_saldo_is_unlimited(): void
    {
        $karyawan = $this->karyawanUser('karyawan')->karyawan;
        $this->karyawanUser('kepala_bagian', ['departemen_id' => $karyawan->departemen_id]);

        $jenisCuti = JenisCuti::factory()->create(['kuota_default' => null]);
        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'tahun' => now()->year,
            'kuota' => null,
            'terpakai' => 0,
            'sisa' => null,
        ]);

        $response = $this->actingAs($karyawan->user)->post(route('cuti.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(7)->toDateString(),
            'tanggal_selesai' => now()->addDays(37)->toDateString(),
            'alasan' => 'Bencana alam',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('pengajuan_cutis', [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'jumlah_hari' => 31,
            'status' => 'pending',
        ]);
    }

    public function test_alasan_cuti_id_is_required_when_jenis_cuti_has_sub_reasons_and_caps_jumlah_hari(): void
    {
        $karyawan = $this->karyawanUser('karyawan')->karyawan;
        $this->karyawanUser('kepala_bagian', ['departemen_id' => $karyawan->departemen_id]);

        $jenisCuti = JenisCuti::factory()->create();
        $alasanCuti = AlasanCuti::factory()->create([
            'jenis_cuti_id' => $jenisCuti->id,
            'nama_alasan' => 'Pekerja yang bersangkutan menikah',
            'jumlah_hari' => 3,
        ]);
        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'tahun' => now()->year,
            'kuota' => 12,
            'terpakai' => 0,
            'sisa' => 12,
        ]);

        // Tanpa alasan_cuti_id -> ditolak karena wajib diisi.
        $response = $this->actingAs($karyawan->user)->post(route('cuti.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(7)->toDateString(),
            'tanggal_selesai' => now()->addDays(9)->toDateString(),
            'alasan' => 'Menikah',
        ]);
        $response->assertSessionHasErrors('alasan_cuti_id');

        // Melebihi jumlah_hari maksimal alasan -> ditolak.
        $response = $this->actingAs($karyawan->user)->post(route('cuti.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'alasan_cuti_id' => $alasanCuti->id,
            'tanggal_mulai' => now()->addDays(7)->toDateString(),
            'tanggal_selesai' => now()->addDays(11)->toDateString(),
            'alasan' => 'Menikah',
        ]);
        $response->assertSessionHasErrors('tanggal_selesai');

        // Sesuai jumlah_hari maksimal alasan -> diterima.
        $response = $this->actingAs($karyawan->user)->post(route('cuti.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'alasan_cuti_id' => $alasanCuti->id,
            'tanggal_mulai' => now()->addDays(7)->toDateString(),
            'tanggal_selesai' => now()->addDays(9)->toDateString(),
            'alasan' => 'Menikah',
        ]);
        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('pengajuan_cutis', [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'alasan_cuti_id' => $alasanCuti->id,
            'jumlah_hari' => 3,
        ]);
    }

    public function test_jumlah_hari_is_reduced_by_overlapping_hari_libur(): void
    {
        $karyawan = $this->karyawanUser('karyawan')->karyawan;
        $this->karyawanUser('kepala_bagian', ['departemen_id' => $karyawan->departemen_id]);

        $jenisCuti = JenisCuti::factory()->create();
        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'tahun' => now()->year,
            'kuota' => 12,
            'terpakai' => 0,
            'sisa' => 12,
        ]);

        HariLibur::factory()->create(['tanggal' => now()->addDays(8)->toDateString()]);

        $response = $this->actingAs($karyawan->user)->post(route('cuti.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(7)->toDateString(),
            'tanggal_selesai' => now()->addDays(9)->toDateString(),
            'alasan' => 'Liburan',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('pengajuan_cutis', [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'jumlah_hari' => 2,
            'jumlah_hari_kalender' => 3,
        ]);
    }

    public function test_male_karyawan_cannot_submit_gender_restricted_jenis_cuti(): void
    {
        $karyawan = $this->karyawanUser('karyawan', ['jenis_kelamin' => JenisKelamin::LakiLaki])->karyawan;
        $this->karyawanUser('kepala_bagian', ['departemen_id' => $karyawan->departemen_id]);

        $jenisCuti = JenisCuti::factory()->create(['khusus_gender' => JenisKelamin::Perempuan]);
        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'tahun' => now()->year,
            'kuota' => 12,
            'terpakai' => 0,
            'sisa' => 12,
        ]);

        $response = $this->actingAs($karyawan->user)->post(route('cuti.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(7)->toDateString(),
            'tanggal_selesai' => now()->addDays(8)->toDateString(),
            'alasan' => 'Cuti',
        ]);

        $response->assertSessionHasErrors('jenis_cuti_id');
        $this->assertDatabaseCount('pengajuan_cutis', 0);
    }

    public function test_leave_request_is_rejected_when_it_conflicts_with_shift_schedule(): void
    {
        $karyawan = $this->karyawanUser('karyawan')->karyawan;
        $this->karyawanUser('kepala_bagian', ['departemen_id' => $karyawan->departemen_id]);

        $jenisCuti = JenisCuti::factory()->create();
        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'tahun' => now()->year,
            'kuota' => 12,
            'terpakai' => 0,
            'sisa' => 12,
        ]);

        JadwalShift::factory()->create([
            'karyawan_id' => $karyawan->id,
            'shift_id' => Shift::factory(),
            'tanggal' => now()->addDays(7),
        ]);

        $response = $this->actingAs($karyawan->user)->post(route('cuti.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(7)->toDateString(),
            'tanggal_selesai' => now()->addDays(8)->toDateString(),
            'alasan' => 'Acara lain',
        ]);

        $response->assertSessionHasErrors('tanggal_selesai');
        $this->assertDatabaseCount('pengajuan_cutis', 0);
    }
}
