<?php

namespace Tests\Feature\Cuti;

use App\Enums\JenisKelamin;
use App\Models\JenisCuti;
use App\Models\SaldoCuti;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class PengajuanCutiFormTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_male_karyawan_does_not_see_gender_restricted_jenis_cuti_or_saldo(): void
    {
        $karyawan = $this->karyawanUser('karyawan', ['jenis_kelamin' => JenisKelamin::LakiLaki])->karyawan;

        $cutiHamil = JenisCuti::factory()->create(['khusus_gender' => JenisKelamin::Perempuan]);
        $cutiTahunan = JenisCuti::factory()->create(['khusus_gender' => null]);
        SaldoCuti::factory()->create(['karyawan_id' => $karyawan->id, 'jenis_cuti_id' => $cutiHamil->id]);
        SaldoCuti::factory()->create(['karyawan_id' => $karyawan->id, 'jenis_cuti_id' => $cutiTahunan->id]);

        $response = $this->actingAs($karyawan->user)->get(route('cuti.create'));

        $response->assertOk();
        $response->assertInertia(function (Assert $page) use ($cutiHamil, $cutiTahunan) {
            $jenisCutiIds = collect($page->toArray()['props']['jenisCutis'])->pluck('id');
            $saldoJenisCutiIds = collect($page->toArray()['props']['saldoCuti'])->pluck('jenis_cuti_id');

            $this->assertFalse($jenisCutiIds->contains($cutiHamil->id));
            $this->assertTrue($jenisCutiIds->contains($cutiTahunan->id));
            $this->assertFalse($saldoJenisCutiIds->contains($cutiHamil->id));
            $this->assertTrue($saldoJenisCutiIds->contains($cutiTahunan->id));
        });
    }

    public function test_female_karyawan_sees_gender_restricted_jenis_cuti_and_saldo(): void
    {
        $karyawan = $this->karyawanUser('karyawan', ['jenis_kelamin' => JenisKelamin::Perempuan])->karyawan;

        $cutiHamil = JenisCuti::factory()->create(['khusus_gender' => JenisKelamin::Perempuan]);
        SaldoCuti::factory()->create(['karyawan_id' => $karyawan->id, 'jenis_cuti_id' => $cutiHamil->id]);

        $response = $this->actingAs($karyawan->user)->get(route('cuti.create'));

        $response->assertOk();
        $response->assertInertia(function (Assert $page) use ($cutiHamil) {
            $jenisCutiIds = collect($page->toArray()['props']['jenisCutis'])->pluck('id');
            $saldoJenisCutiIds = collect($page->toArray()['props']['saldoCuti'])->pluck('jenis_cuti_id');

            $this->assertTrue($jenisCutiIds->contains($cutiHamil->id));
            $this->assertTrue($saldoJenisCutiIds->contains($cutiHamil->id));
        });
    }

    public function test_saldo_for_period_type_jenis_cuti_is_shown_even_when_period_started_in_a_previous_year(): void
    {
        $karyawan = $this->karyawanUser('karyawan')->karyawan;

        $cutiBesar = JenisCuti::factory()->create([
            'masa_kerja_minimal_bulan' => 60,
            'khusus_gender' => null,
        ]);

        $saldo = SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $cutiBesar->id,
            'tahun' => Carbon::now()->subYears(3)->year,
            'periode_ke' => 1,
            'kuota' => 21,
            'terpakai' => 0,
            'sisa' => 21,
        ]);

        $response = $this->actingAs($karyawan->user)->get(route('cuti.create'));

        $response->assertOk();
        $response->assertInertia(function (Assert $page) use ($saldo) {
            $saldoIds = collect($page->toArray()['props']['saldoCuti'])->pluck('id');

            $this->assertTrue($saldoIds->contains($saldo->id));
        });
    }
}
