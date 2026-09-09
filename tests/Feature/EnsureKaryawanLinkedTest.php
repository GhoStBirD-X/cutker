<?php

namespace Tests\Feature;

use App\Models\JadwalShift;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class EnsureKaryawanLinkedTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_unlinked_user_visiting_cuti_index_is_redirected_to_dashboard_instead_of_erroring(): void
    {
        $user = $this->unlinkedUser('karyawan');

        $response = $this->actingAs($user)->get(route('cuti.index'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_unlinked_user_visiting_ajukan_cuti_is_redirected_to_dashboard_instead_of_erroring(): void
    {
        $user = $this->unlinkedUser('karyawan');

        $response = $this->actingAs($user)->get(route('cuti.create'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_unlinked_hrd_user_visiting_approval_index_is_redirected_to_dashboard_instead_of_erroring(): void
    {
        $user = $this->unlinkedUser('hrd');

        $response = $this->actingAs($user)->get(route('approval.index'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_unlinked_user_sees_empty_jadwal_shift_instead_of_every_departments_schedule(): void
    {
        $karyawanLain = $this->karyawanUser('karyawan');
        JadwalShift::factory()->create(['karyawan_id' => $karyawanLain->karyawan->id]);

        $user = $this->unlinkedUser('karyawan');

        $response = $this->actingAs($user)->get(route('jadwal-shift.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('jadwals', []));
    }
}
