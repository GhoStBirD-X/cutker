<?php

namespace Tests\Feature\JadwalShift;

use App\Models\JadwalShift;
use App\Models\Shift;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class JadwalShiftManagementTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_koordinator_shift_can_bulk_add_jadwal_for_own_departemen(): void
    {
        $koordinator = $this->karyawanUser('koordinator_shift');
        $rekanA = $this->karyawanUser('karyawan', ['departemen_id' => $koordinator->karyawan->departemen_id]);
        $rekanB = $this->karyawanUser('karyawan', ['departemen_id' => $koordinator->karyawan->departemen_id]);
        $shift = Shift::factory()->create();

        $response = $this->actingAs($koordinator)->post(route('jadwal-shift.store'), [
            'karyawan_ids' => [$rekanA->karyawan->id, $rekanB->karyawan->id],
            'shift_id' => $shift->id,
            'tanggal_mulai' => now()->addDay()->toDateString(),
            'tanggal_selesai' => now()->addDay()->toDateString(),
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('jadwal_shifts', ['karyawan_id' => $rekanA->karyawan->id]);
        $this->assertDatabaseHas('jadwal_shifts', ['karyawan_id' => $rekanB->karyawan->id]);
    }

    public function test_bulk_add_creates_one_jadwal_per_karyawan_per_day_in_range(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawanA = $this->karyawanUser('karyawan');
        $karyawanB = $this->karyawanUser('karyawan');
        $shift = Shift::factory()->create();

        $response = $this->actingAs($hrd)->post(route('jadwal-shift.store'), [
            'karyawan_ids' => [$karyawanA->karyawan->id, $karyawanB->karyawan->id],
            'shift_id' => $shift->id,
            'tanggal_mulai' => now()->addDay()->toDateString(),
            'tanggal_selesai' => now()->addDays(4)->toDateString(),
        ]);

        $response->assertRedirect();
        // 2 karyawan x 4 hari = 8 record
        $this->assertDatabaseCount('jadwal_shifts', 8);
    }

    public function test_bulk_add_overwrites_existing_jadwal_on_the_same_day_instead_of_duplicating(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = $this->karyawanUser('karyawan');
        $shiftLama = Shift::factory()->create();
        $shiftBaru = Shift::factory()->create();
        $tanggal = now()->addDay()->toDateString();

        JadwalShift::factory()->create([
            'karyawan_id' => $karyawan->karyawan->id,
            'shift_id' => $shiftLama->id,
            'tanggal' => $tanggal,
        ]);

        $response = $this->actingAs($hrd)->post(route('jadwal-shift.store'), [
            'karyawan_ids' => [$karyawan->karyawan->id],
            'shift_id' => $shiftBaru->id,
            'tanggal_mulai' => $tanggal,
            'tanggal_selesai' => $tanggal,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('jadwal_shifts', 1);
        $this->assertDatabaseHas('jadwal_shifts', ['karyawan_id' => $karyawan->karyawan->id, 'shift_id' => $shiftBaru->id]);
    }

    public function test_koordinator_shift_cannot_bulk_add_when_any_selected_karyawan_is_in_another_departemen(): void
    {
        $koordinatorProduksi = $this->karyawanUser('koordinator_shift');
        $rekanSatuDepartemen = $this->karyawanUser('karyawan', ['departemen_id' => $koordinatorProduksi->karyawan->departemen_id]);
        $karyawanGudang = $this->karyawanUser('karyawan');
        $shift = Shift::factory()->create();

        $response = $this->actingAs($koordinatorProduksi)->post(route('jadwal-shift.store'), [
            'karyawan_ids' => [$rekanSatuDepartemen->karyawan->id, $karyawanGudang->karyawan->id],
            'shift_id' => $shift->id,
            'tanggal_mulai' => now()->addDay()->toDateString(),
            'tanggal_selesai' => now()->addDay()->toDateString(),
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('jadwal_shifts', ['karyawan_id' => $rekanSatuDepartemen->karyawan->id]);
        $this->assertDatabaseMissing('jadwal_shifts', ['karyawan_id' => $karyawanGudang->karyawan->id]);
    }

    public function test_koordinator_shift_cannot_delete_jadwal_from_other_departemen(): void
    {
        $koordinatorProduksi = $this->karyawanUser('koordinator_shift');
        $karyawanGudang = $this->karyawanUser('karyawan');
        $jadwal = JadwalShift::factory()->create(['karyawan_id' => $karyawanGudang->karyawan->id]);

        $response = $this->actingAs($koordinatorProduksi)->delete(route('jadwal-shift.destroy', $jadwal));

        $response->assertForbidden();
        $this->assertDatabaseHas('jadwal_shifts', ['id' => $jadwal->id]);
    }

    public function test_date_range_longer_than_31_days_is_rejected(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = $this->karyawanUser('karyawan');
        $shift = Shift::factory()->create();

        $response = $this->actingAs($hrd)->post(route('jadwal-shift.store'), [
            'karyawan_ids' => [$karyawan->karyawan->id],
            'shift_id' => $shift->id,
            'tanggal_mulai' => now()->addDay()->toDateString(),
            'tanggal_selesai' => now()->addDays(40)->toDateString(),
        ]);

        $response->assertSessionHasErrors('tanggal_selesai');
    }

    public function test_hrd_can_set_jam_lembur_on_a_jadwal_shift(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = $this->karyawanUser('karyawan');
        $jadwal = JadwalShift::factory()->create(['karyawan_id' => $karyawan->karyawan->id]);

        $response = $this->actingAs($hrd)->patch(route('jadwal-shift.update-lembur', $jadwal), [
            'jam_lembur' => 2.5,
            'catatan_lembur' => 'Lembur tutup buku bulanan',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('jadwal_shifts', [
            'id' => $jadwal->id,
            'jam_lembur' => 2.5,
            'catatan_lembur' => 'Lembur tutup buku bulanan',
        ]);
    }

    public function test_koordinator_shift_can_set_jam_lembur_for_own_departemen(): void
    {
        $koordinator = $this->karyawanUser('koordinator_shift');
        $rekan = $this->karyawanUser('karyawan', ['departemen_id' => $koordinator->karyawan->departemen_id]);
        $jadwal = JadwalShift::factory()->create(['karyawan_id' => $rekan->karyawan->id]);

        $response = $this->actingAs($koordinator)->patch(route('jadwal-shift.update-lembur', $jadwal), [
            'jam_lembur' => 3,
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('jadwal_shifts', ['id' => $jadwal->id, 'jam_lembur' => 3]);
    }

    public function test_koordinator_shift_cannot_set_jam_lembur_for_other_departemen(): void
    {
        $koordinatorProduksi = $this->karyawanUser('koordinator_shift');
        $karyawanGudang = $this->karyawanUser('karyawan');
        $jadwal = JadwalShift::factory()->create(['karyawan_id' => $karyawanGudang->karyawan->id]);

        $response = $this->actingAs($koordinatorProduksi)->patch(route('jadwal-shift.update-lembur', $jadwal), [
            'jam_lembur' => 2,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('jadwal_shifts', ['id' => $jadwal->id, 'jam_lembur' => null]);
    }

    public function test_jam_lembur_above_12_hours_is_rejected(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = $this->karyawanUser('karyawan');
        $jadwal = JadwalShift::factory()->create(['karyawan_id' => $karyawan->karyawan->id]);

        $response = $this->actingAs($hrd)->patch(route('jadwal-shift.update-lembur', $jadwal), [
            'jam_lembur' => 13,
        ]);

        $response->assertSessionHasErrors('jam_lembur');
        $this->assertDatabaseHas('jadwal_shifts', ['id' => $jadwal->id, 'jam_lembur' => null]);
    }

    public function test_karyawan_without_jadwal_shift_permission_is_forbidden(): void
    {
        $karyawan = $this->karyawanUser('karyawan');
        $shift = Shift::factory()->create();

        $response = $this->actingAs($karyawan)->post(route('jadwal-shift.store'), [
            'karyawan_ids' => [$karyawan->karyawan->id],
            'shift_id' => $shift->id,
            'tanggal_mulai' => now()->addDay()->toDateString(),
            'tanggal_selesai' => now()->addDay()->toDateString(),
        ]);

        $response->assertForbidden();
    }

    public function test_kepala_bagian_can_view_jadwal_shift_across_all_departments(): void
    {
        $kepalaBagian = $this->karyawanUser('kepala_bagian');
        $karyawanDepartemenLain = $this->karyawanUser('karyawan');
        $shift = Shift::factory()->create();
        $tanggal = now()->startOfMonth()->addDays(2)->toDateString();

        JadwalShift::factory()->create([
            'karyawan_id' => $karyawanDepartemenLain->karyawan->id,
            'shift_id' => $shift->id,
            'tanggal' => $tanggal,
        ]);

        $response = $this->actingAs($kepalaBagian)->get(route('jadwal-shift.index', [
            'bulan' => now()->month,
            'tahun' => now()->year,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('jadwals', 1)
            ->where('jadwals.0.karyawan_id', $karyawanDepartemenLain->karyawan->id)
            ->where('karyawans', [])
        );
    }

    public function test_manager_can_view_jadwal_shift_across_all_departments(): void
    {
        $manager = $this->karyawanUser('manager');
        $karyawanDepartemenLain = $this->karyawanUser('karyawan');
        $shift = Shift::factory()->create();
        $tanggal = now()->startOfMonth()->addDays(2)->toDateString();

        JadwalShift::factory()->create([
            'karyawan_id' => $karyawanDepartemenLain->karyawan->id,
            'shift_id' => $shift->id,
            'tanggal' => $tanggal,
        ]);

        $response = $this->actingAs($manager)->get(route('jadwal-shift.index', [
            'bulan' => now()->month,
            'tahun' => now()->year,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('jadwals', 1)
            ->where('jadwals.0.karyawan_id', $karyawanDepartemenLain->karyawan->id)
            ->where('karyawans', [])
        );
    }

    public function test_kepala_bagian_cannot_create_jadwal_shift(): void
    {
        $kepalaBagian = $this->karyawanUser('kepala_bagian');
        $karyawan = $this->karyawanUser('karyawan');
        $shift = Shift::factory()->create();

        $response = $this->actingAs($kepalaBagian)->post(route('jadwal-shift.store'), [
            'karyawan_ids' => [$karyawan->karyawan->id],
            'shift_id' => $shift->id,
            'tanggal_mulai' => now()->addDay()->toDateString(),
            'tanggal_selesai' => now()->addDay()->toDateString(),
        ]);

        $response->assertForbidden();
    }
}
