<?php

namespace Tests\Feature\Laporan;

use App\Models\AlasanCuti;
use App\Models\JadwalShift;
use App\Models\JenisCuti;
use App\Models\PengajuanCuti;
use App\Models\Shift;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Concerns\InteractsWithKaryawan;
use Tests\TestCase;

class LaporanCutiTest extends TestCase
{
    use InteractsWithKaryawan, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_lembur_summary_sums_jam_lembur_per_karyawan_within_filtered_period(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawan = $this->karyawanUser('karyawan');
        $shift = Shift::factory()->create();

        JadwalShift::factory()->create([
            'karyawan_id' => $karyawan->karyawan->id,
            'shift_id' => $shift->id,
            'tanggal' => '2030-01-05',
            'jam_lembur' => 2,
        ]);
        JadwalShift::factory()->create([
            'karyawan_id' => $karyawan->karyawan->id,
            'shift_id' => $shift->id,
            'tanggal' => '2030-01-10',
            'jam_lembur' => 1.5,
        ]);
        // Di luar rentang filter, tidak boleh ikut terjumlah.
        JadwalShift::factory()->create([
            'karyawan_id' => $karyawan->karyawan->id,
            'shift_id' => $shift->id,
            'tanggal' => '2030-02-01',
            'jam_lembur' => 5,
        ]);
        // Tidak lembur, tidak boleh muncul di ringkasan.
        JadwalShift::factory()->create([
            'karyawan_id' => $karyawan->karyawan->id,
            'shift_id' => $shift->id,
            'tanggal' => '2030-01-15',
            'jam_lembur' => null,
        ]);

        $response = $this->actingAs($hrd)->get(route('laporan.index', [
            'dari' => '2030-01-01',
            'sampai' => '2030-01-31',
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('lemburSummary.0.karyawan_id', $karyawan->karyawan->id)
            ->where('lemburSummary.0.total_jam_lembur', 3.5)
            ->has('lemburSummary', 1)
        );
    }

    public function test_karyawan_id_filters_pengajuan_to_a_single_employee(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $karyawanA = $this->karyawanUser('karyawan')->karyawan;
        $karyawanB = $this->karyawanUser('karyawan')->karyawan;

        $pengajuanA = PengajuanCuti::factory()->create(['karyawan_id' => $karyawanA->id]);
        PengajuanCuti::factory()->create(['karyawan_id' => $karyawanB->id]);

        $response = $this->actingAs($hrd)->get(route('laporan.index', [
            'karyawan_id' => $karyawanA->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('pengajuans.data', 1)
            ->where('pengajuans.data.0.id', $pengajuanA->id)
        );
    }

    public function test_jenis_cuti_id_filters_pengajuan_by_leave_type(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $cutiTahunan = JenisCuti::factory()->create();
        $cutiBesar = JenisCuti::factory()->create();

        $pengajuanTahunan = PengajuanCuti::factory()->create(['jenis_cuti_id' => $cutiTahunan->id]);
        PengajuanCuti::factory()->create(['jenis_cuti_id' => $cutiBesar->id]);

        $response = $this->actingAs($hrd)->get(route('laporan.index', [
            'jenis_cuti_id' => $cutiTahunan->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('pengajuans.data', 1)
            ->where('pengajuans.data.0.id', $pengajuanTahunan->id)
        );
    }

    public function test_pengajuan_rows_include_alasan_sub_alasan_and_alasan_mendadak(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $alasanCuti = AlasanCuti::factory()->create(['nama_alasan' => 'Menikah']);

        PengajuanCuti::factory()->create([
            'jenis_cuti_id' => $alasanCuti->jenis_cuti_id,
            'alasan_cuti_id' => $alasanCuti->id,
            'alasan' => 'Pernikahan sendiri',
            'is_mendadak' => true,
            'alasan_mendadak' => 'Tanggal akad dimajukan keluarga.',
        ]);

        $response = $this->actingAs($hrd)->get(route('laporan.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('pengajuans.data.0.alasan', 'Pernikahan sendiri')
            ->where('pengajuans.data.0.alasan_cuti.nama_alasan', 'Menikah')
            ->where('pengajuans.data.0.is_mendadak', true)
            ->where('pengajuans.data.0.alasan_mendadak', 'Tanggal akad dimajukan keluarga.')
        );
    }

    public function test_karyawan_cannot_access_laporan_page(): void
    {
        $karyawan = $this->karyawanUser('karyawan');

        $response = $this->actingAs($karyawan)->get(route('laporan.index'));

        $response->assertForbidden();
    }

    public function test_export_excel_colors_the_status_column_by_status(): void
    {
        $hrd = $this->karyawanUser('hrd');
        PengajuanCuti::factory()->disetujui()->create();

        $response = $this->actingAs($hrd)->get(route('laporan.export.excel'));

        $response->assertOk();
        $sheet = IOFactory::load($response->getFile()->getPathname())->getActiveSheet();

        $this->assertSame('FF4F46E5', $sheet->getStyle('A1')->getFill()->getStartColor()->getARGB());
        $this->assertSame('FFD1FAE5', $sheet->getStyle('G2')->getFill()->getStartColor()->getARGB());
        $this->assertSame('FF065F46', $sheet->getStyle('G2')->getFont()->getColor()->getARGB());
    }

    public function test_export_excel_includes_alasan_columns(): void
    {
        $hrd = $this->karyawanUser('hrd');
        $alasanCuti = AlasanCuti::factory()->create(['nama_alasan' => 'Menikah']);
        PengajuanCuti::factory()->create([
            'jenis_cuti_id' => $alasanCuti->jenis_cuti_id,
            'alasan_cuti_id' => $alasanCuti->id,
            'alasan' => 'Pernikahan sendiri',
            'is_mendadak' => true,
            'alasan_mendadak' => 'Tanggal akad dimajukan keluarga.',
        ]);

        $response = $this->actingAs($hrd)->get(route('laporan.export.excel'));

        $response->assertOk();
        $sheet = IOFactory::load($response->getFile()->getPathname())->getActiveSheet();

        $this->assertSame(
            ['Sub Alasan', 'Alasan', 'Mendadak', 'Alasan Mendadak'],
            $sheet->rangeToArray('I1:L1')[0],
        );
        $this->assertSame(
            ['Menikah', 'Pernikahan sendiri', 'Ya', 'Tanggal akad dimajukan keluarga.'],
            $sheet->rangeToArray('I2:L2')[0],
        );
    }

    public function test_pdf_view_shows_alasan_sub_alasan_and_alasan_mendadak(): void
    {
        $alasanCuti = AlasanCuti::factory()->create(['nama_alasan' => 'Menikah']);
        PengajuanCuti::factory()->create([
            'jenis_cuti_id' => $alasanCuti->jenis_cuti_id,
            'alasan_cuti_id' => $alasanCuti->id,
            'alasan' => 'Pernikahan sendiri',
            'is_mendadak' => true,
            'alasan_mendadak' => 'Tanggal akad dimajukan keluarga.',
        ]);

        $view = $this->view('laporan.cuti-pdf', [
            'pengajuans' => PengajuanCuti::query()->with(['karyawan.departemen', 'jenisCuti', 'alasanCuti'])->get(),
            'filters' => [],
        ]);

        $view->assertSeeInOrder(['Menikah', 'Pernikahan sendiri', 'Mendadak:', 'Tanggal akad dimajukan keluarga.']);
    }

    public function test_hrd_can_export_laporan_cuti_as_pdf(): void
    {
        $hrd = $this->karyawanUser('hrd');
        PengajuanCuti::factory()->create(['is_mendadak' => true, 'alasan_mendadak' => 'Sakit mendadak.']);

        $response = $this->actingAs($hrd)->get(route('laporan.export.pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
