<?php

namespace App\Http\Controllers\Approval;

use App\Enums\StatusApproval;
use App\Http\Controllers\Controller;
use App\Http\Requests\Approval\ApprovalActionRequest;
use App\Models\Approval;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Approval::class);

        $karyawan = $request->user()->karyawan;

        $approvals = Approval::query()
            ->with(['pengajuanCuti.karyawan', 'pengajuanCuti.jenisCuti'])
            ->where('approver_id', $karyawan->id)
            ->where('status', StatusApproval::Pending)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('approval/index', [
            'approvals' => $approvals,
        ]);
    }

    public function show(Request $request, Approval $approval): Response
    {
        $this->authorize('view', $approval);

        $approval->load(['pengajuanCuti.karyawan.departemen', 'pengajuanCuti.jenisCuti', 'pengajuanCuti.approvals.approver']);

        return Inertia::render('approval/show', [
            'approval' => $approval,
            'canAct' => $request->user()->can('act', $approval),
        ]);
    }

    public function approve(ApprovalActionRequest $request, Approval $approval, ApprovalService $service): RedirectResponse
    {
        $service->approve($approval, $request->user()->karyawan, $request->validated('catatan'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengajuan cuti berhasil disetujui.']);

        return to_route('approval.index');
    }

    public function reject(ApprovalActionRequest $request, Approval $approval, ApprovalService $service): RedirectResponse
    {
        $service->reject($approval, $request->user()->karyawan, $request->validated('catatan'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengajuan cuti berhasil ditolak.']);

        return to_route('approval.index');
    }
}
