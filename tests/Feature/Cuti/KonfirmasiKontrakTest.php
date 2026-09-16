<?php

namespace Tests\Feature\Cuti;

use App\Enums\TipeKaryawan;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\KonfirmasiKontrakCuti;
use App\Models\SaldoCuti;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class KonfirmasiKontrakTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_konfirmasi_perpanjang_wajib_mengisi_tanggal_akhir_kontrak_baru(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $konfirmasi = KonfirmasiKontrakCuti::factory()->create();

        $response = $this->actingAs($hrd)->post(route('cuti.konfirmasi-kontrak.konfirmasi', $konfirmasi), [
            'diperpanjang' => true,
        ]);

        $response->assertSessionHasErrors('tanggal_akhir_kontrak_baru');
    }

    public function test_konfirmasi_perpanjang_memperbarui_tanggal_akhir_kontrak_karyawan(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = Karyawan::factory()->create([
            'tipe_karyawan' => TipeKaryawan::Kontrak,
            'tanggal_akhir_kontrak' => now()->addDays(3),
        ]);
        $jenisCuti = JenisCuti::factory()->create(['kuota_default' => 12, 'masa_kerja_minimal_bulan' => 12]);
        $saldoCuti = SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'periode_ke' => 1,
            'periode_mulai' => now()->subYear(),
            'periode_selesai' => now()->subDay(),
        ]);
        $konfirmasi = KonfirmasiKontrakCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'saldo_cuti_id' => $saldoCuti->id,
        ]);

        $tanggalBaru = now()->addYear()->toDateString();

        $response = $this->actingAs($hrd)->post(route('cuti.konfirmasi-kontrak.konfirmasi', $konfirmasi), [
            'diperpanjang' => true,
            'tanggal_akhir_kontrak_baru' => $tanggalBaru,
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertSame($tanggalBaru, $karyawan->fresh()->tanggal_akhir_kontrak->toDateString());
        $this->assertDatabaseHas('konfirmasi_kontrak_cutis', [
            'id' => $konfirmasi->id,
            'status' => 'diperpanjang',
        ]);
    }

    public function test_konfirmasi_tidak_diperpanjang_tidak_wajib_mengisi_tanggal_akhir_kontrak_baru(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $konfirmasi = KonfirmasiKontrakCuti::factory()->create();

        $response = $this->actingAs($hrd)->post(route('cuti.konfirmasi-kontrak.konfirmasi', $konfirmasi), [
            'diperpanjang' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('konfirmasi_kontrak_cutis', [
            'id' => $konfirmasi->id,
            'status' => 'tidak_diperpanjang',
        ]);
    }

    public function test_karyawan_cannot_access_konfirmasi_kontrak_page(): void
    {
        $karyawan = $this->karyawanUser('karyawan');

        $response = $this->actingAs($karyawan)->get(route('cuti.konfirmasi-kontrak.index'));

        $response->assertForbidden();
    }
}
