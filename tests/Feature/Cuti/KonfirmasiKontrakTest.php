<?php

namespace Tests\Feature\Cuti;

use App\Enums\StatusKonfirmasiKontrak;
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
            'keputusan' => 'perpanjang',
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
            'keputusan' => 'perpanjang',
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

    public function test_konfirmasi_perpanjang_menolak_tanggal_akhir_kontrak_baru_sebelum_tanggal_batas(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = Karyawan::factory()->create(['tipe_karyawan' => TipeKaryawan::Kontrak]);
        $jenisCuti = JenisCuti::factory()->create(['kuota_default' => 12, 'masa_kerja_minimal_bulan' => 12]);
        $saldoCuti = SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'periode_ke' => 1,
        ]);
        $konfirmasi = KonfirmasiKontrakCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'saldo_cuti_id' => $saldoCuti->id,
            'tanggal_batas' => now()->subMonths(6),
        ]);

        $response = $this->actingAs($hrd)->post(route('cuti.konfirmasi-kontrak.konfirmasi', $konfirmasi), [
            'keputusan' => 'perpanjang',
            'tanggal_akhir_kontrak_baru' => now()->subMonths(6)->toDateString(),
        ]);

        $response->assertSessionHasErrors('tanggal_akhir_kontrak_baru');
    }

    public function test_konfirmasi_perpanjang_menerima_tanggal_lampau_untuk_konfirmasi_yang_tertunggak(): void
    {
        // Karyawan yang periodenya sudah lama lewat (mis. baru diproses
        // sekarang setelah menunggak beberapa bulan) tetap harus bisa
        // dikonfirmasi dengan tanggal kontrak baru yang secara historis
        // benar, walau tanggal itu sendiri sudah lewat dari hari ini.
        $hrd = $this->karyawanUser('hrd');
        $karyawan = Karyawan::factory()->create(['tipe_karyawan' => TipeKaryawan::Kontrak]);
        $jenisCuti = JenisCuti::factory()->create(['kuota_default' => 12, 'masa_kerja_minimal_bulan' => 12]);
        $saldoCuti = SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'periode_ke' => 1,
            'periode_mulai' => now()->subMonths(18),
            'periode_selesai' => now()->subMonths(6),
        ]);
        $konfirmasi = KonfirmasiKontrakCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'saldo_cuti_id' => $saldoCuti->id,
            'tanggal_batas' => now()->subMonths(6),
        ]);

        $tanggalBaruHistoris = now()->subMonths(3)->toDateString();

        $response = $this->actingAs($hrd)->post(route('cuti.konfirmasi-kontrak.konfirmasi', $konfirmasi), [
            'keputusan' => 'perpanjang',
            'tanggal_akhir_kontrak_baru' => $tanggalBaruHistoris,
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertSame($tanggalBaruHistoris, $karyawan->fresh()->tanggal_akhir_kontrak->toDateString());
    }

    public function test_konfirmasi_tidak_diperpanjang_tidak_wajib_mengisi_tanggal_akhir_kontrak_baru(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $konfirmasi = KonfirmasiKontrakCuti::factory()->create();

        $response = $this->actingAs($hrd)->post(route('cuti.konfirmasi-kontrak.konfirmasi', $konfirmasi), [
            'keputusan' => 'tidak_diperpanjang',
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

    public function test_perpanjang_massal_memperpanjang_tiap_karyawan_satu_tahun_dari_tanggal_batasnya_sendiri(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $pertama = $this->konfirmasiMenunggu('2026-09-30');
        $kedua = $this->konfirmasiMenunggu('2026-10-02');
        $tidakDipilih = $this->konfirmasiMenunggu('2026-10-01');

        $response = $this->actingAs($hrd)->post(route('cuti.konfirmasi-kontrak.perpanjang-massal'), [
            'konfirmasi_ids' => [$pertama->id, $kedua->id],
            'catatan' => 'Perpanjangan kontrak gelombang Oktober.',
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertSame('2027-09-30', $pertama->karyawan->fresh()->tanggal_akhir_kontrak->toDateString());
        $this->assertSame('2027-10-02', $kedua->karyawan->fresh()->tanggal_akhir_kontrak->toDateString());
        $this->assertDatabaseHas('konfirmasi_kontrak_cutis', ['id' => $pertama->id, 'status' => 'diperpanjang', 'dikonfirmasi_oleh_id' => $hrd->karyawan->id]);
        $this->assertDatabaseHas('konfirmasi_kontrak_cutis', ['id' => $tidakDipilih->id, 'status' => 'menunggu']);
    }

    public function test_perpanjang_massal_semua_memproses_seluruh_yang_menunggu_dan_melewati_yang_sudah_diproses(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $menunggu = $this->konfirmasiMenunggu('2026-09-30');
        $sudahDiproses = $this->konfirmasiMenunggu('2026-09-30');
        $sudahDiproses->update(['status' => StatusKonfirmasiKontrak::TidakDiperpanjang]);

        $this->actingAs($hrd)->post(route('cuti.konfirmasi-kontrak.perpanjang-massal'), [
            'semua' => true,
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('konfirmasi_kontrak_cutis', ['id' => $menunggu->id, 'status' => 'diperpanjang']);
        $this->assertDatabaseHas('konfirmasi_kontrak_cutis', ['id' => $sudahDiproses->id, 'status' => 'tidak_diperpanjang']);
    }

    public function test_perpanjang_massal_wajib_memilih_karyawan_kalau_tidak_semua(): void
    {
        $hrd = $this->karyawanUser('hrd');

        $response = $this->actingAs($hrd)->post(route('cuti.konfirmasi-kontrak.perpanjang-massal'), []);

        $response->assertSessionHasErrors('konfirmasi_ids');
    }

    public function test_kontrak_ulang_ke_k1_setelah_k5_wajib_beralasan(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $konfirmasi = $this->konfirmasiMenunggu('2026-09-30', periodeKe: 5);

        $response = $this->actingAs($hrd)->post(route('cuti.konfirmasi-kontrak.konfirmasi', $konfirmasi), [
            'keputusan' => 'perpanjang',
            'tanggal_akhir_kontrak_baru' => '2027-09-30',
        ]);

        $response->assertSessionHasErrors(['catatan' => 'Alasan wajib diisi untuk kontrak ulang ke K1 setelah K5.']);
        $this->assertSame(StatusKonfirmasiKontrak::Menunggu, $konfirmasi->fresh()->status);
    }

    public function test_kontrak_ulang_ke_k1_dengan_alasan_dimulai_dari_saldo_nol(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $konfirmasi = $this->konfirmasiMenunggu('2026-09-30', periodeKe: 5);

        $response = $this->actingAs($hrd)->post(route('cuti.konfirmasi-kontrak.konfirmasi', $konfirmasi), [
            'keputusan' => 'perpanjang',
            'tanggal_akhir_kontrak_baru' => '2027-09-30',
            'catatan' => 'Belum memenuhi syarat pengangkatan, kontrak ulang.',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertSame(StatusKonfirmasiKontrak::Diperpanjang, $konfirmasi->fresh()->status);
        $this->assertDatabaseHas('saldo_cutis', [
            'karyawan_id' => $konfirmasi->karyawan_id,
            'periode_ke' => 6,
            'kuota' => 0,
            'sisa' => 0,
        ]);
    }

    public function test_angkat_tetap_di_akhir_k5_melanjutkan_cuti_tahunan_penuh_dan_memulai_cuti_besar_sejak_pengangkatan(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $cutiBesar = JenisCuti::factory()->create(['nama_jenis' => JenisCuti::NAMA_CUTI_BESAR, 'kuota_default' => 21, 'masa_kerja_minimal_bulan' => 60]);
        $konfirmasi = $this->konfirmasiMenunggu('2026-09-30', periodeKe: 5);

        $response = $this->actingAs($hrd)->post(route('cuti.konfirmasi-kontrak.konfirmasi', $konfirmasi), [
            'keputusan' => 'angkat_tetap',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $karyawan = $konfirmasi->karyawan->fresh();
        $this->assertSame(TipeKaryawan::Tetap, $karyawan->tipe_karyawan);
        $this->assertNull($karyawan->tanggal_akhir_kontrak);
        $this->assertSame(StatusKonfirmasiKontrak::DiangkatTetap, $konfirmasi->fresh()->status);
        $this->assertDatabaseHas('saldo_cutis', [
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $konfirmasi->saldoCuti->jenis_cuti_id,
            'periode_ke' => 6,
            'kuota' => 12,
            'sisa' => 12,
        ]);

        $saldoCutiBesar = SaldoCuti::query()->where('karyawan_id', $karyawan->id)->where('jenis_cuti_id', $cutiBesar->id)->firstOrFail();
        $this->assertSame('2026-10-01', $saldoCutiBesar->periode_mulai->toDateString());
        $this->assertSame('2031-09-30', $saldoCutiBesar->periode_selesai->toDateString());
        $this->assertSame(0, $saldoCutiBesar->kuota);
    }

    public function test_perpanjang_massal_melewati_karyawan_di_akhir_k5(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $biasa = $this->konfirmasiMenunggu('2026-09-30', periodeKe: 2);
        $akhirK5 = $this->konfirmasiMenunggu('2026-09-30', periodeKe: 5);

        $response = $this->actingAs($hrd)->post(route('cuti.konfirmasi-kontrak.perpanjang-massal'), [
            'konfirmasi_ids' => [$biasa->id, $akhirK5->id],
        ]);

        $response->assertInertiaFlash(
            'toast.message',
            'Kontrak 1 karyawan berhasil diperpanjang 1 tahun. 1 karyawan di akhir K5 dilewati — putuskan satu per satu (angkat tetap atau kontrak ulang ke K1).',
        );
        $this->assertSame(StatusKonfirmasiKontrak::Diperpanjang, $biasa->fresh()->status);
        $this->assertSame(StatusKonfirmasiKontrak::Menunggu, $akhirK5->fresh()->status);
    }

    private function konfirmasiMenunggu(string $tanggalBatas, int $periodeKe = 1): KonfirmasiKontrakCuti
    {
        $karyawan = Karyawan::factory()->create([
            'tipe_karyawan' => TipeKaryawan::Kontrak,
            'tanggal_akhir_kontrak' => $tanggalBatas,
        ]);
        $saldoCuti = SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => JenisCuti::factory()->create(['kuota_default' => 12, 'masa_kerja_minimal_bulan' => 12])->id,
            'periode_ke' => $periodeKe,
            'periode_mulai' => '2025-10-01',
            'periode_selesai' => $tanggalBatas,
            'kuota' => 12,
            'terpakai' => 12,
            'sisa' => 0,
            'ditutup_pada' => now(),
        ]);

        return KonfirmasiKontrakCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'saldo_cuti_id' => $saldoCuti->id,
            'periode_ke' => $periodeKe,
            'tanggal_batas' => $tanggalBatas,
            'status' => StatusKonfirmasiKontrak::Menunggu,
        ]);
    }
}
