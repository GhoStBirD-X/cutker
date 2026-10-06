<?php

namespace Tests\Feature\Notifications;

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
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class StatusPengajuanCutiWhatsAppTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected const NOMOR_PENGAJU = '6281111111111';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        config([
            'services.evolution.base_url' => 'http://evolution.test',
            'services.evolution.instance' => 'cutker',
        ]);

        Http::fake(['*/message/sendText/*' => Http::response(['status' => 'PENDING'], 201)]);
    }

    protected function buatApproval(int $level, User $approver): Approval
    {
        $pengaju = $this->karyawanUser('karyawan', ['no_hp' => '081111111111'])->karyawan;
        $jenisCuti = JenisCuti::factory()->create();

        SaldoCuti::factory()->create([
            'karyawan_id' => $pengaju->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'tahun' => now()->year,
            'kuota' => 12,
            'terpakai' => 0,
            'sisa' => 12,
        ]);

        $pengajuan = PengajuanCuti::factory()->create([
            'karyawan_id' => $pengaju->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'jumlah_hari' => 2,
            'status' => StatusPengajuan::Pending,
        ]);

        return Approval::factory()->create([
            'pengajuan_cuti_id' => $pengajuan->id,
            'approver_id' => $approver->karyawan->id,
            'level' => $level,
            'status' => StatusApproval::Pending,
        ]);
    }

    protected function assertPengajuDikirimiWhatsApp(string ...$potonganPesan): void
    {
        Http::assertSent(function (Request $request) use ($potonganPesan) {
            if ($request['number'] !== self::NOMOR_PENGAJU) {
                return false;
            }

            foreach ($potonganPesan as $potongan) {
                if (! str_contains($request['text'], $potongan)) {
                    return false;
                }
            }

            return true;
        });
    }

    public function test_pengaju_is_told_kepala_bagian_approved_and_hrd_is_next(): void
    {
        $kepalaBagian = $this->karyawanUser('kepala_bagian');
        $approval = $this->buatApproval(ApprovalService::LEVEL_KEPALA_BAGIAN, $kepalaBagian);

        $this->actingAs($kepalaBagian)->post(route('approval.approve', $approval), [
            'catatan' => 'Silakan',
        ]);

        $this->assertPengajuDikirimiWhatsApp(
            "disetujui Kepala Bagian* ({$kepalaBagian->karyawan->nama})",
            'menunggu persetujuan *HRD*',
            'Catatan: Silakan',
        );
    }

    public function test_pengaju_is_told_hrd_approved_and_manager_is_next(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $approval = $this->buatApproval(ApprovalService::LEVEL_HRD, $hrd);

        $this->actingAs($hrd)->post(route('approval.approve', $approval));

        $this->assertPengajuDikirimiWhatsApp('disetujui HRD*', 'menunggu persetujuan *Manager*');
    }

    public function test_pengaju_is_told_leave_can_be_used_after_final_approval(): void
    {
        $manager = $this->karyawanUser('manager');
        $approval = $this->buatApproval(ApprovalService::LEVEL_MANAGER, $manager);

        $this->actingAs($manager)->post(route('approval.approve', $approval));

        $this->assertPengajuDikirimiWhatsApp('disetujui final', 'Cuti dapat digunakan');
        Http::assertNotSent(fn (Request $request) => str_contains($request['text'], 'menunggu persetujuan'));
    }

    public function test_pengaju_is_told_which_level_rejected_and_why(): void
    {
        $kepalaBagian = $this->karyawanUser('kepala_bagian');
        $approval = $this->buatApproval(ApprovalService::LEVEL_KEPALA_BAGIAN, $kepalaBagian);

        $this->actingAs($kepalaBagian)->post(route('approval.reject', $approval), [
            'catatan' => 'Beban kerja tinggi',
        ]);

        $this->assertPengajuDikirimiWhatsApp('ditolak oleh Kepala Bagian', 'Catatan: Beban kerja tinggi');
    }
}
