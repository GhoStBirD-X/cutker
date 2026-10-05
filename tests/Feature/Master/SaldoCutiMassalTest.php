<?php

namespace Tests\Feature\Master;

use App\Models\Departemen;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\SaldoCuti;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class SaldoCutiMassalTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_index_filters_by_jenis_cuti_and_departemen(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $produksi = Departemen::factory()->create();
        $tahunan = JenisCuti::factory()->create();
        $cocok = SaldoCuti::factory()->create([
            'karyawan_id' => Karyawan::factory()->create(['departemen_id' => $produksi->id])->id,
            'jenis_cuti_id' => $tahunan->id,
        ]);
        SaldoCuti::factory()->create(['jenis_cuti_id' => $tahunan->id]);
        SaldoCuti::factory()->create(['karyawan_id' => $cocok->karyawan_id]);

        $response = $this->actingAs($hrd)->get(route('master.saldo-cuti.index', [
            'jenis_cuti_id' => $tahunan->id,
            'departemen_id' => $produksi->id,
        ]));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('saldoCutis.data', 1)
            ->where('saldoCutis.data.0.id', $cocok->id));
    }

    public function test_update_massal_saves_every_row_with_one_catatan(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $pertama = SaldoCuti::factory()->create(['kuota' => 12, 'terpakai' => 0, 'sisa' => 12]);
        $kedua = SaldoCuti::factory()->create(['kuota' => 12, 'terpakai' => 0, 'sisa' => 12]);

        $response = $this->actingAs($hrd)->put(route('master.saldo-cuti.update-massal'), [
            'perubahan' => [
                ['id' => $pertama->id, 'kuota' => 12, 'terpakai' => 4, 'sisa' => 8],
                ['id' => $kedua->id, 'kuota' => null, 'terpakai' => 0, 'sisa' => null],
            ],
            'catatan' => 'Migrasi saldo dari sistem lama.',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('saldo_cutis', ['id' => $pertama->id, 'terpakai' => 4, 'sisa' => 8, 'catatan' => 'Migrasi saldo dari sistem lama.', 'diubah_oleh_id' => $hrd->karyawan->id]);
        $this->assertDatabaseHas('saldo_cutis', ['id' => $kedua->id, 'kuota' => null, 'sisa' => null]);
    }

    public function test_update_massal_rejects_everything_when_one_row_is_invalid(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $valid = SaldoCuti::factory()->create(['kuota' => 12, 'terpakai' => 0, 'sisa' => 12]);
        $invalid = SaldoCuti::factory()->create(['kuota' => 12, 'terpakai' => 0, 'sisa' => 12]);

        $response = $this->actingAs($hrd)->put(route('master.saldo-cuti.update-massal'), [
            'perubahan' => [
                ['id' => $valid->id, 'kuota' => 12, 'terpakai' => 2, 'sisa' => 10],
                ['id' => $invalid->id, 'kuota' => 12, 'terpakai' => -1, 'sisa' => 13],
            ],
            'catatan' => 'Koreksi.',
        ]);

        $response->assertSessionHasErrors('perubahan.1.terpakai');
        $this->assertDatabaseHas('saldo_cutis', ['id' => $valid->id, 'terpakai' => 0]);
    }

    public function test_update_massal_requires_catatan(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $saldo = SaldoCuti::factory()->create();

        $response = $this->actingAs($hrd)->put(route('master.saldo-cuti.update-massal'), [
            'perubahan' => [['id' => $saldo->id, 'kuota' => 12, 'terpakai' => 1, 'sisa' => 11]],
        ]);

        $response->assertSessionHasErrors('catatan');
    }

    public function test_karyawan_cannot_use_update_massal(): void
    {
        $karyawan = $this->karyawanUser('karyawan');
        $saldo = SaldoCuti::factory()->create();

        $response = $this->actingAs($karyawan)->put(route('master.saldo-cuti.update-massal'), [
            'perubahan' => [['id' => $saldo->id, 'kuota' => 12, 'terpakai' => 1, 'sisa' => 11]],
            'catatan' => 'Coba-coba.',
        ]);

        $response->assertForbidden();
    }

    public function test_store_massal_creates_saldo_for_many_karyawan_and_skips_those_with_active_row(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $jenisCuti = JenisCuti::factory()->create(['masa_kerja_minimal_bulan' => null]);
        [$baru1, $baru2, $sudahPunya] = Karyawan::factory()->count(3)->create()->all();
        SaldoCuti::factory()->create(['karyawan_id' => $sudahPunya->id, 'jenis_cuti_id' => $jenisCuti->id, 'sisa' => 3]);

        $response = $this->actingAs($hrd)->post(route('master.saldo-cuti.store-massal'), [
            'karyawan_ids' => [$baru1->id, $baru2->id, $sudahPunya->id],
            'jenis_cuti_id' => $jenisCuti->id,
            'tahun' => now()->year,
            'kuota' => 12,
            'terpakai' => 2,
        ]);

        $response->assertSessionDoesntHaveErrors();
        foreach ([$baru1, $baru2] as $karyawan) {
            $this->assertDatabaseHas('saldo_cutis', ['karyawan_id' => $karyawan->id, 'jenis_cuti_id' => $jenisCuti->id, 'kuota' => 12, 'terpakai' => 2, 'sisa' => 10]);
        }
        $this->assertSame(1, SaldoCuti::query()->where('karyawan_id', $sudahPunya->id)->count());
    }

    public function test_store_massal_uses_each_karyawans_running_periode_for_periode_jenis_cuti(): void
    {
        Carbon::setTestNow('2026-10-05');
        $hrd = $this->karyawanUser('hrd');
        $jenisCuti = JenisCuti::factory()->create(['masa_kerja_minimal_bulan' => 12]);
        $lama = Karyawan::factory()->create(['tanggal_masuk' => '2023-01-10']);
        $baru = Karyawan::factory()->create(['tanggal_masuk' => '2026-03-01']);

        $this->actingAs($hrd)->post(route('master.saldo-cuti.store-massal'), [
            'karyawan_ids' => [$lama->id, $baru->id],
            'jenis_cuti_id' => $jenisCuti->id,
            'kuota' => 12,
            'terpakai' => 0,
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('saldo_cutis', ['karyawan_id' => $lama->id, 'periode_ke' => 4, 'periode_mulai' => '2026-01-10 00:00:00', 'periode_selesai' => '2027-01-09 00:00:00']);
        $this->assertDatabaseHas('saldo_cutis', ['karyawan_id' => $baru->id, 'periode_ke' => 1, 'periode_mulai' => '2026-03-01 00:00:00']);
    }

    public function test_store_massal_rejects_terpakai_above_kuota(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $jenisCuti = JenisCuti::factory()->create(['masa_kerja_minimal_bulan' => null]);

        $response = $this->actingAs($hrd)->post(route('master.saldo-cuti.store-massal'), [
            'karyawan_ids' => [Karyawan::factory()->create()->id],
            'jenis_cuti_id' => $jenisCuti->id,
            'tahun' => now()->year,
            'kuota' => 5,
            'terpakai' => 6,
        ]);

        $response->assertSessionHasErrors('terpakai');
    }

    public function test_pratinjau_aksi_massal_shows_before_after_without_saving(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $saldo = SaldoCuti::factory()->create(['kuota' => 12, 'terpakai' => 4, 'sisa' => 8]);
        $tanpaBatas = SaldoCuti::factory()->create(['kuota' => null, 'terpakai' => 0, 'sisa' => null]);

        $response = $this->actingAs($hrd)->getJson(route('master.saldo-cuti.aksi-massal.pratinjau', [
            'aksi' => 'tambah_kuota',
            'nilai' => 2,
            'ids' => [$saldo->id, $tanpaBatas->id],
        ]));

        $response->assertOk();
        $baris = collect($response->json('baris'))->keyBy('id');
        $this->assertSame(['kuota' => 14, 'terpakai' => 4, 'sisa' => 10], $baris[$saldo->id]['sesudah']);
        $this->assertNull($baris[$saldo->id]['galat']);
        $this->assertNotNull($baris[$tanpaBatas->id]['galat']);
        $this->assertDatabaseHas('saldo_cutis', ['id' => $saldo->id, 'kuota' => 12]);
    }

    public function test_aksi_massal_set_kuota_applies_to_all_filtered_rows_and_skips_invalid_ones(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $jenisCuti = JenisCuti::factory()->create();
        $aman = SaldoCuti::factory()->create(['jenis_cuti_id' => $jenisCuti->id, 'kuota' => 12, 'terpakai' => 3, 'sisa' => 9]);
        $jadiMinus = SaldoCuti::factory()->create(['jenis_cuti_id' => $jenisCuti->id, 'kuota' => 12, 'terpakai' => 11, 'sisa' => 1]);
        $jenisLain = SaldoCuti::factory()->create(['kuota' => 12, 'terpakai' => 0, 'sisa' => 12]);

        $response = $this->actingAs($hrd)->post(route('master.saldo-cuti.aksi-massal'), [
            'aksi' => 'set_kuota',
            'nilai' => 10,
            'semua' => true,
            'jenis_cuti_id' => $jenisCuti->id,
            'catatan' => 'Penyesuaian kebijakan.',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('saldo_cutis', ['id' => $aman->id, 'kuota' => 10, 'sisa' => 7, 'catatan' => 'Penyesuaian kebijakan.']);
        $this->assertDatabaseHas('saldo_cutis', ['id' => $jadiMinus->id, 'kuota' => 12, 'sisa' => 1]);
        $this->assertDatabaseHas('saldo_cutis', ['id' => $jenisLain->id, 'kuota' => 12]);
    }

    public function test_aksi_massal_hapus_only_deletes_selected_rows(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $dipilih = SaldoCuti::factory()->create();
        $tidakDipilih = SaldoCuti::factory()->create();

        $this->actingAs($hrd)->post(route('master.saldo-cuti.aksi-massal'), [
            'aksi' => 'hapus',
            'ids' => [$dipilih->id],
            'catatan' => 'Salah input.',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('saldo_cutis', ['id' => $dipilih->id]);
        $this->assertDatabaseHas('saldo_cutis', ['id' => $tidakDipilih->id]);
    }

    public function test_aksi_massal_requires_catatan(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $saldo = SaldoCuti::factory()->create();

        $response = $this->actingAs($hrd)->post(route('master.saldo-cuti.aksi-massal'), [
            'aksi' => 'set_kuota',
            'nilai' => 12,
            'ids' => [$saldo->id],
        ]);

        $response->assertSessionHasErrors('catatan');
    }

    public function test_export_downloads_filtered_saldo_as_excel(): void
    {
        $hrd = $this->karyawanUser('hrd');
        SaldoCuti::factory()->create();

        $response = $this->actingAs($hrd)->get(route('master.saldo-cuti.export'));

        $response->assertOk();
        $response->assertDownload('saldo-cuti-'.now()->format('Y-m-d').'.xlsx');
    }

    public function test_pratinjau_import_lists_changes_and_errors_without_saving(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $berubah = SaldoCuti::factory()->create(['kuota' => 12, 'terpakai' => 0, 'sisa' => 12]);
        $sama = SaldoCuti::factory()->create(['kuota' => 12, 'terpakai' => 1, 'sisa' => 11]);
        $nipSalah = SaldoCuti::factory()->create();

        $csv = implode("\n", [
            'id,nip,nama,departemen,jenis_cuti,periode,kuota,terpakai,sisa',
            "{$berubah->id},{$berubah->karyawan->nip},x,x,x,x,12,5,7",
            "{$sama->id},{$sama->karyawan->nip},x,x,x,x,12,1,11",
            "{$nipSalah->id},NIP-LAIN,x,x,x,x,12,1,11",
            '999999,123,x,x,x,x,12,abc,11',
        ]);

        $response = $this->actingAs($hrd)->post(route('master.saldo-cuti.import.pratinjau'), [
            'file' => UploadedFile::fake()->createWithContent('saldo.csv', $csv),
        ]);

        $response->assertRedirect();
        $response->assertInertiaFlash('importPratinjau.perubahan.0.id', $berubah->id);
        $response->assertInertiaFlash('importPratinjau.perubahan.0.sesudah', ['kuota' => 12, 'terpakai' => 5, 'sisa' => 7]);
        $response->assertInertiaFlash('importPratinjau.tidak_berubah', 1);
        $response->assertInertiaFlash('importPratinjau.failures.0.row', 4);
        $response->assertInertiaFlash('importPratinjau.failures.1.row', 5);
        $this->assertDatabaseHas('saldo_cutis', ['id' => $berubah->id, 'terpakai' => 0]);
    }
}
