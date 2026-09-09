<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\JenisCutiRequest;
use App\Models\JenisCuti;
use App\Services\SaldoCutiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JenisCutiController extends Controller
{
    public function index(Request $request): Response
    {
        $search = (string) $request->string('search');

        $jenisCutis = JenisCuti::query()
            ->when($search, fn ($query) => $query->where('nama_jenis', 'like', "%{$search}%"))
            ->orderBy('nama_jenis')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('master/jenis-cuti', [
            'jenisCutis' => $jenisCutis,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(JenisCutiRequest $request, SaldoCutiService $saldoCutiService): RedirectResponse
    {
        $jenisCuti = JenisCuti::query()->create($request->validated());

        $jumlahSaldo = $jenisCuti->masa_kerja_minimal_bulan !== null
            ? $saldoCutiService->generatePeriodeAwalUntukJenisCuti($jenisCuti)
            : $saldoCutiService->generateUntukJenisCuti($jenisCuti, (int) now()->year);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Jenis cuti berhasil ditambahkan. Saldo langsung dibuat untuk {$jumlahSaldo} karyawan aktif.",
        ]);

        return back();
    }

    public function update(JenisCutiRequest $request, JenisCuti $jenis_cuti): RedirectResponse
    {
        $jenis_cuti->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jenis cuti berhasil diperbarui.']);

        return back();
    }

    public function destroy(JenisCuti $jenis_cuti): RedirectResponse
    {
        if ($jenis_cuti->pengajuanCutis()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Jenis cuti tidak bisa dihapus karena sudah memiliki riwayat pengajuan.']);

            return back();
        }

        $jenis_cuti->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jenis cuti berhasil dihapus.']);

        return back();
    }
}
