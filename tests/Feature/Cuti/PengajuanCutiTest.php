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
use Illuminate\Support\Carbon;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class PengajuanCutiTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        // Dikunci ke hari Senin supaya semua rentang now()->addDays(...) di
        // test ini jatuh pada hari kerja yang bisa diprediksi (kecuali test
        // yang memang sengaja menguji akhir pekan/hari libur).
        $this->travelTo(Carbon::parse('2026-10-05'));
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
            'jumlah_hari' => 23,
            'jumlah_hari_kalender' => 31,
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

    public function test_jumlah_hari_excludes_saturday_and_sunday(): void
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

        // Kamis s/d Minggu: 4 hari kalender, 2 di antaranya akhir pekan.
        $response = $this->actingAs($karyawan->user)->post(route('cuti.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(13)->toDateString(),
            'alasan' => 'Liburan',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('pengajuan_cutis', [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'jumlah_hari' => 2,
            'jumlah_hari_kalender' => 4,
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

    public function test_karyawan_can_cancel_own_pending_leave_request_and_its_pending_approval_is_cancelled_too(): void
    {
        $karyawan = $this->karyawanUser('karyawan')->karyawan;
        $kepalaBagian = $this->karyawanUser('kepala_bagian', ['departemen_id' => $karyawan->departemen_id])->karyawan;

        $jenisCuti = JenisCuti::factory()->create();
        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'tahun' => now()->year,
            'kuota' => 12,
            'terpakai' => 0,
            'sisa' => 12,
        ]);

        $this->actingAs($karyawan->user)->post(route('cuti.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(7)->toDateString(),
            'tanggal_selesai' => now()->addDays(8)->toDateString(),
            'alasan' => 'Acara keluarga',
        ]);

        $pengajuan = PengajuanCuti::query()->where('karyawan_id', $karyawan->id)->firstOrFail();

        $response = $this->actingAs($karyawan->user)->patch(route('cuti.batalkan', $pengajuan));

        $response->assertRedirect();

        $this->assertDatabaseHas('pengajuan_cutis', [
            'id' => $pengajuan->id,
            'status' => 'dibatalkan',
        ]);

        $this->assertDatabaseHas('approvals', [
            'pengajuan_cuti_id' => $pengajuan->id,
            'level' => 1,
            'status' => 'dibatalkan',
        ]);

        // Approver tidak lagi bisa memproses approval yang sudah dibatalkan.
        $approval = $pengajuan->approvals()->firstOrFail();
        $approveResponse = $this->actingAs($kepalaBagian->user)->post(route('approval.approve', $approval), [
            'catatan' => null,
        ]);
        $approveResponse->assertForbidden();
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

    public function test_leave_request_is_rejected_when_it_does_not_meet_the_minimum_notice_period(): void
    {
        $karyawan = $this->karyawanUser('karyawan')->karyawan;
        $this->karyawanUser('kepala_bagian', ['departemen_id' => $karyawan->departemen_id]);

        $jenisCuti = JenisCuti::factory()->create(['minimal_hari_pengajuan' => 7]);
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
            'tanggal_mulai' => now()->addDays(3)->toDateString(),
            'tanggal_selesai' => now()->addDays(4)->toDateString(),
            'alasan' => 'Liburan',
        ]);

        $response->assertSessionHasErrors('tanggal_mulai');
        $this->assertDatabaseCount('pengajuan_cutis', 0);
    }

    public function test_leave_request_is_accepted_when_it_meets_the_minimum_notice_period(): void
    {
        $karyawan = $this->karyawanUser('karyawan')->karyawan;
        $this->karyawanUser('kepala_bagian', ['departemen_id' => $karyawan->departemen_id]);

        $jenisCuti = JenisCuti::factory()->create(['minimal_hari_pengajuan' => 7]);
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

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseCount('pengajuan_cutis', 1);
    }

    public function test_mendadak_request_bypasses_minimum_notice_period_when_justified(): void
    {
        $karyawan = $this->karyawanUser('karyawan')->karyawan;
        $this->karyawanUser('kepala_bagian', ['departemen_id' => $karyawan->departemen_id]);

        $jenisCuti = JenisCuti::factory()->create(['minimal_hari_pengajuan' => 7]);
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
            'tanggal_mulai' => now()->addDay()->toDateString(),
            'tanggal_selesai' => now()->addDay()->toDateString(),
            'alasan' => 'Sakit mendadak',
            'mendadak' => true,
            'alasan_mendadak' => 'Orang tua masuk rumah sakit tiba-tiba.',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('pengajuan_cutis', [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'is_mendadak' => true,
            'alasan_mendadak' => 'Orang tua masuk rumah sakit tiba-tiba.',
        ]);
    }

    public function test_mendadak_request_requires_a_justification(): void
    {
        $karyawan = $this->karyawanUser('karyawan')->karyawan;
        $this->karyawanUser('kepala_bagian', ['departemen_id' => $karyawan->departemen_id]);

        $jenisCuti = JenisCuti::factory()->create(['minimal_hari_pengajuan' => 7]);
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
            'tanggal_mulai' => now()->addDay()->toDateString(),
            'tanggal_selesai' => now()->addDay()->toDateString(),
            'alasan' => 'Sakit mendadak',
            'mendadak' => true,
        ]);

        $response->assertSessionHasErrors('alasan_mendadak');
        $this->assertDatabaseCount('pengajuan_cutis', 0);
    }
}
