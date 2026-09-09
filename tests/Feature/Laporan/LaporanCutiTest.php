<?php

namespace Tests\Feature\Laporan;

use App\Models\JadwalShift;
use App\Models\Shift;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class LaporanCutiTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_lembur_summary_sums_jam_lembur_per_karyawan_within_filtered_period(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = $this->karyawanUser('karyawan');
        $shift = Shift::factory()->create();

        JadwalShift::factory()->create([
            'karyawan_id' => $karyawan->karyawan->id,
            'shift_id' => $shift->id,
            'tanggal' => '2030-01-05',
            'jam_lembur' => 2,
        ]);
        JadwalShift::factory()->create([
            'karyawan_id' => $karyawan->karyawan->id,
            'shift_id' => $shift->id,
            'tanggal' => '2030-01-10',
            'jam_lembur' => 1.5,
        ]);
        // Di luar rentang filter, tidak boleh ikut terjumlah.
        JadwalShift::factory()->create([
            'karyawan_id' => $karyawan->karyawan->id,
            'shift_id' => $shift->id,
            'tanggal' => '2030-02-01',
            'jam_lembur' => 5,
        ]);
        // Tidak lembur, tidak boleh muncul di ringkasan.
        JadwalShift::factory()->create([
            'karyawan_id' => $karyawan->karyawan->id,
            'shift_id' => $shift->id,
            'tanggal' => '2030-01-15',
            'jam_lembur' => null,
        ]);

        $response = $this->actingAs($hrd)->get(route('laporan.index', [
            'dari' => '2030-01-01',
            'sampai' => '2030-01-31',
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('lemburSummary.0.karyawan_id', $karyawan->karyawan->id)
            ->where('lemburSummary.0.total_jam_lembur', 3.5)
            ->has('lemburSummary', 1)
        );
    }

    public function test_karyawan_cannot_access_laporan_page(): void
    {
        $karyawan = $this->karyawanUser('karyawan');

        $response = $this->actingAs($karyawan)->get(route('laporan.index'));

        $response->assertForbidden();
    }
}
