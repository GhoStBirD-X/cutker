<?php

namespace Tests\Feature\Console;

use App\Enums\StatusKaryawan;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\SaldoCuti;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetSaldoCutiTahunanTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_saldo_for_every_active_employee_and_leave_type(): void
    {
        $karyawans = Karyawan::factory()->count(2)->create(['status' => StatusKaryawan::Aktif]);
        $jenisCutis = JenisCuti::factory()->count(3)->create();

        $this->artisan('cuti:reset-saldo-tahunan', ['--tahun' => 2030])
            ->assertExitCode(0);

        $this->assertDatabaseCount('saldo_cutis', 6);

        foreach ($karyawans as $karyawan) {
            foreach ($jenisCutis as $jenisCuti) {
                $this->assertDatabaseHas('saldo_cutis', [
                    'karyawan_id' => $karyawan->id,
                    'jenis_cuti_id' => $jenisCuti->id,
                    'tahun' => 2030,
                    'kuota' => $jenisCuti->kuota_default,
                    'terpakai' => 0,
                    'sisa' => $jenisCuti->kuota_default,
                ]);
            }
        }
    }

    public function test_generates_saldo_with_null_kuota_for_unlimited_leave_type(): void
    {
        $karyawan = Karyawan::factory()->create(['status' => StatusKaryawan::Aktif]);
        $jenisCuti = JenisCuti::factory()->create(['kuota_default' => null]);

        $this->artisan('cuti:reset-saldo-tahunan', ['--tahun' => 2030])
            ->assertExitCode(0);

        $this->assertDatabaseHas('saldo_cutis', [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'tahun' => 2030,
            'kuota' => null,
            'terpakai' => 0,
            'sisa' => null,
        ]);
    }

    public function test_skips_inactive_employees(): void
    {
        Karyawan::factory()->create(['status' => StatusKaryawan::Nonaktif]);
        JenisCuti::factory()->create();

        $this->artisan('cuti:reset-saldo-tahunan', ['--tahun' => 2030]);

        $this->assertDatabaseCount('saldo_cutis', 0);
    }

    public function test_skips_jenis_cuti_bertipe_periode(): void
    {
        Karyawan::factory()->create(['status' => StatusKaryawan::Aktif]);
        JenisCuti::factory()->create(['masa_kerja_minimal_bulan' => 12]);

        $this->artisan('cuti:reset-saldo-tahunan', ['--tahun' => 2030])->assertExitCode(0);

        $this->assertDatabaseCount('saldo_cutis', 0);
    }

    public function test_does_not_duplicate_existing_saldo_for_the_year(): void
    {
        $karyawan = Karyawan::factory()->create(['status' => StatusKaryawan::Aktif]);
        $jenisCuti = JenisCuti::factory()->create(['kuota_default' => 12]);

        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'tahun' => 2030,
            'kuota' => 12,
            'terpakai' => 5,
            'sisa' => 7,
        ]);

        $this->artisan('cuti:reset-saldo-tahunan', ['--tahun' => 2030]);

        $this->assertDatabaseCount('saldo_cutis', 1);
        $this->assertDatabaseHas('saldo_cutis', [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'tahun' => 2030,
            'terpakai' => 5,
            'sisa' => 7,
        ]);
    }
}
