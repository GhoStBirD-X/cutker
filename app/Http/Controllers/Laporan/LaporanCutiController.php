<?php

namespace App\Http\Controllers\Laporan;

use App\Exports\PengajuanCutiExport;
use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\JadwalShift;
use App\Models\PengajuanCuti;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaporanCutiController extends Controller
{
    use HasPerPage;

    public function index(Request $request): Response
    {
        $pengajuans = $this->filteredQuery($request)->latest('tanggal_pengajuan')->paginate($this->resolvePerPage($request, 15))->withQueryString();

        return Inertia::render('laporan/index', [
            'pengajuans' => $pengajuans,
            'lemburSummary' => $this->lemburSummary($request),
            'departemens' => Departemen::all(),
            'filters' => $this->filters($request),
        ]);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $nama = 'laporan-cuti-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new PengajuanCutiExport($this->filteredQuery($request)), $nama);
    }

    public function exportPdf(Request $request): HttpResponse
    {
        $pengajuans = $this->filteredQuery($request)->latest('tanggal_pengajuan')->get();

        $pdf = Pdf::loadView('laporan.cuti-pdf', [
            'pengajuans' => $pengajuans,
            'filters' => $this->filters($request),
        ]);

        return $pdf->download('laporan-cuti-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * @return Builder<PengajuanCuti>
     */
    protected function filteredQuery(Request $request): Builder
    {
        $filters = $this->filters($request);

        return PengajuanCuti::query()
            ->with(['karyawan.departemen', 'jenisCuti'])
            ->when($filters['departemen_id'], fn ($query) => $query->whereHas('karyawan', fn ($q) => $q->where('departemen_id', $filters['departemen_id'])))
            ->when($filters['dari'], fn ($query) => $query->whereDate('tanggal_mulai', '>=', $filters['dari']))
            ->when($filters['sampai'], fn ($query) => $query->whereDate('tanggal_selesai', '<=', $filters['sampai']));
    }

    /**
     * Total jam lembur per karyawan pada periode & departemen yang sama
     * dengan filter laporan cuti (dari/sampai dicocokkan ke tanggal jadwal).
     *
     * @return Collection<int, array{karyawan_id: int, nama: string, departemen: string, total_jam_lembur: float}>
     */
    protected function lemburSummary(Request $request): Collection
    {
        $filters = $this->filters($request);

        return JadwalShift::query()
            ->with('karyawan.departemen')
            ->whereNotNull('jam_lembur')
            ->when($filters['departemen_id'], fn ($query) => $query->whereHas('karyawan', fn ($q) => $q->where('departemen_id', $filters['departemen_id'])))
            ->when($filters['dari'], fn ($query) => $query->whereDate('tanggal', '>=', $filters['dari']))
            ->when($filters['sampai'], fn ($query) => $query->whereDate('tanggal', '<=', $filters['sampai']))
            ->get()
            ->groupBy('karyawan_id')
            ->map($this->ringkasLembur(...))
            ->sortBy('nama')
            ->values();
    }

    /**
     * @param  Collection<int, JadwalShift>  $items
     * @return array{karyawan_id: int, nama: string, departemen: string, total_jam_lembur: float}
     */
    protected function ringkasLembur(Collection $items): array
    {
        $karyawan = $items->firstOrFail()->karyawan;

        return [
            'karyawan_id' => $karyawan->id,
            'nama' => $karyawan->nama,
            'departemen' => $karyawan->departemen->nama_departemen,
            'total_jam_lembur' => (float) $items->sum('jam_lembur'),
        ];
    }

    /**
     * @return array{departemen_id: int|null, dari: string|null, sampai: string|null}
     */
    protected function filters(Request $request): array
    {
        return [
            'departemen_id' => $request->integer('departemen_id') ?: null,
            'dari' => $request->string('dari')->toString() ?: null,
            'sampai' => $request->string('sampai')->toString() ?: null,
        ];
    }
}
