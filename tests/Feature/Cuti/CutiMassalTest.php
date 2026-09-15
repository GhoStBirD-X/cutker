<?php

namespace Tests\Feature\Cuti;

use App\Enums\JenisKelamin;
use App\Models\CutiMassal;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\SaldoCuti;
use App\Services\CutiMassalService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class CutiMassalTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        // Dikunci ke hari Sabtu supaya semua rentang now()->addDays(...) di
        // test ini jatuh pada hari kerja yang bisa diprediksi.
        $this->travelTo(Carbon::parse('2026-10-03'));
    }

    public function test_hrd_can_create_mass_leave_for_all_eligible_active_employees(): void
    {
        $hrd = $this->karyawanUser('hrd')->karyawan;
        $jenisCuti = JenisCuti::factory()->create();

        $karyawanA = Karyawan::factory()->create();
        $karyawanB = Karyawan::factory()->create();

        foreach ([$karyawanA, $karyawanB] as $karyawan) {
            SaldoCuti::factory()->create([
                'karyawan_id' => $karyawan->id,
                'jenis_cuti_id' => $jenisCuti->id,
                'kuota' => 12,
                'terpakai' => 0,
                'sisa' => 12,
            ]);
        }

        $response = $this->actingAs($hrd->user)->post(route('cuti.massal.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(11)->toDateString(),
            'alasan' => 'Cuti bersama Lebaran',
            'karyawan_ids' => [$karyawanA->id, $karyawanB->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $cutiMassal = CutiMassal::query()->firstOrFail();
        $this->assertSame(2, $cutiMassal->jumlah_karyawan);

        $this->assertDatabaseHas('pengajuan_cutis', [
            'karyawan_id' => $karyawanA->id,
            'cuti_massal_id' => $cutiMassal->id,
            'status' => 'disetujui',
        ]);
        $this->assertDatabaseHas('pengajuan_cutis', [
            'karyawan_id' => $karyawanB->id,
            'cuti_massal_id' => $cutiMassal->id,
            'status' => 'disetujui',
        ]);
    }

    public function test_balance_is_deducted_correctly_for_employees_with_sufficient_balance(): void
    {
        $hrd = $this->karyawanUser('hrd')->karyawan;
        $jenisCuti = JenisCuti::factory()->create();
        $karyawan = Karyawan::factory()->create();

        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'kuota' => 12,
            'terpakai' => 0,
            'sisa' => 12,
        ]);

        $this->actingAs($hrd->user)->post(route('cuti.massal.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(11)->toDateString(),
            'alasan' => 'Cuti bersama',
            'karyawan_ids' => [$karyawan->id],
        ]);

        $this->assertDatabaseHas('saldo_cutis', [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'terpakai' => 2,
            'sisa' => 10,
        ]);
    }

    public function test_jumlah_hari_excludes_saturday_and_sunday(): void
    {
        $hrd = $this->karyawanUser('hrd')->karyawan;
        $jenisCuti = JenisCuti::factory()->create();
        $karyawan = Karyawan::factory()->create();

        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'kuota' => 12,
            'terpakai' => 0,
            'sisa' => 12,
        ]);

        // Jumat s/d Senin: 4 hari kalender, 2 di antaranya akhir pekan.
        $this->actingAs($hrd->user)->post(route('cuti.massal.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(6)->toDateString(),
            'tanggal_selesai' => now()->addDays(9)->toDateString(),
            'alasan' => 'Cuti bersama',
            'karyawan_ids' => [$karyawan->id],
        ]);

        $this->assertDatabaseHas('saldo_cutis', [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'terpakai' => 2,
            'sisa' => 10,
        ]);
    }

    public function test_balance_goes_negative_instead_of_blocking_for_employees_with_insufficient_balance(): void
    {
        $hrd = $this->karyawanUser('hrd')->karyawan;
        $jenisCuti = JenisCuti::factory()->create();
        $karyawanBaru = Karyawan::factory()->create(['tanggal_masuk' => now()->subDays(5)]);

        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawanBaru->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'kuota' => 12,
            'terpakai' => 11,
            'sisa' => 1,
        ]);

        $response = $this->actingAs($hrd->user)->post(route('cuti.massal.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(12)->toDateString(),
            'alasan' => 'Cuti bersama',
            'karyawan_ids' => [$karyawanBaru->id],
        ]);

        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('pengajuan_cutis', [
            'karyawan_id' => $karyawanBaru->id,
            'status' => 'disetujui',
        ]);

        $this->assertDatabaseHas('saldo_cutis', [
            'karyawan_id' => $karyawanBaru->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'terpakai' => 14,
            'sisa' => -2,
        ]);

        $cutiMassal = CutiMassal::query()->firstOrFail();
        $this->assertSame(1, $cutiMassal->jumlah_karyawan);
        $this->assertCount(0, $cutiMassal->dilewati);
    }

    public function test_nonaktif_employees_are_never_included_in_eligible_list(): void
    {
        $jenisCuti = JenisCuti::factory()->create();
        $aktif = Karyawan::factory()->create();
        $nonaktif = Karyawan::factory()->nonaktif()->create();

        $eligible = app(CutiMassalService::class)->eligibleKaryawan($jenisCuti);

        $this->assertTrue($eligible->contains('id', $aktif->id));
        $this->assertFalse($eligible->contains('id', $nonaktif->id));
    }

    public function test_gender_restricted_jenis_cuti_excludes_ineligible_gender_from_eligible_list(): void
    {
        $jenisCuti = JenisCuti::factory()->create(['khusus_gender' => JenisKelamin::Perempuan]);
        $perempuan = Karyawan::factory()->perempuan()->create();
        $lakiLaki = Karyawan::factory()->lakiLaki()->create();

        $eligible = app(CutiMassalService::class)->eligibleKaryawan($jenisCuti);

        $this->assertTrue($eligible->contains('id', $perempuan->id));
        $this->assertFalse($eligible->contains('id', $lakiLaki->id));
    }

    public function test_hrd_can_exclude_specific_employees_via_karyawan_ids(): void
    {
        $hrd = $this->karyawanUser('hrd')->karyawan;
        $jenisCuti = JenisCuti::factory()->create();

        $disertakan = Karyawan::factory()->create();
        $dikecualikan = Karyawan::factory()->create();

        foreach ([$disertakan, $dikecualikan] as $karyawan) {
            SaldoCuti::factory()->create([
                'karyawan_id' => $karyawan->id,
                'jenis_cuti_id' => $jenisCuti->id,
                'kuota' => 12,
                'terpakai' => 0,
                'sisa' => 12,
            ]);
        }

        $this->actingAs($hrd->user)->post(route('cuti.massal.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(11)->toDateString(),
            'alasan' => 'Cuti bersama',
            'karyawan_ids' => [$disertakan->id],
        ]);

        $this->assertDatabaseHas('pengajuan_cutis', ['karyawan_id' => $disertakan->id]);
        $this->assertDatabaseMissing('pengajuan_cutis', ['karyawan_id' => $dikecualikan->id]);
    }

    public function test_employee_with_overlapping_approved_leave_is_skipped_with_reason_others_still_processed(): void
    {
        $hrd = $this->karyawanUser('hrd')->karyawan;
        $jenisCuti = JenisCuti::factory()->create();

        $konflik = Karyawan::factory()->create();
        $aman = Karyawan::factory()->create();

        foreach ([$konflik, $aman] as $karyawan) {
            SaldoCuti::factory()->create([
                'karyawan_id' => $karyawan->id,
                'jenis_cuti_id' => $jenisCuti->id,
                'kuota' => 12,
                'terpakai' => 0,
                'sisa' => 12,
            ]);
        }

        PengajuanCuti::factory()->disetujui()->create([
            'karyawan_id' => $konflik->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(9),
            'tanggal_selesai' => now()->addDays(12),
        ]);

        $response = $this->actingAs($hrd->user)->post(route('cuti.massal.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(11)->toDateString(),
            'alasan' => 'Cuti bersama',
            'karyawan_ids' => [$konflik->id, $aman->id],
        ]);

        $response->assertSessionDoesntHaveErrors();

        $cutiMassal = CutiMassal::query()->firstOrFail();
        $this->assertSame(1, $cutiMassal->jumlah_karyawan);
        $this->assertCount(1, $cutiMassal->dilewati);
        $this->assertSame($konflik->id, $cutiMassal->dilewati[0]['karyawan_id']);

        $this->assertDatabaseHas('pengajuan_cutis', ['karyawan_id' => $aman->id, 'cuti_massal_id' => $cutiMassal->id]);
    }

    public function test_employee_without_active_saldo_row_is_skipped_with_reason(): void
    {
        $hrd = $this->karyawanUser('hrd')->karyawan;
        $jenisCuti = JenisCuti::factory()->create();
        $karyawan = Karyawan::factory()->create();

        $response = $this->actingAs($hrd->user)->post(route('cuti.massal.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(11)->toDateString(),
            'alasan' => 'Cuti bersama',
            'karyawan_ids' => [$karyawan->id],
        ]);

        $response->assertSessionDoesntHaveErrors();

        $cutiMassal = CutiMassal::query()->firstOrFail();
        $this->assertSame(0, $cutiMassal->jumlah_karyawan);
        $this->assertCount(1, $cutiMassal->dilewati);
        $this->assertDatabaseMissing('pengajuan_cutis', ['karyawan_id' => $karyawan->id]);
    }

    public function test_each_processed_employee_gets_three_preapproved_approval_rows(): void
    {
        $hrd = $this->karyawanUser('hrd')->karyawan;
        $jenisCuti = JenisCuti::factory()->create();
        $karyawan = Karyawan::factory()->create();

        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'kuota' => 12,
            'terpakai' => 0,
            'sisa' => 12,
        ]);

        $this->actingAs($hrd->user)->post(route('cuti.massal.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(11)->toDateString(),
            'alasan' => 'Cuti bersama',
            'karyawan_ids' => [$karyawan->id],
        ]);

        $pengajuan = PengajuanCuti::query()->where('karyawan_id', $karyawan->id)->firstOrFail();

        $this->assertDatabaseCount('approvals', 3);
        foreach ([1, 2, 3] as $level) {
            $this->assertDatabaseHas('approvals', [
                'pengajuan_cuti_id' => $pengajuan->id,
                'level' => $level,
                'status' => 'disetujui',
                'approver_id' => $hrd->id,
            ]);
        }
    }

    public function test_karyawan_without_permission_gets_403_when_creating_mass_leave(): void
    {
        $karyawan = $this->karyawanUser('karyawan')->karyawan;
        $jenisCuti = JenisCuti::factory()->create();

        $response = $this->actingAs($karyawan->user)->post(route('cuti.massal.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(11)->toDateString(),
            'alasan' => 'Cuti bersama',
            'karyawan_ids' => [$karyawan->id],
        ]);

        $response->assertForbidden();
    }

    public function test_hrd_can_cancel_a_mass_leave_batch_and_balances_are_restored(): void
    {
        $hrd = $this->karyawanUser('hrd')->karyawan;
        $jenisCuti = JenisCuti::factory()->create();
        $karyawan = Karyawan::factory()->create();

        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'kuota' => 12,
            'terpakai' => 0,
            'sisa' => 12,
        ]);

        $this->actingAs($hrd->user)->post(route('cuti.massal.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(11)->toDateString(),
            'alasan' => 'Cuti bersama',
            'karyawan_ids' => [$karyawan->id],
        ]);

        $cutiMassal = CutiMassal::query()->firstOrFail();
        $pengajuan = PengajuanCuti::query()->where('karyawan_id', $karyawan->id)->firstOrFail();

        $response = $this->actingAs($hrd->user)->patch(route('cuti.massal.batalkan', $cutiMassal), [
            'catatan_pembatalan' => 'Salah tanggal',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('saldo_cutis', [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'terpakai' => 0,
            'sisa' => 12,
        ]);

        $this->assertDatabaseHas('cuti_massals', [
            'id' => $cutiMassal->id,
            'status' => 'dibatalkan',
        ]);
    }

    public function test_cancelling_a_batch_sets_pengajuan_and_approvals_to_dibatalkan(): void
    {
        $hrd = $this->karyawanUser('hrd')->karyawan;
        $jenisCuti = JenisCuti::factory()->create();
        $karyawan = Karyawan::factory()->create();

        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'kuota' => 12,
            'terpakai' => 0,
            'sisa' => 12,
        ]);

        $this->actingAs($hrd->user)->post(route('cuti.massal.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(11)->toDateString(),
            'alasan' => 'Cuti bersama',
            'karyawan_ids' => [$karyawan->id],
        ]);

        $cutiMassal = CutiMassal::query()->firstOrFail();
        $pengajuan = PengajuanCuti::query()->where('karyawan_id', $karyawan->id)->firstOrFail();

        $this->actingAs($hrd->user)->patch(route('cuti.massal.batalkan', $cutiMassal));

        $this->assertDatabaseHas('pengajuan_cutis', [
            'id' => $pengajuan->id,
            'status' => 'dibatalkan',
        ]);

        $this->assertDatabaseCount('approvals', 3);
        $this->assertDatabaseHas('approvals', [
            'pengajuan_cuti_id' => $pengajuan->id,
            'status' => 'dibatalkan',
        ]);
    }

    public function test_cancelling_an_already_cancelled_batch_is_rejected(): void
    {
        $hrd = $this->karyawanUser('hrd')->karyawan;
        $jenisCuti = JenisCuti::factory()->create();
        $karyawan = Karyawan::factory()->create();

        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'kuota' => 12,
            'terpakai' => 0,
            'sisa' => 12,
        ]);

        $this->actingAs($hrd->user)->post(route('cuti.massal.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(11)->toDateString(),
            'alasan' => 'Cuti bersama',
            'karyawan_ids' => [$karyawan->id],
        ]);

        $cutiMassal = CutiMassal::query()->firstOrFail();

        $this->actingAs($hrd->user)->patch(route('cuti.massal.batalkan', $cutiMassal));

        $response = $this->actingAs($hrd->user)->patch(route('cuti.massal.batalkan', $cutiMassal));
        $response->assertRedirect();

        $this->assertDatabaseHas('saldo_cutis', [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'terpakai' => 0,
            'sisa' => 12,
        ]);
    }

    public function test_mass_leave_shows_up_in_employee_own_cuti_history_and_show_page(): void
    {
        $hrd = $this->karyawanUser('hrd')->karyawan;
        $jenisCuti = JenisCuti::factory()->create();
        $karyawan = $this->karyawanUser('karyawan')->karyawan;

        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'kuota' => 12,
            'terpakai' => 0,
            'sisa' => 12,
        ]);

        $this->actingAs($hrd->user)->post(route('cuti.massal.store'), [
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(11)->toDateString(),
            'alasan' => 'Cuti bersama',
            'karyawan_ids' => [$karyawan->id],
        ]);

        $pengajuan = PengajuanCuti::query()->where('karyawan_id', $karyawan->id)->firstOrFail();

        $this->actingAs($karyawan->user)->get(route('cuti.index'))->assertOk();
        $this->actingAs($karyawan->user)->get(route('cuti.show', $pengajuan))->assertOk();
    }
}
