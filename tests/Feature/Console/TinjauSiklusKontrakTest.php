<?php

namespace Tests\Feature\Console;

use App\Enums\StatusKaryawan;
use App\Enums\StatusKonfirmasiKontrak;
use App\Enums\TipeKaryawan;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\KonfirmasiKontrakCuti;
use App\Models\RiwayatSaldoCuti;
use App\Models\SaldoCuti;
use App\Services\PeriodeCutiService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class TinjauSiklusKontrakTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    private JenisCuti $cutiTahunan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->cutiTahunan = JenisCuti::factory()->create([
            'nama_jenis' => JenisCuti::NAMA_CUTI_TAHUNAN,
            'kuota_default' => 12,
            'masa_kerja_minimal_bulan' => 12,
        ]);
    }

    private function saldoBerjalan(int $periodeKe, TipeKaryawan $tipe = TipeKaryawan::Kontrak, int $terpakai = 4): SaldoCuti
    {
        $karyawan = Karyawan::factory()->create([
            'tipe_karyawan' => $tipe,
            'tanggal_akhir_kontrak' => $tipe === TipeKaryawan::Kontrak ? '2027-01-31' : null,
        ]);

        return SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $this->cutiTahunan->id,
            'periode_ke' => $periodeKe,
            'periode_mulai' => '2026-02-01',
            'periode_selesai' => '2027-01-31',
            'kuota' => 12,
            'terpakai' => $terpakai,
            'sisa' => 12 - $terpakai,
        ]);
    }

    public function test_command_queues_only_active_kontrak_past_k5_pointing_at_the_last_k5(): void
    {
        $lewatK5 = $this->saldoBerjalan(7);
        RiwayatSaldoCuti::factory()->create([
            'karyawan_id' => $lewatK5->karyawan_id,
            'jenis_cuti_id' => $this->cutiTahunan->id,
            'periode_ke' => 5,
            'periode_mulai' => '2024-02-01',
            'periode_selesai' => '2025-01-31',
        ]);
        $this->saldoBerjalan(7, TipeKaryawan::Tetap);
        $this->saldoBerjalan(5);
        $sudahMenunggu = $this->saldoBerjalan(6);
        KonfirmasiKontrakCuti::factory()->create(['karyawan_id' => $sudahMenunggu->karyawan_id, 'saldo_cuti_id' => $sudahMenunggu->id]);

        $this->artisan('kontrak:tinjau-siklus')->assertSuccessful();

        $tinjauan = KonfirmasiKontrakCuti::query()->where('tinjauan_siklus', true)->get();
        $this->assertCount(1, $tinjauan);
        $this->assertSame($lewatK5->id, $tinjauan->first()->saldo_cuti_id);
        $this->assertSame(5, $tinjauan->first()->periode_ke);
        $this->assertSame('2025-01-31', $tinjauan->first()->tanggal_batas->toDateString());
        $this->assertSame(StatusKonfirmasiKontrak::Menunggu, $tinjauan->first()->status);
    }

    public function test_simulasi_creates_nothing_and_rerun_does_not_requeue_a_reviewed_saldo(): void
    {
        $saldo = $this->saldoBerjalan(6);

        $this->artisan('kontrak:tinjau-siklus --simulasi')->assertSuccessful();
        $this->assertDatabaseCount('konfirmasi_kontrak_cutis', 0);

        $this->artisan('kontrak:tinjau-siklus')->assertSuccessful();
        KonfirmasiKontrakCuti::query()->where('saldo_cuti_id', $saldo->id)->update(['status' => StatusKonfirmasiKontrak::Diperpanjang]);
        $this->artisan('kontrak:tinjau-siklus')->assertSuccessful();

        $this->assertDatabaseCount('konfirmasi_kontrak_cutis', 1);
    }

    public function test_angkat_tetap_from_tinjauan_keeps_running_saldo_and_starts_cuti_besar_after_k5(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $cutiBesar = JenisCuti::factory()->create(['nama_jenis' => JenisCuti::NAMA_CUTI_BESAR, 'kuota_default' => 21, 'masa_kerja_minimal_bulan' => 60]);
        $saldo = $this->saldoBerjalan(6);
        $konfirmasi = app(PeriodeCutiService::class)->buatTinjauanSiklus($saldo);

        $this->actingAs($hrd)->post(route('cuti.konfirmasi-kontrak.konfirmasi', $konfirmasi), [
            'keputusan' => 'angkat_tetap',
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame(TipeKaryawan::Tetap, $saldo->karyawan->fresh()->tipe_karyawan);
        $this->assertNull($saldo->fresh()->ditutup_pada);
        $this->assertSame(8, $saldo->fresh()->sisa);
        $this->assertDatabaseMissing('saldo_cutis', ['karyawan_id' => $saldo->karyawan_id, 'periode_ke' => 7]);
        $saldoCutiBesar = SaldoCuti::query()->where('karyawan_id', $saldo->karyawan_id)->where('jenis_cuti_id', $cutiBesar->id)->firstOrFail();
        $this->assertSame('2026-02-01', $saldoCutiBesar->periode_mulai->toDateString());
    }

    public function test_kontrak_ulang_from_tinjauan_keeps_running_saldo_and_extends_contract(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $saldoK1 = $this->saldoBerjalan(6, terpakai: 4);
        $saldoK2 = $this->saldoBerjalan(7, terpakai: 4);
        $service = app(PeriodeCutiService::class);

        foreach ([$saldoK1, $saldoK2] as $saldo) {
            $this->actingAs($hrd)->post(route('cuti.konfirmasi-kontrak.konfirmasi', $service->buatTinjauanSiklus($saldo)), [
                'keputusan' => 'perpanjang',
                'tanggal_akhir_kontrak_baru' => '2027-01-31',
                'catatan' => 'Belum memenuhi syarat pengangkatan.',
            ])->assertSessionDoesntHaveErrors();
        }

        $saldoK1->refresh();
        $this->assertSame(12, $saldoK1->kuota);
        $this->assertSame(8, $saldoK1->sisa);
        $this->assertNull($saldoK1->ditutup_pada);
        $this->assertSame('2027-01-31', $saldoK1->karyawan->fresh()->tanggal_akhir_kontrak->toDateString());
        $this->assertSame(12, $saldoK2->fresh()->kuota);
        $this->assertSame(8, $saldoK2->fresh()->sisa);
    }

    public function test_tidak_diperpanjang_from_tinjauan_closes_running_saldo_and_compensates_remaining_days(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $saldo = $this->saldoBerjalan(6, terpakai: 4);
        $konfirmasi = app(PeriodeCutiService::class)->buatTinjauanSiklus($saldo);

        $this->actingAs($hrd)->post(route('cuti.konfirmasi-kontrak.konfirmasi', $konfirmasi), [
            'keputusan' => 'tidak_diperpanjang',
        ])->assertSessionDoesntHaveErrors();

        $this->assertNotNull($saldo->fresh()->ditutup_pada);
        $this->assertSame(StatusKaryawan::Nonaktif, $saldo->karyawan->fresh()->status);
        $this->assertDatabaseHas('riwayat_saldo_cutis', ['karyawan_id' => $saldo->karyawan_id, 'periode_ke' => 6]);
        $this->assertDatabaseHas('kompensasi_cutis', ['karyawan_id' => $saldo->karyawan_id, 'jumlah_hari' => 8]);
    }
}
