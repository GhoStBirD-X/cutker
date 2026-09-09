<?php

namespace Tests\Feature\Master;

use App\Enums\SumberHariLibur;
use App\Models\HariLibur;
use App\Services\HariLiburService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class HariLiburManagementTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_hrd_can_create_hari_libur_manually(): void
    {
        $hrd = $this->karyawanUser('hrd');

        $response = $this->actingAs($hrd)->post(route('master.hari-libur.store'), [
            'tanggal' => '2027-05-17',
            'keterangan' => 'Cuti Bersama Perusahaan',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('hari_liburs', [
            'tanggal' => '2027-05-17',
            'keterangan' => 'Cuti Bersama Perusahaan',
            'sumber' => SumberHariLibur::Perusahaan->value,
        ]);
    }

    public function test_hari_libur_tanggal_must_be_unique(): void
    {
        $hrd = $this->karyawanUser('hrd');
        HariLibur::factory()->create(['tanggal' => '2027-08-17']);

        $response = $this->actingAs($hrd)->post(route('master.hari-libur.store'), [
            'tanggal' => '2027-08-17',
            'keterangan' => 'Duplikat',
        ]);

        $response->assertSessionHasErrors('tanggal');
    }

    public function test_karyawan_cannot_access_master_hari_libur_page(): void
    {
        $karyawan = $this->karyawanUser('karyawan');

        $response = $this->actingAs($karyawan)->get(route('master.hari-libur.index'));

        $response->assertForbidden();
    }

    public function test_sync_service_stores_only_actual_holidays_for_the_requested_year(): void
    {
        Http::fake([
            'raw.githubusercontent.com/*' => Http::response([
                '2027-01-01' => ['holiday' => true, 'summary' => ['Tahun Baru']],
                '2027-02-19' => ['holiday' => false, 'summary' => ['Bukan Libur']],
                '2028-01-01' => ['holiday' => true, 'summary' => ['Tahun Baru Lain']],
            ]),
        ]);

        $disinkron = app(HariLiburService::class)->sync(2027);

        $this->assertSame(1, $disinkron);
        $this->assertDatabaseHas('hari_liburs', [
            'tanggal' => '2027-01-01',
            'sumber' => SumberHariLibur::Nasional->value,
        ]);
        $this->assertDatabaseMissing('hari_liburs', ['tanggal' => '2028-01-01']);
    }

    public function test_sync_service_does_not_throw_when_request_fails(): void
    {
        Http::fake([
            'raw.githubusercontent.com/*' => Http::response(null, 500),
        ]);

        $disinkron = app(HariLiburService::class)->sync(2027);

        $this->assertSame(0, $disinkron);
    }
}
