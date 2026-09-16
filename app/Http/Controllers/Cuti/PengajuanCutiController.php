<?php

namespace App\Http\Controllers\Cuti;

use App\Exceptions\SaldoCutiTidakCukupException;
use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cuti\StorePengajuanCutiRequest;
use App\Models\AlasanCuti;
use App\Models\JenisCuti;
use App\Models\PengajuanCuti;
use App\Models\SaldoCuti;
use App\Services\PengajuanCutiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PengajuanCutiController extends Controller
{
    use HasPerPage;

    public function index(Request $request): Response
    {
        $karyawan = $request->user()->karyawan;

        $pengajuans = PengajuanCuti::query()
            ->with('jenisCuti')
            ->where('karyawan_id', $karyawan->id)
            ->latest('tanggal_pengajuan')
            ->paginate($this->resolvePerPage($request))
            ->withQueryString();

        return Inertia::render('cuti/index', [
            'pengajuans' => $pengajuans,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('cuti/ajukan', $this->dataUntukForm($request));
    }

    public function createMendadak(Request $request): Response
    {
        return Inertia::render('cuti/ajukan-mendadak', $this->dataUntukForm($request));
    }

    /**
     * @return array<string, mixed>
     */
    private function dataUntukForm(Request $request): array
    {
        $karyawan = $request->user()->karyawan;

        return [
            'jenisCutis' => JenisCuti::all(),
            'alasanCutis' => AlasanCuti::all(),
            'saldoCuti' => SaldoCuti::query()
                ->with('jenisCuti')
                ->where('karyawan_id', $karyawan->id)
                ->where('tahun', now()->year)
                ->get(),
        ];
    }

    public function store(StorePengajuanCutiRequest $request, PengajuanCutiService $service): RedirectResponse
    {
        $data = $request->validated();

        try {
            $pengajuan = $service->ajukan(
                $request->user()->karyawan,
                [
                    'jenis_cuti_id' => (int) $data['jenis_cuti_id'],
                    'alasan_cuti_id' => isset($data['alasan_cuti_id']) ? (int) $data['alasan_cuti_id'] : null,
                    'tanggal_mulai' => (string) $data['tanggal_mulai'],
                    'tanggal_selesai' => (string) $data['tanggal_selesai'],
                    'alasan' => (string) $data['alasan'],
                    'mendadak' => $data['mendadak'] ?? false,
                    'alasan_mendadak' => $data['alasan_mendadak'] ?? null,
                ],
                $request->file('lampiran'),
            );
        } catch (SaldoCutiTidakCukupException $e) {
            return back()->withErrors(['tanggal_selesai' => $e->getMessage()])->withInput();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengajuan cuti berhasil diajukan.']);

        return to_route('cuti.show', $pengajuan);
    }

    public function show(Request $request, PengajuanCuti $pengajuan): Response
    {
        $this->authorize('view', $pengajuan);

        $pengajuan->load(['jenisCuti', 'karyawan', 'approvals.approver']);

        return Inertia::render('cuti/show', [
            'pengajuan' => $pengajuan,
        ]);
    }

    public function batalkan(Request $request, PengajuanCuti $pengajuan, PengajuanCutiService $service): RedirectResponse
    {
        $this->authorize('cancel', $pengajuan);

        $service->batalkan($pengajuan);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengajuan cuti berhasil dibatalkan.']);

        return back();
    }
}
