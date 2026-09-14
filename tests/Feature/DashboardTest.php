<?php

namespace Tests\Feature;

use App\Enums\JenisKelamin;
use App\Enums\StatusApproval;
use App\Models\Approval;
use App\Models\JenisCuti;
use App\Models\PengajuanCuti;
use App\Models\SaldoCuti;
use App\Models\User;
use App\Services\ApprovalService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_male_karyawan_does_not_see_gender_restricted_saldo(): void
    {
        $karyawan = $this->karyawanUser('karyawan', ['jenis_kelamin' => JenisKelamin::LakiLaki])->karyawan;

        $cutiHamil = JenisCuti::factory()->create(['khusus_gender' => JenisKelamin::Perempuan]);
        $cutiTahunan = JenisCuti::factory()->create(['khusus_gender' => null]);
        SaldoCuti::factory()->create(['karyawan_id' => $karyawan->id, 'jenis_cuti_id' => $cutiHamil->id]);
        SaldoCuti::factory()->create(['karyawan_id' => $karyawan->id, 'jenis_cuti_id' => $cutiTahunan->id]);

        $response = $this->actingAs($karyawan->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(function (Assert $page) use ($cutiHamil, $cutiTahunan) {
            $jenisCutiIds = collect($page->toArray()['props']['saldoCuti'])->pluck('jenis_cuti_id');

            $this->assertFalse($jenisCutiIds->contains($cutiHamil->id));
            $this->assertTrue($jenisCutiIds->contains($cutiTahunan->id));
        });
    }

    public function test_female_karyawan_sees_gender_restricted_saldo(): void
    {
        $karyawan = $this->karyawanUser('karyawan', ['jenis_kelamin' => JenisKelamin::Perempuan])->karyawan;

        $cutiHamil = JenisCuti::factory()->create(['khusus_gender' => JenisKelamin::Perempuan]);
        SaldoCuti::factory()->create(['karyawan_id' => $karyawan->id, 'jenis_cuti_id' => $cutiHamil->id]);

        $response = $this->actingAs($karyawan->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(function (Assert $page) use ($cutiHamil) {
            $jenisCutiIds = collect($page->toArray()['props']['saldoCuti'])->pluck('jenis_cuti_id');

            $this->assertTrue($jenisCutiIds->contains($cutiHamil->id));
        });
    }

    public function test_hrd_dashboard_counts_pending_hrd_approvals_from_any_departemen_not_just_own(): void
    {
        $hrdLain = $this->karyawanUser('hrd');
        $hrdYangLihatDashboard = $this->karyawanUser('hrd');
        $pengajuan = PengajuanCuti::factory()->create();

        // Approval level HRD ini "ditugaskan" ke HRD lain (approver_id-nya
        // bukan milik $hrdYangLihatDashboard), tapi karena HRD adalah kolam
        // bersama, tetap harus terhitung di dashboard-nya.
        Approval::factory()->create([
            'pengajuan_cuti_id' => $pengajuan->id,
            'approver_id' => $hrdLain->karyawan->id,
            'level' => ApprovalService::LEVEL_HRD,
            'status' => StatusApproval::Pending,
        ]);

        $response = $this->actingAs($hrdYangLihatDashboard)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('approvalPendingCount', 1)
        );
    }
}
