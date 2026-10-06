<?php

namespace App\Http\Controllers\Approval;

use App\Enums\StatusApproval;
use App\Enums\StatusPengajuan;
use App\Exceptions\ApprovalSudahDiprosesException;
use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Approval\ApprovalActionRequest;
use App\Models\Approval;
use App\Models\PengajuanCuti;
use App\Services\ApprovalService;
use App\Services\SaldoCutiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    use HasPerPage;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Approval::class);

        $user = $request->user();
        $karyawan = $user->karyawan;
        $search = (string) $request->string('search');
        $hanyaMendadak = $request->boolean('mendadak');

        $approvals = Approval::query()
            ->with(['pengajuanCuti.karyawan', 'pengajuanCuti.jenisCuti'])
            ->where('status', StatusApproval::Pending)
            ->where(function ($query) use ($user, $karyawan) {
                $query->where('approver_id', $karyawan?->id);

                if ($user->hasRole('hrd')) {
                    $query->orWhere('level', ApprovalService::LEVEL_HRD);
                }

                if ($user->hasRole('manager')) {
                    $query->orWhere('level', ApprovalService::LEVEL_MANAGER);
                }
            })
            ->when($search, fn ($query) => $query->whereHas('pengajuanCuti.karyawan', fn ($karyawanQuery) => $karyawanQuery->where('nama', 'like', "%{$search}%")))
            ->when($hanyaMendadak, fn ($query) => $query->whereHas('pengajuanCuti', fn ($pengajuanQuery) => $pengajuanQuery->where('is_mendadak', true)))
            ->latest()
            ->paginate($this->resolvePerPage($request))
            ->withQueryString();

        return Inertia::render('approval/index', [
            'approvals' => $approvals,
            'filters' => ['search' => $search, 'mendadak' => $hanyaMendadak],
        ]);
    }

    /**
     * Selain detail pengajuan, approver juga diberi konteks keputusan: saldo
     * berjalan karyawan untuk jenis cuti tersebut dan rekan satu departemen
     * yang cutinya (pending/disetujui) beririsan dengan rentang tanggal yang
     * sama — supaya tidak perlu mengecek halaman lain sebelum memutuskan.
     */
    public function show(Request $request, Approval $approval, SaldoCutiService $saldoCutiService): Response
    {
        $this->authorize('view', $approval);

        $approval->load(['pengajuanCuti.karyawan.departemen', 'pengajuanCuti.jenisCuti', 'pengajuanCuti.approvals.approver']);
        $pengajuan = $approval->pengajuanCuti;

        $rekanCutiBersamaan = PengajuanCuti::query()
            ->with(['karyawan:id,nama', 'jenisCuti:id,nama_jenis'])
            ->whereKeyNot($pengajuan->id)
            ->where('karyawan_id', '!=', $pengajuan->karyawan_id)
            ->whereHas('karyawan', fn ($query) => $query->where('departemen_id', $pengajuan->karyawan->departemen_id))
            ->whereIn('status', [StatusPengajuan::Pending, StatusPengajuan::Disetujui])
            ->whereDate('tanggal_mulai', '<=', $pengajuan->tanggal_selesai)
            ->whereDate('tanggal_selesai', '>=', $pengajuan->tanggal_mulai)
            ->orderBy('tanggal_mulai')
            ->get(['id', 'karyawan_id', 'jenis_cuti_id', 'tanggal_mulai', 'tanggal_selesai', 'status']);

        return Inertia::render('approval/show', [
            'approval' => $approval,
            'canAct' => $request->user()->can('act', $approval),
            'saldoKaryawan' => $saldoCutiService->untukPeriodeAktif($pengajuan->karyawan, $pengajuan->jenisCuti)
                ?->only(['kuota', 'terpakai', 'sisa']),
            'rekanCutiBersamaan' => $rekanCutiBersamaan,
        ]);
    }

    public function approve(ApprovalActionRequest $request, Approval $approval, ApprovalService $service): RedirectResponse
    {
        try {
            $service->approve($approval, $request->user()->karyawan, $request->validated('catatan'));
        } catch (ApprovalSudahDiprosesException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return to_route('approval.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengajuan cuti berhasil disetujui.']);

        return to_route('approval.index');
    }

    public function reject(ApprovalActionRequest $request, Approval $approval, ApprovalService $service): RedirectResponse
    {
        try {
            $service->reject($approval, $request->user()->karyawan, $request->validated('catatan'));
        } catch (ApprovalSudahDiprosesException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return to_route('approval.index');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengajuan cuti berhasil ditolak.']);

        return to_route('approval.index');
    }
}
