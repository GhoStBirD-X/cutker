<?php

namespace Tests\Feature\Master;

use App\Models\Departemen;
use App\Models\Jabatan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class KaryawanImportTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function csvFile(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('karyawan.csv', $content);
    }

    public function test_hrd_can_bulk_import_karyawan_from_csv(): void
    {
        $hrd = $this->karyawanUser('hrd');
        Departemen::factory()->create(['nama_departemen' => 'Produksi']);
        Jabatan::factory()->create(['nama_jabatan' => 'Operator Produksi']);

        $csv = "nip,nama,email,no_hp,jenis_kelamin,departemen,jabatan,tanggal_masuk,status,tipe_karyawan,tanggal_akhir_kontrak\n".
            "20250001,Budi Santoso,budi.santoso@pabrik.test,081234567890,laki_laki,Produksi,Operator Produksi,2019-03-01,aktif,tetap,\n";

        $response = $this->actingAs($hrd)->post(route('master.karyawan.import'), [
            'file' => $this->csvFile($csv),
        ]);

        $response->assertRedirect();
        $response->assertInertiaFlash('toast.type', 'success');
        $this->assertDatabaseHas('karyawans', [
            'nip' => '20250001',
            'email' => 'budi.santoso@pabrik.test',
        ]);
    }

    public function test_import_skips_row_with_unknown_departemen_but_keeps_valid_rows(): void
    {
        $hrd = $this->karyawanUser('hrd');
        Departemen::factory()->create(['nama_departemen' => 'Produksi']);
        Jabatan::factory()->create(['nama_jabatan' => 'Operator Produksi']);

        $csv = "nip,nama,email,no_hp,jenis_kelamin,departemen,jabatan,tanggal_masuk,status,tipe_karyawan,tanggal_akhir_kontrak\n".
            "20250001,Budi Santoso,budi.santoso@pabrik.test,081234567890,laki_laki,Produksi,Operator Produksi,2019-03-01,aktif,tetap,\n".
            "20250002,Siti Aminah,siti.aminah@pabrik.test,081298765432,perempuan,Departemen Tidak Ada,Operator Produksi,2020-01-01,aktif,tetap,\n";

        $response = $this->actingAs($hrd)->post(route('master.karyawan.import'), [
            'file' => $this->csvFile($csv),
        ]);

        $response->assertRedirect();
        $response->assertInertiaFlash('toast.type', 'error');
        $response->assertInertiaFlash('importFailures.0.row', 3);
        $this->assertDatabaseHas('karyawans', ['email' => 'budi.santoso@pabrik.test']);
        $this->assertDatabaseMissing('karyawans', ['email' => 'siti.aminah@pabrik.test']);
    }

    public function test_import_rejects_tipe_kontrak_without_tanggal_akhir_kontrak(): void
    {
        $hrd = $this->karyawanUser('hrd');
        Departemen::factory()->create(['nama_departemen' => 'Produksi']);
        Jabatan::factory()->create(['nama_jabatan' => 'Operator Produksi']);

        $csv = "nip,nama,email,no_hp,jenis_kelamin,departemen,jabatan,tanggal_masuk,status,tipe_karyawan,tanggal_akhir_kontrak\n".
            "20250003,Andi,andi@pabrik.test,081200000000,laki_laki,Produksi,Operator Produksi,2022-01-01,aktif,kontrak,\n";

        $response = $this->actingAs($hrd)->post(route('master.karyawan.import'), [
            'file' => $this->csvFile($csv),
        ]);

        $response->assertInertiaFlash('toast.type', 'error');
        $this->assertDatabaseMissing('karyawans', ['email' => 'andi@pabrik.test']);
    }

    public function test_import_creates_login_account_with_role_mapped_from_jabatan(): void
    {
        $hrd = $this->karyawanUser('hrd');
        Departemen::factory()->create(['nama_departemen' => 'Produksi']);
        Jabatan::factory()->create(['nama_jabatan' => 'Operator Produksi']);
        Jabatan::factory()->create(['nama_jabatan' => 'Kepala Bagian']);

        $csv = "nip,nama,email,no_hp,jenis_kelamin,departemen,jabatan,tanggal_masuk,status,tipe_karyawan,tanggal_akhir_kontrak\n".
            "20250001,Budi Santoso,budi.santoso@pabrik.test,081234567890,laki_laki,Produksi,Operator Produksi,2019-03-01,aktif,tetap,\n".
            "20250002,Dewi Lestari,dewi.lestari@pabrik.test,081234500002,perempuan,Produksi,Kepala Bagian,2019-03-01,aktif,tetap,\n";

        $response = $this->actingAs($hrd)->post(route('master.karyawan.import'), [
            'file' => $this->csvFile($csv),
        ]);

        $response->assertRedirect();
        $response->assertInertiaFlash('toast.type', 'success');
        $response->assertInertiaFlash('importKredensial.0.email', 'budi.santoso@pabrik.test');
        $response->assertInertiaFlash('importKredensial.0.role', 'karyawan');
        $response->assertInertiaFlash('importKredensial.1.role', 'kepala_bagian');

        $budi = User::query()->where('email', 'budi.santoso@pabrik.test')->firstOrFail();
        $this->assertTrue($budi->hasRole('karyawan'));
        $this->assertNotNull($budi->karyawan_id);

        $dewi = User::query()->where('email', 'dewi.lestari@pabrik.test')->firstOrFail();
        $this->assertTrue($dewi->hasRole('kepala_bagian'));
    }

    public function test_import_skips_row_when_email_already_used_by_existing_user_account(): void
    {
        $hrd = $this->karyawanUser('hrd');
        Departemen::factory()->create(['nama_departemen' => 'Produksi']);
        Jabatan::factory()->create(['nama_jabatan' => 'Operator Produksi']);

        User::factory()->create(['email' => 'sudah.ada@pabrik.test', 'password' => Hash::make('secret')]);

        $csv = "nip,nama,email,no_hp,jenis_kelamin,departemen,jabatan,tanggal_masuk,status,tipe_karyawan,tanggal_akhir_kontrak\n".
            "20250001,Budi Santoso,sudah.ada@pabrik.test,081234567890,laki_laki,Produksi,Operator Produksi,2019-03-01,aktif,tetap,\n";

        $response = $this->actingAs($hrd)->post(route('master.karyawan.import'), [
            'file' => $this->csvFile($csv),
        ]);

        $response->assertRedirect();
        $response->assertInertiaFlash('toast.type', 'error');
        $this->assertDatabaseMissing('karyawans', ['nip' => '20250001']);
    }

    public function test_import_template_download_includes_role_guide_sheet(): void
    {
        $hrd = $this->karyawanUser('hrd');

        $response = $this->actingAs($hrd)->get(route('master.karyawan.import.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_karyawan_cannot_access_import_route(): void
    {
        $karyawan = $this->karyawanUser('karyawan');

        $response = $this->actingAs($karyawan)->post(route('master.karyawan.import'), [
            'file' => $this->csvFile("nip\n1"),
        ]);

        $response->assertForbidden();
    }
}
