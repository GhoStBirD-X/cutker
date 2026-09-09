<?php

namespace Tests\Feature\Cuti;

use App\Enums\StatusKaryawan;
use App\Enums\StatusKonfirmasiKontrak;
use App\Enums\TipeKaryawan;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\KonfirmasiKontrakCuti;
use App\Models\SaldoCuti;
use App\Services\PeriodeCutiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodeCutiSiklusTest extends TestCase
{
    use RefreshDatabase;

    public function test_tutup_periode_untuk_karyawan_tetap_langsung_reset_dan_membuat_kompensasi(): void
    {
        $karyawan = Karyawan::factory()->create(['tipe_karyawan' => TipeKaryawan::Tetap]);
        $jenisCuti = JenisCuti::factory()->create(['kuota_default' => 12, 'masa_kerja_minimal_bulan' => 12]);

        $saldoLama = SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'periode_ke' => 1,
            'periode_mulai' => now()->subYear(),
            'periode_selesai' => now()->subDay(),
            'kuota' => 12,
            'terpakai' => 4,
            'sisa' => 8,
        ]);

        app(PeriodeCutiService::class)->tutupPeriode($saldoLama->fresh());

        $this->assertDatabaseHas('riwayat_saldo_cutis', [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'periode_ke' => 1,
            'sisa' => 8,
        ]);
        $this->assertDatabaseHas('saldo_cutis', [
            'id' => $saldoLama->id,
        ]);
        $this->assertNotNull($saldoLama->fresh()->ditutup_pada);
        $this->assertDatabaseHas('saldo_cutis', [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'periode_ke' => 2,
            'kuota' => 12,
            'sisa' => 12,
            'ditutup_pada' => null,
        ]);
        $this->assertDatabaseHas('kompensasi_cutis', [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'jumlah_hari' => 8,
            'status' => 'menunggu_diproses',
        ]);
        $this->assertDatabaseCount('konfirmasi_kontrak_cutis', 0);
    }

    public function test_tutup_periode_untuk_karyawan_kontrak_menahan_dan_tidak_membuat_kompensasi(): void
    {
        $karyawan = Karyawan::factory()->create([
            'tipe_karyawan' => TipeKaryawan::Kontrak,
            'tanggal_akhir_kontrak' => now()->addMonths(2),
        ]);
        $jenisCuti = JenisCuti::factory()->create(['kuota_default' => 12, 'masa_kerja_minimal_bulan' => 12]);

        $saldoLama = SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'periode_ke' => 1,
            'periode_mulai' => now()->subYear(),
            'periode_selesai' => now()->subDay(),
            'kuota' => 12,
            'terpakai' => 4,
            'sisa' => 8,
        ]);

        app(PeriodeCutiService::class)->tutupPeriode($saldoLama->fresh());

        $this->assertNotNull($saldoLama->fresh()->ditutup_pada);
        $this->assertDatabaseMissing('saldo_cutis', ['karyawan_id' => $karyawan->id, 'periode_ke' => 2]);
        $this->assertDatabaseCount('kompensasi_cutis', 0);
        $this->assertDatabaseHas('konfirmasi_kontrak_cutis', [
            'karyawan_id' => $karyawan->id,
            'saldo_cuti_id' => $saldoLama->id,
            'periode_ke' => 1,
            'status' => 'menunggu',
        ]);
    }

    public function test_konfirmasi_diperpanjang_melanjutkan_periode_dan_membuat_kompensasi(): void
    {
        $karyawan = Karyawan::factory()->create(['tipe_karyawan' => TipeKaryawan::Kontrak]);
        $hrd = Karyawan::factory()->create();
        $jenisCuti = JenisCuti::factory()->create(['kuota_default' => 12, 'masa_kerja_minimal_bulan' => 12]);

        $saldoLama = SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'periode_ke' => 1,
            'periode_mulai' => now()->subYear(),
            'periode_selesai' => now()->subDay(),
            'kuota' => 12,
            'terpakai' => 2,
            'sisa' => 10,
        ]);

        $periodeCutiService = app(PeriodeCutiService::class);
        $periodeCutiService->tutupPeriode($saldoLama->fresh());
        $konfirmasi = KonfirmasiKontrakCuti::query()->where('karyawan_id', $karyawan->id)->firstOrFail();

        $periodeCutiService->konfirmasiPerpanjangan($konfirmasi, $hrd, true, 'Kontrak diperpanjang 1 tahun.');

        $this->assertDatabaseHas('konfirmasi_kontrak_cutis', [
            'id' => $konfirmasi->id,
            'status' => StatusKonfirmasiKontrak::Diperpanjang->value,
            'dikonfirmasi_oleh_id' => $hrd->id,
        ]);
        $this->assertDatabaseHas('saldo_cutis', [
            'karyawan_id' => $karyawan->id,
            'periode_ke' => 2,
            'sisa' => 12,
        ]);
        $this->assertDatabaseHas('kompensasi_cutis', [
            'karyawan_id' => $karyawan->id,
            'jumlah_hari' => 10,
        ]);
        $this->assertSame(StatusKaryawan::Aktif, $karyawan->fresh()->status);
    }

    public function test_konfirmasi_tidak_diperpanjang_membuat_kompensasi_akhir_dan_menonaktifkan_karyawan(): void
    {
        $karyawan = Karyawan::factory()->create(['tipe_karyawan' => TipeKaryawan::Kontrak]);
        $hrd = Karyawan::factory()->create();
        $jenisCuti = JenisCuti::factory()->create(['kuota_default' => 12, 'masa_kerja_minimal_bulan' => 12]);

        $saldoLama = SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'periode_ke' => 1,
            'periode_mulai' => now()->subYear(),
            'periode_selesai' => now()->subDay(),
            'kuota' => 12,
            'terpakai' => 9,
            'sisa' => 3,
        ]);

        $periodeCutiService = app(PeriodeCutiService::class);
        $periodeCutiService->tutupPeriode($saldoLama->fresh());
        $konfirmasi = KonfirmasiKontrakCuti::query()->where('karyawan_id', $karyawan->id)->firstOrFail();

        $periodeCutiService->konfirmasiPerpanjangan($konfirmasi, $hrd, false, 'Kontrak tidak diperpanjang.');

        $this->assertDatabaseHas('konfirmasi_kontrak_cutis', [
            'id' => $konfirmasi->id,
            'status' => StatusKonfirmasiKontrak::TidakDiperpanjang->value,
        ]);
        $this->assertDatabaseMissing('saldo_cutis', ['karyawan_id' => $karyawan->id, 'periode_ke' => 2]);
        $this->assertDatabaseHas('kompensasi_cutis', [
            'karyawan_id' => $karyawan->id,
            'jumlah_hari' => 3,
        ]);
        $this->assertSame(StatusKaryawan::Nonaktif, $karyawan->fresh()->status);
    }

    public function test_proses_semua_karyawan_mengejar_beberapa_periode_yang_terlewat_sekaligus(): void
    {
        $karyawan = Karyawan::factory()->create([
            'tipe_karyawan' => TipeKaryawan::Tetap,
            'tanggal_masuk' => now()->subYears(3),
        ]);
        $jenisCuti = JenisCuti::factory()->create(['kuota_default' => 12, 'masa_kerja_minimal_bulan' => 12]);

        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'periode_ke' => 1,
            'periode_mulai' => $karyawan->tanggal_masuk,
            'periode_selesai' => $karyawan->tanggal_masuk->copy()->addYear()->subDay(),
            'kuota' => 12,
            'terpakai' => 0,
            'sisa' => 12,
        ]);

        $diproses = app(PeriodeCutiService::class)->prosesSemuaKaryawan();

        $this->assertGreaterThanOrEqual(2, $diproses);
        $this->assertDatabaseHas('saldo_cutis', [
            'karyawan_id' => $karyawan->id,
            'periode_ke' => 4,
            'ditutup_pada' => null,
        ]);
    }
}
