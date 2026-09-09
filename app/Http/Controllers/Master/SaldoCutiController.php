<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SaldoCutiRequest;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\SaldoCuti;
use App\Services\SaldoCutiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaldoCutiController extends Controller
{
    public function index(Request $request): Response
    {
        $search = (string) $request->string('search');

        $saldoCutis = SaldoCuti::query()
            ->with(['karyawan', 'jenisCuti', 'diubahOleh'])
            ->aktif()
            ->when($search, fn ($query) => $query->whereHas('karyawan', fn ($q) => $q->where('nama', 'like', "%{$search}%")->orWhere('nip', 'like', "%{$search}%")))
            ->orderBy('karyawan_id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('master/saldo-cuti', [
            'saldoCutis' => $saldoCutis,
            'karyawans' => Karyawan::query()->orderBy('nama')->get(['id', 'nama', 'nip']),
            'jenisCutis' => JenisCuti::query()->orderBy('nama_jenis')->get(),
            'filters' => ['search' => $search],
        ]);
    }

    public function store(SaldoCutiRequest $request, SaldoCutiService $saldoCutiService): RedirectResponse
    {
        $saldoCutiService->buatManual($request->validated(), $request->user()->karyawan);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saldo cuti berhasil ditambahkan.']);

        return back();
    }

    public function update(SaldoCutiRequest $request, SaldoCuti $saldo_cuti, SaldoCutiService $saldoCutiService): RedirectResponse
    {
        $saldoCutiService->sesuaikanManual($saldo_cuti, $request->validated(), $request->user()->karyawan);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saldo cuti berhasil disesuaikan.']);

        return back();
    }
}
