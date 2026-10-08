<?php

namespace Tests\Feature\Cuti;

use App\Enums\StatusApproval;
use App\Enums\StatusPengajuan;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\SaldoCuti;
use App\Services\ApprovalService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class InputCutiKaryawanTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->travelTo(Carbon::parse('2026-10-05'));
    }

    #[TestWith(['hrd'])]
    #[TestWith(['admin'])]
    public function test_hrd_and_admin_can_record_long_backdated_cuti_which_is_approved_immediately(string $role): void
    {
        $pencatat = $this->karyawanUser($role);
        [$karyawan, $saldo] = $this->karyawanDenganSaldo(sisa: 12);

        // Senin–Selasa, 9 bulan lalu.
        $response = $this->actingAs($pencatat)->post(route('cuti.input-karyawan.store'), [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $saldo->jenis_cuti_id,
            'tanggal_mulai' => '2026-01-05',
            'tanggal_selesai' => '2026-01-06',
            'alasan' => 'Sakit, lupa diajukan.',
        ]);

        $pengajuan = $karyawan->pengajuanCutis()->sole();
        $response->assertRedirect(route('cuti.show', $pengajuan));
        $this->assertSame(StatusPengajuan::Disetujui, $pengajuan->status);
        $this->assertSame(2, $pengajuan->jumlah_hari);
        $this->assertDatabaseHas('saldo_cutis', ['id' => $saldo->id, 'terpakai' => 2, 'sisa' => 10]);
        $this->assertDatabaseHas('approvals', [
            'pengajuan_cuti_id' => $pengajuan->id,
            'approver_id' => $pencatat->karyawan_id,
            'level' => ApprovalService::LEVEL_HRD,
            'status' => StatusApproval::Disetujui,
        ]);
        $this->assertDatabaseCount('approvals', 1);
    }

    public function test_karyawan_cannot_record_cuti_for_someone_else(): void
    {
        $user = $this->karyawanUser('karyawan');
        [$karyawan, $saldo] = $this->karyawanDenganSaldo(sisa: 12);

        $response = $this->actingAs($user)->post(route('cuti.input-karyawan.store'), [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $saldo->jenis_cuti_id,
            'tanggal_mulai' => '2026-01-05',
            'tanggal_selesai' => '2026-01-06',
            'alasan' => 'Sakit.',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('pengajuan_cutis', 0);
    }

    public function test_recording_is_rejected_when_saldo_of_the_karyawan_is_not_enough(): void
    {
        $hrd = $this->karyawanUser('hrd');
        [$karyawan, $saldo] = $this->karyawanDenganSaldo(sisa: 1);

        $response = $this->actingAs($hrd)->post(route('cuti.input-karyawan.store'), [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $saldo->jenis_cuti_id,
            'tanggal_mulai' => '2026-01-05',
            'tanggal_selesai' => '2026-01-06',
            'alasan' => 'Sakit.',
        ]);

        $response->assertSessionHasErrors('tanggal_selesai');
        $this->assertDatabaseCount('pengajuan_cutis', 0);
        $this->assertDatabaseHas('saldo_cutis', ['id' => $saldo->id, 'sisa' => 1]);
    }

    public function test_form_loads_saldo_of_the_selected_karyawan(): void
    {
        $hrd = $this->karyawanUser('hrd');
        [$karyawan, $saldo] = $this->karyawanDenganSaldo(sisa: 12);

        $this->actingAs($hrd)->get(route('cuti.input-karyawan.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('cuti/input-karyawan')
                ->where('karyawanId', null)
                ->missing('saldoCuti'));

        $this->actingAs($hrd)->get(route('cuti.input-karyawan.create', ['karyawan_id' => $karyawan->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('karyawanId', $karyawan->id)
                ->has('saldoCuti', 1)
                ->where('saldoCuti.0.id', $saldo->id));
    }

    /**
     * @return array{0: Karyawan, 1: SaldoCuti}
     */
    private function karyawanDenganSaldo(int $sisa): array
    {
        $karyawan = Karyawan::factory()->create();
        $saldo = SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => JenisCuti::factory()->create()->id,
            'tahun' => now()->year,
            'kuota' => 12,
            'terpakai' => 12 - $sisa,
            'sisa' => $sisa,
        ]);

        return [$karyawan, $saldo];
    }
}
