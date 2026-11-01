<?php

namespace Tests\Feature\Cuti;

use App\Enums\StatusPengajuan;
use App\Enums\TipeKaryawan;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\KonfirmasiKontrakCuti;
use App\Models\PengajuanCuti;
use App\Models\SaldoCuti;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class PenampunganCutiTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected Karyawan $karyawan;

    protected JenisCuti $jenisCuti;

    protected SaldoCuti $saldo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        // Senin; periode saldo berakhir Jumat 16 Okt 2026.
        $this->travelTo(Carbon::parse('2026-10-05'));

        $this->karyawan = $this->karyawanUser('karyawan', [
            'tipe_karyawan' => TipeKaryawan::Kontrak,
            'tanggal_masuk' => '2024-10-17',
        ])->karyawan;
        $this->karyawanUser('kepala_bagian', ['departemen_id' => $this->karyawan->departemen_id]);
        $this->jenisCuti = JenisCuti::factory()->create([
            'nama_jenis' => 'Cuti Tahunan',
            'kuota_default' => 12,
            'masa_kerja_minimal_bulan' => 12,
        ]);
        $this->saldo = SaldoCuti::factory()->create([
            'karyawan_id' => $this->karyawan->id,
            'jenis_cuti_id' => $this->jenisCuti->id,
            'periode_ke' => 2,
            'periode_mulai' => '2025-10-17',
            'periode_selesai' => '2026-10-16',
            'kuota' => 12,
            'terpakai' => 0,
            'sisa' => 12,
        ]);
    }

    public function test_request_ending_after_saldo_period_goes_to_penampungan_without_approval(): void
    {
        $response = $this->ajukan('2026-10-15', '2026-10-20');

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $pengajuan = PengajuanCuti::query()->firstOrFail();
        $this->assertSame(StatusPengajuan::Ditampung, $pengajuan->status);
        $this->assertDatabaseCount('approvals', 0);
        $this->assertSame(12, $this->saldo->fresh()->sisa);
    }

    public function test_request_within_saldo_period_is_submitted_normally(): void
    {
        $this->ajukan('2026-10-14', '2026-10-16')->assertSessionDoesntHaveErrors();

        $this->assertSame(StatusPengajuan::Pending, PengajuanCuti::query()->firstOrFail()->status);
    }

    public function test_held_request_blocks_overlapping_new_request(): void
    {
        $this->ajukan('2026-10-19', '2026-10-20');

        $this->ajukan('2026-10-20', '2026-10-21')->assertSessionHasErrors('tanggal_selesai');
    }

    public function test_held_request_cannot_be_resubmitted_before_saldo_period_is_renewed(): void
    {
        $this->ajukan('2026-10-19', '2026-10-20');
        $pengajuan = PengajuanCuti::query()->firstOrFail();

        $this->actingAs($this->karyawan->user)
            ->patch(route('cuti.ajukan-ulang', $pengajuan))
            ->assertRedirect();

        $this->assertSame(StatusPengajuan::Ditampung, $pengajuan->fresh()->status);
        $this->assertDatabaseCount('approvals', 0);
    }

    public function test_held_request_can_be_resubmitted_once_new_saldo_period_is_active(): void
    {
        $this->ajukan('2026-10-19', '2026-10-20');
        $pengajuan = PengajuanCuti::query()->firstOrFail();

        $this->travelTo(Carbon::parse('2026-10-17'));
        $this->saldo->update(['ditutup_pada' => now()]);
        SaldoCuti::factory()->create([
            'karyawan_id' => $this->karyawan->id,
            'jenis_cuti_id' => $this->jenisCuti->id,
            'periode_ke' => 3,
            'periode_mulai' => '2026-10-17',
            'periode_selesai' => '2027-10-16',
            'kuota' => 12,
            'terpakai' => 0,
            'sisa' => 12,
        ]);

        $this->actingAs($this->karyawan->user)
            ->patch(route('cuti.ajukan-ulang', $pengajuan))
            ->assertRedirect();

        $this->assertSame(StatusPengajuan::Pending, $pengajuan->fresh()->status);
        $this->assertDatabaseHas('approvals', [
            'pengajuan_cuti_id' => $pengajuan->id,
            'level' => 1,
            'status' => 'pending',
        ]);
    }

    public function test_other_employee_cannot_resubmit_held_request(): void
    {
        $this->ajukan('2026-10-19', '2026-10-20');
        $pengajuan = PengajuanCuti::query()->firstOrFail();
        $lain = $this->karyawanUser('karyawan');

        $this->actingAs($lain)
            ->patch(route('cuti.ajukan-ulang', $pengajuan))
            ->assertForbidden();
    }

    public function test_held_requests_are_cancelled_when_contract_is_not_extended(): void
    {
        $this->ajukan('2026-10-19', '2026-10-20');
        $konfirmasi = KonfirmasiKontrakCuti::factory()->create([
            'karyawan_id' => $this->karyawan->id,
            'saldo_cuti_id' => $this->saldo->id,
        ]);

        $this->actingAs($this->karyawanUser('hrd'))
            ->post(route('cuti.konfirmasi-kontrak.konfirmasi', $konfirmasi), ['keputusan' => 'tidak_diperpanjang'])
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(StatusPengajuan::Dibatalkan, PengajuanCuti::query()->firstOrFail()->status);
    }

    public function test_daily_cycle_cancels_held_requests_whose_start_date_has_passed(): void
    {
        $this->ajukan('2026-10-19', '2026-10-20');
        $this->ajukan('2026-10-26', '2026-10-27');

        $this->travelTo(Carbon::parse('2026-10-19'));
        $this->artisan('cuti:proses-siklus-tahunan')->assertSuccessful();

        $this->assertSame(
            [StatusPengajuan::Dibatalkan, StatusPengajuan::Ditampung],
            PengajuanCuti::query()->orderBy('tanggal_mulai')->pluck('status')->all(),
        );
    }

    protected function ajukan(string $mulai, string $selesai): TestResponse
    {
        return $this->actingAs($this->karyawan->user)->post(route('cuti.store'), [
            'jenis_cuti_id' => $this->jenisCuti->id,
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $selesai,
            'alasan' => 'Acara keluarga',
        ]);
    }
}
