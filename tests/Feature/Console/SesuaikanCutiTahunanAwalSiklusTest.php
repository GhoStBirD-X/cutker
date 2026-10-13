<?php

namespace Tests\Feature\Console;

use App\Console\Commands\SesuaikanCutiTahunanAwalSiklus;
use App\Enums\TipeKaryawan;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\SaldoCuti;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SesuaikanCutiTahunanAwalSiklusTest extends TestCase
{
    use RefreshDatabase;

    private JenisCuti $cutiTahunan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cutiTahunan = JenisCuti::factory()->create([
            'nama_jenis' => JenisCuti::NAMA_CUTI_TAHUNAN,
            'kuota_default' => 12,
            'masa_kerja_minimal_bulan' => 12,
        ]);
    }

    private function saldo(int $periodeKe, TipeKaryawan $tipe = TipeKaryawan::Tetap, int $terpakai = 4, ?JenisCuti $jenisCuti = null): SaldoCuti
    {
        return SaldoCuti::factory()->create([
            'karyawan_id' => Karyawan::factory()->create(['tipe_karyawan' => $tipe])->id,
            'jenis_cuti_id' => ($jenisCuti ?? $this->cutiTahunan)->id,
            'periode_ke' => $periodeKe,
            'periode_mulai' => now()->subMonths(3),
            'periode_selesai' => now()->addMonths(9),
            'kuota' => 12,
            'terpakai' => $terpakai,
            'sisa' => 12 - $terpakai,
        ]);
    }

    public function test_tanpa_terapkan_hanya_menampilkan_daftar(): void
    {
        $saldo = $this->saldo(6);

        $this->artisan('cuti:sesuaikan-tahunan-awal-siklus')
            ->expectsOutputToContain('1 saldo ditemukan')
            ->assertSuccessful();

        $this->assertSame(12, $saldo->fresh()->kuota);
    }

    public function test_terapkan_menolkan_sisa_karyawan_tetap_di_periode_awal_siklus_dan_menghormati_yang_terpakai(): void
    {
        $periodeKeenam = $this->saldo(6);
        $periodeKesebelas = $this->saldo(11, terpakai: 0);
        $periodeKetujuh = $this->saldo(7);
        $kontrakK1Ulang = $this->saldo(6, TipeKaryawan::Kontrak);
        $cutiBesar = $this->saldo(6, jenisCuti: JenisCuti::factory()->create(['nama_jenis' => JenisCuti::NAMA_CUTI_BESAR]));

        $this->artisan('cuti:sesuaikan-tahunan-awal-siklus', ['--terapkan' => true])
            ->expectsOutputToContain('2 saldo Cuti Tahunan disesuaikan')
            ->assertSuccessful();

        $this->assertSame([4, 4, 0], [$periodeKeenam->fresh()->kuota, $periodeKeenam->fresh()->terpakai, $periodeKeenam->fresh()->sisa]);
        $this->assertSame(SesuaikanCutiTahunanAwalSiklus::CATATAN, $periodeKeenam->fresh()->catatan);
        $this->assertSame([0, 0], [$periodeKesebelas->fresh()->kuota, $periodeKesebelas->fresh()->sisa]);
        $this->assertSame(12, $periodeKetujuh->fresh()->kuota);
        $this->assertSame(12, $kontrakK1Ulang->fresh()->kuota);
        $this->assertSame(12, $cutiBesar->fresh()->kuota);
    }
}
