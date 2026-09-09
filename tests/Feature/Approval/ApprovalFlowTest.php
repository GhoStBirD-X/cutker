<?php

namespace Tests\Feature\Approval;

use App\Enums\StatusApproval;
use App\Enums\StatusPengajuan;
use App\Models\Approval;
use App\Models\JenisCuti;
use App\Models\PengajuanCuti;
use App\Models\SaldoCuti;
use App\Models\User;
use App\Services\ApprovalService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class ApprovalFlowTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * @return array{pengajuan: PengajuanCuti, approvalLevel1: Approval, saldo: SaldoCuti}
     */
    protected function buatPengajuanDenganApprovalLevel1(int $jumlahHari = 3, ?int $kuota = 12): array
    {
        $karyawan = $this->karyawanUser('karyawan')->karyawan;
        $kepalaBagian = $this->karyawanUser('kepala_bagian', ['departemen_id' => $karyawan->departemen_id])->karyawan;
        $jenisCuti = JenisCuti::factory()->create();

        $saldo = SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'tahun' => now()->year,
            'kuota' => $kuota,
            'terpakai' => 0,
            'sisa' => $kuota,
        ]);

        $pengajuan = PengajuanCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'jumlah_hari' => $jumlahHari,
            'status' => StatusPengajuan::Pending,
        ]);

        $approvalLevel1 = Approval::factory()->create([
            'pengajuan_cuti_id' => $pengajuan->id,
            'approver_id' => $kepalaBagian->id,
            'level' => ApprovalService::LEVEL_KEPALA_BAGIAN,
            'status' => StatusApproval::Pending,
        ]);

        return compact('pengajuan', 'approvalLevel1', 'saldo') + ['kepalaBagian' => $kepalaBagian];
    }

    /**
     * @return array{pengajuan: PengajuanCuti, approvalLevel2: Approval, saldo: SaldoCuti, hrd: User}
     */
    protected function buatPengajuanDenganApprovalLevel2(int $jumlahHari = 3, ?int $kuota = 12): array
    {
        ['pengajuan' => $pengajuan, 'saldo' => $saldo] = $this->buatPengajuanDenganApprovalLevel1(jumlahHari: $jumlahHari, kuota: $kuota);

        $hrd = $this->karyawanUser('hrd');

        $approvalLevel2 = Approval::factory()->create([
            'pengajuan_cuti_id' => $pengajuan->id,
            'approver_id' => $hrd->karyawan->id,
            'level' => ApprovalService::LEVEL_HRD,
            'status' => StatusApproval::Pending,
        ]);

        return compact('pengajuan', 'approvalLevel2', 'saldo', 'hrd');
    }

    public function test_kepala_bagian_approval_forwards_request_to_hrd_without_touching_saldo(): void
    {
        ['pengajuan' => $pengajuan, 'approvalLevel1' => $approvalLevel1, 'saldo' => $saldo, 'kepalaBagian' => $kepalaBagian] =
            $this->buatPengajuanDenganApprovalLevel1();
        $this->karyawanUser('hrd');

        $response = $this->actingAs($kepalaBagian->user)->post(route('approval.approve', $approvalLevel1), [
            'catatan' => 'Disetujui kepala bagian',
        ]);

        $response->assertRedirect(route('approval.index'));

        $this->assertDatabaseHas('approvals', [
            'id' => $approvalLevel1->id,
            'status' => 'disetujui',
        ]);

        $this->assertDatabaseHas('approvals', [
            'pengajuan_cuti_id' => $pengajuan->id,
            'level' => ApprovalService::LEVEL_HRD,
            'status' => 'pending',
        ]);

        $this->assertSame(StatusPengajuan::Pending, $pengajuan->fresh()->status);
        $this->assertSame(0, $saldo->fresh()->terpakai);
    }

    public function test_hrd_approval_forwards_request_to_manager_without_touching_saldo(): void
    {
        ['pengajuan' => $pengajuan, 'approvalLevel2' => $approvalLevel2, 'saldo' => $saldo, 'hrd' => $hrd] =
            $this->buatPengajuanDenganApprovalLevel2();
        $this->karyawanUser('manager');

        $response = $this->actingAs($hrd)->post(route('approval.approve', $approvalLevel2), [
            'catatan' => 'Diteruskan ke manager',
        ]);

        $response->assertRedirect(route('approval.index'));

        $this->assertDatabaseHas('approvals', [
            'id' => $approvalLevel2->id,
            'status' => 'disetujui',
        ]);

        $this->assertDatabaseHas('approvals', [
            'pengajuan_cuti_id' => $pengajuan->id,
            'level' => ApprovalService::LEVEL_MANAGER,
            'status' => 'pending',
        ]);

        $this->assertSame(StatusPengajuan::Pending, $pengajuan->fresh()->status);
        $this->assertSame(0, $saldo->fresh()->terpakai);
    }

    public function test_manager_final_approval_deducts_saldo_and_marks_request_approved(): void
    {
        ['pengajuan' => $pengajuan, 'saldo' => $saldo] = $this->buatPengajuanDenganApprovalLevel2(jumlahHari: 3, kuota: 12);

        $manager = $this->karyawanUser('manager');

        $approvalLevel3 = Approval::factory()->create([
            'pengajuan_cuti_id' => $pengajuan->id,
            'approver_id' => $manager->karyawan->id,
            'level' => ApprovalService::LEVEL_MANAGER,
            'status' => StatusApproval::Pending,
        ]);

        $response = $this->actingAs($manager)->post(route('approval.approve', $approvalLevel3), [
            'catatan' => 'Final approved',
        ]);

        $response->assertRedirect(route('approval.index'));

        $this->assertSame(StatusPengajuan::Disetujui, $pengajuan->fresh()->status);

        $saldo->refresh();
        $this->assertSame(3, $saldo->terpakai);
        $this->assertSame(9, $saldo->sisa);
    }

    public function test_manager_final_approval_keeps_sisa_null_when_saldo_is_unlimited(): void
    {
        ['pengajuan' => $pengajuan, 'saldo' => $saldo] = $this->buatPengajuanDenganApprovalLevel2(jumlahHari: 3, kuota: null);

        $manager = $this->karyawanUser('manager');

        $approvalLevel3 = Approval::factory()->create([
            'pengajuan_cuti_id' => $pengajuan->id,
            'approver_id' => $manager->karyawan->id,
            'level' => ApprovalService::LEVEL_MANAGER,
            'status' => StatusApproval::Pending,
        ]);

        $response = $this->actingAs($manager)->post(route('approval.approve', $approvalLevel3), [
            'catatan' => 'Final approved',
        ]);

        $response->assertRedirect(route('approval.index'));

        $this->assertSame(StatusPengajuan::Disetujui, $pengajuan->fresh()->status);

        $saldo->refresh();
        $this->assertSame(3, $saldo->terpakai);
        $this->assertNull($saldo->sisa);
    }

    public function test_rejection_marks_request_rejected_without_deducting_saldo(): void
    {
        ['pengajuan' => $pengajuan, 'approvalLevel1' => $approvalLevel1, 'saldo' => $saldo, 'kepalaBagian' => $kepalaBagian] =
            $this->buatPengajuanDenganApprovalLevel1();

        $response = $this->actingAs($kepalaBagian->user)->post(route('approval.reject', $approvalLevel1), [
            'catatan' => 'Tidak disetujui, beban kerja tinggi',
        ]);

        $response->assertRedirect(route('approval.index'));

        $this->assertSame(StatusPengajuan::Ditolak, $pengajuan->fresh()->status);
        $this->assertSame(0, $saldo->fresh()->terpakai);

        $this->assertDatabaseMissing('approvals', [
            'pengajuan_cuti_id' => $pengajuan->id,
            'level' => ApprovalService::LEVEL_HRD,
        ]);
    }

    public function test_karyawan_without_approval_role_cannot_approve_a_request(): void
    {
        ['approvalLevel1' => $approvalLevel1] = $this->buatPengajuanDenganApprovalLevel1();

        $unrelatedKaryawan = $this->karyawanUser('karyawan');

        $response = $this->actingAs($unrelatedKaryawan)->post(route('approval.approve', $approvalLevel1), [
            'catatan' => null,
        ]);

        $response->assertForbidden();
    }

    public function test_kepala_bagian_cannot_approve_a_request_assigned_to_a_different_approver(): void
    {
        ['approvalLevel1' => $approvalLevel1] = $this->buatPengajuanDenganApprovalLevel1();

        $otherKepalaBagian = $this->karyawanUser('kepala_bagian');

        $response = $this->actingAs($otherKepalaBagian)->post(route('approval.approve', $approvalLevel1), [
            'catatan' => null,
        ]);

        $response->assertForbidden();
    }
}
