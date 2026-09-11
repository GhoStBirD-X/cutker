<?php

namespace Tests\Feature\Laporan;

use App\Models\Karyawan;
use App\Models\SaldoCuti;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class SaldoCutiTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function allowedRoles(): iterable
    {
        yield 'admin' => ['admin'];
        yield 'hrd' => ['hrd'];
        yield 'manager' => ['manager'];
        yield 'kepala_bagian' => ['kepala_bagian'];
    }

    #[DataProvider('allowedRoles')]
    public function test_allowed_role_can_view_karyawan_grouped_saldo_cuti(string $role): void
    {
        $user = $this->karyawanUser($role);
        $saldo = SaldoCuti::factory()->create(['sisa' => 8]);

        $response = $this->actingAs($user)->get(route('laporan.saldo-cuti', [
            'search' => $saldo->karyawan->nip,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('laporan/saldo-cuti')
            ->where('karyawans.data.0.id', $saldo->karyawan_id)
            ->where('karyawans.data.0.saldo_cutis.0.id', $saldo->id)
        );
    }

    public function test_karyawan_and_koordinator_shift_cannot_access_saldo_cuti_report(): void
    {
        $karyawan = $this->karyawanUser('karyawan');
        $koordinator = $this->karyawanUser('koordinator_shift');

        $this->actingAs($karyawan)->get(route('laporan.saldo-cuti'))->assertForbidden();
        $this->actingAs($koordinator)->get(route('laporan.saldo-cuti'))->assertForbidden();
    }

    public function test_search_filters_by_karyawan_name(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $cocok = Karyawan::factory()->create(['nama' => 'Budi Santoso']);
        Karyawan::factory()->create(['nama' => 'Siti Aminah']);

        $response = $this->actingAs($hrd)->get(route('laporan.saldo-cuti', ['search' => 'Budi']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('karyawans.data', 1)
            ->where('karyawans.data.0.id', $cocok->id)
        );
    }

    public function test_karyawan_without_active_saldo_still_lists_with_empty_children(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = Karyawan::factory()->create();

        $response = $this->actingAs($hrd)->get(route('laporan.saldo-cuti', [
            'search' => $karyawan->nip,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('karyawans.data', 1)
            ->where('karyawans.data.0.saldo_cutis', [])
        );
    }
}
