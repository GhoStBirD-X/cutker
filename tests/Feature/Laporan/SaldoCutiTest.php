<?php

namespace Tests\Feature\Laporan;

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
    public function test_allowed_role_can_view_all_karyawan_saldo_cuti(string $role): void
    {
        $user = $this->karyawanUser($role);
        $saldo = SaldoCuti::factory()->create(['sisa' => 8]);

        $response = $this->actingAs($user)->get(route('laporan.saldo-cuti'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('laporan/saldo-cuti')
            ->where('saldoCutis.data.0.id', $saldo->id)
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
        $cocok = SaldoCuti::factory()->create();
        $cocok->karyawan()->update(['nama' => 'Budi Santoso']);
        $lain = SaldoCuti::factory()->create();
        $lain->karyawan()->update(['nama' => 'Siti Aminah']);

        $response = $this->actingAs($hrd)->get(route('laporan.saldo-cuti', ['search' => 'Budi']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('saldoCutis.data', 1)
            ->where('saldoCutis.data.0.id', $cocok->id)
        );
    }
}
