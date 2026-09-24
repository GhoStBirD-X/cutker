<?php

namespace Tests\Feature\Laporan;

use App\Enums\JenisKelamin;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\KonfirmasiKontrakCuti;
use App\Models\PengajuanCuti;
use App\Models\SaldoCuti;
use App\Services\PeriodeCutiService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class SaldoCutiTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function allowedRoles(): iterable
    {
        yield 'admin' => ['admin'];
        yield 'hrd' => ['hrd'];
        yield 'manager' => ['manager'];
        yield 'kepala_bagian' => ['kepala_bagian'];
    }

    #[DataProvider('allowedRoles')]
    public function test_allowed_role_can_view_karyawan_grouped_saldo_cuti(string $role): void
    {
        $user = $this->karyawanUser($role);
        $saldo = SaldoCuti::factory()->create(['sisa' => 8]);

        $response = $this->actingAs($user)->get(route('laporan.saldo-cuti', [
            'search' => $saldo->karyawan->nip,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('laporan/saldo-cuti')
            ->where('karyawans.data.0.id', $saldo->karyawan_id)
            ->where('karyawans.data.0.saldo_cutis.0.id', $saldo->id)
        );
    }

    public function test_karyawan_and_koordinator_shift_cannot_access_saldo_cuti_report(): void
    {
        $karyawan = $this->karyawanUser('karyawan');
        $koordinator = $this->karyawanUser('koordinator_shift');

        $this->actingAs($karyawan)->get(route('laporan.saldo-cuti'))->assertForbidden();
        $this->actingAs($koordinator)->get(route('laporan.saldo-cuti'))->assertForbidden();
    }

    public function test_search_filters_by_karyawan_name(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $cocok = Karyawan::factory()->create(['nama' => 'Budi Santoso']);
        Karyawan::factory()->create(['nama' => 'Siti Aminah']);

        $response = $this->actingAs($hrd)->get(route('laporan.saldo-cuti', ['search' => 'Budi']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('karyawans.data', 1)
            ->where('karyawans.data.0.id', $cocok->id)
        );
    }

    public function test_karyawan_without_active_saldo_still_lists_with_empty_children(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = Karyawan::factory()->create();

        $response = $this->actingAs($hrd)->get(route('laporan.saldo-cuti', [
            'search' => $karyawan->nip,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('karyawans.data', 1)
            ->where('karyawans.data.0.saldo_cutis', [])
        );
    }

    public function test_status_kontrak_is_kt_for_permanent_employee(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = Karyawan::factory()->create();

        $response = $this->actingAs($hrd)->get(route('laporan.saldo-cuti', [
            'search' => $karyawan->nip,
        ]));

        $response->assertInertia(fn ($page) => $page
            ->where('karyawans.data.0.status_kontrak', 'KT')
        );
    }

    public function test_status_kontrak_reflects_periode_ke_of_cuti_tahunan_for_contract_employee(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = Karyawan::factory()->kontrak()->create();
        $cutiTahunan = JenisCuti::factory()->create(['nama_jenis' => 'Cuti Tahunan']);

        SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $cutiTahunan->id,
            'periode_ke' => 2,
        ]);

        $response = $this->actingAs($hrd)->get(route('laporan.saldo-cuti', [
            'search' => $karyawan->nip,
        ]));

        $response->assertInertia(fn ($page) => $page
            ->where('karyawans.data.0.status_kontrak', 'K2')
        );
    }

    public function test_status_kontrak_becomes_k2_after_contract_renewal_confirmed(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = Karyawan::factory()->kontrak()->create();
        $penyetuju = Karyawan::factory()->create();
        $cutiTahunan = JenisCuti::factory()->create([
            'nama_jenis' => 'Cuti Tahunan',
            'kuota_default' => 12,
            'masa_kerja_minimal_bulan' => 12,
        ]);

        $saldoK1 = SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $cutiTahunan->id,
            'periode_ke' => 1,
            'periode_mulai' => now()->subYear(),
            'periode_selesai' => now()->subDay(),
            'kuota' => 12,
            'terpakai' => 2,
            'sisa' => 10,
        ]);

        $periodeCutiService = app(PeriodeCutiService::class);

        // Sebelum konfirmasi perpanjangan: masih K1.
        $response = $this->actingAs($hrd)->get(route('laporan.saldo-cuti', ['search' => $karyawan->nip]));
        $response->assertInertia(fn ($page) => $page->where('karyawans.data.0.status_kontrak', 'K1'));

        $periodeCutiService->tutupPeriode($saldoK1->fresh());
        $konfirmasiK1 = KonfirmasiKontrakCuti::query()->where('karyawan_id', $karyawan->id)->firstOrFail();
        $periodeCutiService->konfirmasiPerpanjangan($konfirmasiK1, $penyetuju, true, 'Kontrak diperpanjang.');

        // Setelah diperpanjang: periode_ke aktif jadi 2, jadi K2.
        $response = $this->actingAs($hrd)->get(route('laporan.saldo-cuti', ['search' => $karyawan->nip]));
        $response->assertInertia(fn ($page) => $page->where('karyawans.data.0.status_kontrak', 'K2'));

        // Tidak ada batas keras — perpanjangan kedua harus jalan mulus ke K3.
        $saldoK2 = SaldoCuti::query()
            ->where('karyawan_id', $karyawan->id)
            ->where('jenis_cuti_id', $cutiTahunan->id)
            ->where('periode_ke', 2)
            ->firstOrFail();
        $saldoK2->update(['periode_selesai' => now()->subDay()]);

        $periodeCutiService->tutupPeriode($saldoK2->fresh());
        $konfirmasiK2 = KonfirmasiKontrakCuti::query()->where('karyawan_id', $karyawan->id)->where('periode_ke', 2)->firstOrFail();
        $periodeCutiService->konfirmasiPerpanjangan($konfirmasiK2, $penyetuju, true, 'Kontrak diperpanjang lagi.');

        $response = $this->actingAs($hrd)->get(route('laporan.saldo-cuti', ['search' => $karyawan->nip]));
        $response->assertInertia(fn ($page) => $page->where('karyawans.data.0.status_kontrak', 'K3'));
    }

    public function test_riwayat_shows_only_approved_pengajuan_within_saldo_period(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = Karyawan::factory()->create();
        $jenisCuti = JenisCuti::factory()->create(['nama_jenis' => 'Cuti Tahunan']);

        $saldo = SaldoCuti::factory()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'periode_ke' => 1,
            'periode_mulai' => '2026-01-01',
            'periode_selesai' => '2026-12-31',
        ]);

        $dalamPeriode = PengajuanCuti::factory()->disetujui()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => '2026-03-01',
            'tanggal_selesai' => '2026-03-02',
        ]);

        $diLuarPeriode = PengajuanCuti::factory()->disetujui()->create([
            'karyawan_id' => $karyawan->id,
            'jenis_cuti_id' => $jenisCuti->id,
            'tanggal_mulai' => '2027-01-10',
            'tanggal_selesai' => '2027-01-11',
        ]);

        $response = $this->actingAs($hrd)->get(route('laporan.saldo-cuti.riwayat', $saldo));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('laporan/saldo-cuti-riwayat')
            ->has('pengajuans', 1)
            ->where('pengajuans.0.id', $dalamPeriode->id)
        );
    }

    public function test_karyawan_cannot_access_riwayat_saldo_cuti(): void
    {
        $karyawan = $this->karyawanUser('karyawan');
        $saldo = SaldoCuti::factory()->create();

        $this->actingAs($karyawan)->get(route('laporan.saldo-cuti.riwayat', $saldo))->assertForbidden();
    }

    public function test_export_excel_downloads_only_saldo_matching_the_active_filters(): void
    {
        Excel::fake();

        $hrd = $this->karyawanUser('hrd');
        $cocok = Karyawan::factory()->create(['nama' => 'Budi Santoso']);
        SaldoCuti::factory()->create(['karyawan_id' => $cocok->id]);
        $tidakCocok = Karyawan::factory()->create(['nama' => 'Siti Aminah']);
        SaldoCuti::factory()->create(['karyawan_id' => $tidakCocok->id]);

        $response = $this->actingAs($hrd)->get(route('laporan.saldo-cuti.export.excel', ['search' => 'Budi']));

        $response->assertOk();
        Excel::assertDownloaded('saldo-cuti-'.now()->format('Y-m-d').'.xlsx', function ($export) use ($cocok, $tidakCocok) {
            $karyawanIds = $export->query()->pluck('karyawan_id');

            return $karyawanIds->contains($cocok->id) && ! $karyawanIds->contains($tidakCocok->id);
        });
    }

    public function test_karyawan_cannot_export_saldo_cuti_excel(): void
    {
        $karyawan = $this->karyawanUser('karyawan');

        $this->actingAs($karyawan)->get(route('laporan.saldo-cuti.export.excel'))->assertForbidden();
    }

    public function test_export_excel_colors_the_sisa_column_by_severity(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = Karyawan::factory()->create();
        SaldoCuti::factory()->create(['karyawan_id' => $karyawan->id, 'kuota' => 12, 'terpakai' => 15, 'sisa' => -3]);

        $response = $this->actingAs($hrd)->get(route('laporan.saldo-cuti.export.excel'));

        $response->assertOk();
        $sheet = IOFactory::load($response->getFile()->getPathname())->getActiveSheet();

        $this->assertSame('FF4F46E5', $sheet->getStyle('A1')->getFill()->getStartColor()->getARGB());
        $this->assertSame('FFFEE2E2', $sheet->getStyle('H2')->getFill()->getStartColor()->getARGB());
        $this->assertSame('FF991B1B', $sheet->getStyle('H2')->getFont()->getColor()->getARGB());
    }

    public function test_hrd_can_export_saldo_cuti_as_pdf(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = Karyawan::factory()->create();
        SaldoCuti::factory()->create(['karyawan_id' => $karyawan->id]);

        $response = $this->actingAs($hrd)->get(route('laporan.saldo-cuti.export.pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'saldo-cuti-'.now()->format('Y-m-d').'.pdf',
            $response->headers->get('content-disposition'),
        );
    }

    public function test_export_pdf_excludes_a_leave_type_not_relevant_to_the_employee_gender(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = Karyawan::factory()->lakiLaki()->create();
        $cutiHamil = JenisCuti::factory()->create(['nama_jenis' => 'Cuti Hamil', 'khusus_gender' => JenisKelamin::Perempuan]);
        SaldoCuti::factory()->create(['karyawan_id' => $karyawan->id, 'jenis_cuti_id' => $cutiHamil->id]);

        $response = $this->actingAs($hrd)->get(route('laporan.saldo-cuti.export.pdf'));

        $response->assertOk();
    }

    public function test_karyawan_cannot_export_saldo_cuti_pdf(): void
    {
        $karyawan = $this->karyawanUser('karyawan');

        $this->actingAs($karyawan)->get(route('laporan.saldo-cuti.export.pdf'))->assertForbidden();
    }
}
