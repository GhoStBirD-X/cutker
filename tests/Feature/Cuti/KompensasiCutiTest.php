<?php

namespace Tests\Feature\Cuti;

use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\KompensasiCuti;
use App\Models\RiwayatSaldoCuti;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class KompensasiCutiTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_hrd_can_process_kompensasi_cuti_with_a_manual_rate(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = Karyawan::factory()->create();
        $jenisCuti = JenisCuti::factory()->create();
        $kompensasi = KompensasiCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'jumlah_hari' => 5,
        ]);

        $response = $this->actingAs($hrd)->post(route('cuti.kompensasi.proses', $kompensasi), [
            'rate_per_hari' => 150000,
            'catatan' => 'Dibayarkan bersama gaji September.',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('kompensasi_cutis', [
            'id' => $kompensasi->id,
            'rate_per_hari' => 150000,
            'total_rupiah' => 750000,
            'status' => 'diproses',
            'diproses_oleh_id' => $hrd->karyawan->id,
        ]);
    }

    public function test_kompensasi_already_processed_cannot_be_processed_again(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $kompensasi = KompensasiCuti::factory()->diproses()->create();

        $response = $this->actingAs($hrd)->post(route('cuti.kompensasi.proses', $kompensasi), [
            'rate_per_hari' => 200000,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('kompensasi_cutis', ['id' => $kompensasi->id, 'rate_per_hari' => $kompensasi->rate_per_hari]);
    }

    public function test_hrd_can_process_many_kompensasi_cuti_at_once_each_with_its_own_rate(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $jenisCuti = JenisCuti::factory()->create();
        $kompensasiSatu = KompensasiCuti::factory()->create([
            'jenis_cuti_id' => $jenisCuti->id,
            'jumlah_hari' => 3,
        ]);
        $kompensasiDua = KompensasiCuti::factory()->create([
            'jenis_cuti_id' => $jenisCuti->id,
            'jumlah_hari' => 4,
        ]);

        $response = $this->actingAs($hrd)->post(route('cuti.kompensasi.proses-massal'), [
            'items' => [
                ['id' => $kompensasiSatu->id, 'rate_per_hari' => 150000],
                ['id' => $kompensasiDua->id, 'rate_per_hari' => 200000],
            ],
            'catatan' => 'Dibayarkan bersama gaji September.',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('kompensasi_cutis', [
            'id' => $kompensasiSatu->id,
            'rate_per_hari' => 150000,
            'total_rupiah' => 450000,
            'status' => 'diproses',
            'diproses_oleh_id' => $hrd->karyawan->id,
        ]);
        $this->assertDatabaseHas('kompensasi_cutis', [
            'id' => $kompensasiDua->id,
            'rate_per_hari' => 200000,
            'total_rupiah' => 800000,
            'status' => 'diproses',
            'diproses_oleh_id' => $hrd->karyawan->id,
        ]);
    }

    public function test_bulk_process_skips_kompensasi_that_is_already_processed(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $pending = KompensasiCuti::factory()->create(['jumlah_hari' => 2]);
        $sudahDiproses = KompensasiCuti::factory()->diproses()->create();

        $response = $this->actingAs($hrd)->post(route('cuti.kompensasi.proses-massal'), [
            'items' => [
                ['id' => $pending->id, 'rate_per_hari' => 100000],
                ['id' => $sudahDiproses->id, 'rate_per_hari' => 100000],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('kompensasi_cutis', [
            'id' => $pending->id,
            'status' => 'diproses',
            'total_rupiah' => 200000,
        ]);
        $this->assertDatabaseHas('kompensasi_cutis', [
            'id' => $sudahDiproses->id,
            'rate_per_hari' => $sudahDiproses->rate_per_hari,
        ]);
    }

    public function test_kompensasi_list_includes_the_periode_that_expired(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $riwayat = RiwayatSaldoCuti::factory()->create(['periode_ke' => 3]);
        KompensasiCuti::factory()->create([
            'karyawan_id' => $riwayat->karyawan_id,
            'jenis_cuti_id' => $riwayat->jenis_cuti_id,
            'riwayat_saldo_cuti_id' => $riwayat->id,
        ]);

        $response = $this->actingAs($hrd)->get(route('cuti.kompensasi.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('cuti/kompensasi/index')
            ->where('kompensasiCutis.data.0.riwayat_saldo_cuti.periode_ke', 3)
        );
    }

    public function test_karyawan_cannot_access_kompensasi_cuti_page(): void
    {
        $karyawan = $this->karyawanUser('karyawan');

        $response = $this->actingAs($karyawan)->get(route('cuti.kompensasi.index'));

        $response->assertForbidden();
    }

    public function test_bulk_process_rejects_everything_when_one_rate_is_missing(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $adaRate = KompensasiCuti::factory()->create();
        $tanpaRate = KompensasiCuti::factory()->create();

        $response = $this->actingAs($hrd)->post(route('cuti.kompensasi.proses-massal'), [
            'items' => [
                ['id' => $adaRate->id, 'rate_per_hari' => 100000],
                ['id' => $tanpaRate->id, 'rate_per_hari' => ''],
            ],
        ]);

        $response->assertSessionHasErrors('items.1.rate_per_hari');
        $this->assertDatabaseHas('kompensasi_cutis', ['id' => $adaRate->id, 'status' => 'menunggu_diproses']);
    }

    public function test_list_defaults_to_menunggu_and_can_switch_to_diproses(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $menunggu = KompensasiCuti::factory()->create();
        $diproses = KompensasiCuti::factory()->diproses()->create();

        $this->actingAs($hrd)->get(route('cuti.kompensasi.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('kompensasiCutis.data', 1)
                ->where('kompensasiCutis.data.0.id', $menunggu->id)
                ->where('jumlahMenunggu', 1)
                ->where('jumlahDiproses', 1));

        $this->actingAs($hrd)->get(route('cuti.kompensasi.index', ['status' => 'diproses']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('kompensasiCutis.data', 1)
                ->where('kompensasiCutis.data.0.id', $diproses->id));
    }
}
