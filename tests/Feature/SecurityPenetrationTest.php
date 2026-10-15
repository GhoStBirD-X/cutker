<?php

namespace Tests\Feature;

use App\Enums\StatusPengajuan;
use App\Models\PengajuanCuti;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

/**
 * Pengujian penetrasi aktif: tiap test MENCOBA sebuah serangan nyata lewat
 * HTTP stack penuh (middleware + policy + DB), lalu memastikan serangan itu
 * gagal. Bukan uji alur normal — ini bukti pertahanan berfungsi.
 */
class SecurityPenetrationTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_idor_karyawan_tidak_bisa_melihat_pengajuan_milik_karyawan_lain(): void
    {
        $penyerang = $this->karyawanUser('karyawan');
        $korban = $this->karyawanUser('karyawan');
        $pengajuan = PengajuanCuti::factory()->create(['karyawan_id' => $korban->karyawan_id]);

        $this->actingAs($penyerang)
            ->get(route('cuti.show', $pengajuan))
            ->assertForbidden();
    }

    public function test_idor_karyawan_tidak_bisa_membatalkan_pengajuan_milik_karyawan_lain(): void
    {
        $penyerang = $this->karyawanUser('karyawan');
        $korban = $this->karyawanUser('karyawan');
        $pengajuan = PengajuanCuti::factory()->create(['karyawan_id' => $korban->karyawan_id]);

        $this->actingAs($penyerang)
            ->patch(route('cuti.batalkan', $pengajuan))
            ->assertForbidden();

        $this->assertDatabaseHas('pengajuan_cutis', [
            'id' => $pengajuan->id,
            'status' => StatusPengajuan::Pending->value,
        ]);
    }

    public function test_kepala_bagian_tidak_bisa_melihat_pengajuan_di_luar_departemennya(): void
    {
        $kabag = $this->karyawanUser('kepala_bagian');
        $karyawanLain = $this->karyawanUser('karyawan');
        $pengajuan = PengajuanCuti::factory()->create(['karyawan_id' => $karyawanLain->karyawan_id]);

        $this->actingAs($kabag)
            ->get(route('cuti.show', $pengajuan))
            ->assertForbidden();
    }

    public function test_karyawan_biasa_tidak_bisa_mengakses_rute_admin_kelola_user(): void
    {
        $penyerang = $this->karyawanUser('karyawan');

        $this->actingAs($penyerang)->get(route('users.index'))->assertForbidden();
        $this->actingAs($penyerang)->post(route('users.store'), [
            'name' => 'Penyusup',
            'email' => 'susup@pabrik.test',
            'password' => 'password123',
            'roles' => ['admin'],
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'susup@pabrik.test']);
    }

    public function test_eskalasi_hak_akses_lewat_update_profil_diabaikan(): void
    {
        $penyerang = $this->karyawanUser('karyawan');

        $this->actingAs($penyerang)->patch(route('profile.update'), [
            'name' => $penyerang->name,
            'email' => $penyerang->email,
            'roles' => ['admin'],
            'karyawan_id' => 99999,
        ])->assertRedirect();

        $penyerang->refresh();
        $this->assertFalse($penyerang->hasRole('admin'));
        $this->assertSame($penyerang->karyawan_id, $penyerang->karyawan_id);
    }

    public function test_idor_user_tidak_bisa_menandai_notifikasi_milik_orang_lain_sebagai_dibaca(): void
    {
        $penyerang = $this->karyawanUser('karyawan');
        $korban = $this->karyawanUser('karyawan');

        $notifikasi = new DatabaseNotification([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\Dummy',
            'notifiable_type' => $korban->getMorphClass(),
            'notifiable_id' => $korban->id,
            'data' => ['pesan' => 'rahasia'],
        ]);
        $notifikasi->save();

        $this->actingAs($penyerang)
            ->post(route('notifications.read', $notifikasi->id))
            ->assertRedirect();

        $this->assertNull($notifikasi->fresh()->read_at);
    }

    public function test_karyawan_tidak_bisa_memicu_reset_2fa_orang_lain(): void
    {
        $penyerang = $this->karyawanUser('karyawan');
        $korban = $this->karyawanUser('karyawan');
        $korban->forceFill([
            'two_factor_secret' => encrypt('secret'),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->actingAs($penyerang)
            ->post(route('users.reset-two-factor', $korban))
            ->assertForbidden();

        $this->assertTrue($korban->fresh()->hasEnabledTwoFactorAuthentication());
    }

    public function test_tamu_tanpa_login_ditolak_dari_endpoint_terlindungi(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('users.index'))->assertRedirect(route('login'));
        $this->get(route('laporan.index'))->assertRedirect(route('login'));
    }

    /**
     * @return list<string>
     */
    public static function ruteEkspor(): array
    {
        return [
            ['laporan.export.excel'],
            ['laporan.export.pdf'],
            ['laporan.saldo-cuti.export.excel'],
            ['laporan.saldo-cuti.export.pdf'],
            ['master.saldo-cuti.export'],
        ];
    }

    #[DataProvider('ruteEkspor')]
    public function test_tamu_tidak_bisa_mengunduh_ekspor(string $namaRute): void
    {
        $this->get(route($namaRute))->assertRedirect(route('login'));
    }

    #[DataProvider('ruteEkspor')]
    public function test_karyawan_biasa_tidak_bisa_mengunduh_ekspor(string $namaRute): void
    {
        $karyawan = $this->karyawanUser('karyawan');

        $this->actingAs($karyawan)->get(route($namaRute))->assertForbidden();
    }
}
