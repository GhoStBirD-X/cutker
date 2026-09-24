<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SaldoCutiRequest;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\SaldoCuti;
use App\Services\SaldoCutiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaldoCutiController extends Controller
{
    use HasPerPage;

    public function index(Request $request): Response
    {
        $search = (string) $request->string('search');

        $saldoCutis = SaldoCuti::query()
            ->with(['karyawan', 'jenisCuti', 'diubahOleh'])
            ->aktif()
            ->when($search, fn ($query) => $query->whereHas('karyawan', fn ($q) => $q->where('nama', 'like', "%{$search}%")->orWhere('nip', 'like', "%{$search}%")))
            ->orderBy('karyawan_id')
            ->paginate($this->resolvePerPage($request, 15))
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
        $saldoCutiService->buatManual([
            'karyawan_id' => $request->integer('karyawan_id'),
            'jenis_cuti_id' => $request->integer('jenis_cuti_id'),
            'tahun' => $request->filled('tahun') ? $request->integer('tahun') : null,
            'periode_ke' => $request->filled('periode_ke') ? $request->integer('periode_ke') : null,
            'kuota' => $request->filled('kuota') ? $request->integer('kuota') : null,
            'terpakai' => $request->integer('terpakai'),
            'sisa' => $request->filled('sisa') ? $request->integer('sisa') : null,
            'catatan' => $request->string('catatan')->toString(),
        ], $request->user()->karyawan);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saldo cuti berhasil ditambahkan.']);

        return back();
    }

    public function update(SaldoCutiRequest $request, SaldoCuti $saldo_cuti, SaldoCutiService $saldoCutiService): RedirectResponse
    {
        $saldoCutiService->sesuaikanManual($saldo_cuti, [
            'kuota' => $request->filled('kuota') ? $request->integer('kuota') : null,
            'terpakai' => $request->integer('terpakai'),
            'sisa' => $request->filled('sisa') ? $request->integer('sisa') : null,
            'catatan' => $request->string('catatan')->toString(),
        ], $request->user()->karyawan);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saldo cuti berhasil disesuaikan.']);

        return back();
    }

    public function destroy(Request $request, SaldoCuti $saldo_cuti): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('master-data.manage'), 403);

        $saldo_cuti->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Baris saldo cuti berhasil dihapus.']);

        return back();
    }

    /**
     * Nomor periode yang belum tercatat untuk kombinasi karyawan + jenis
     * cuti bertipe periode, lengkap dengan tanggal mulai/selesai yang
     * sudah dihitung otomatis — dipakai mengisi dropdown "Periode Ke-" di
     * form tambah saldo manual.
     */
    public function periodeTersedia(Request $request, SaldoCutiService $saldoCutiService): JsonResponse
    {
        $request->validate([
            'karyawan_id' => ['required', 'integer', 'exists:karyawans,id'],
            'jenis_cuti_id' => ['required', 'integer', 'exists:jenis_cutis,id'],
        ]);

        $karyawan = Karyawan::query()->findOrFail($request->integer('karyawan_id'));
        $jenisCuti = JenisCuti::query()->findOrFail($request->integer('jenis_cuti_id'));

        return response()->json([
            'periodes' => $saldoCutiService->periodeTersediaUntuk($karyawan, $jenisCuti),
        ]);
    }
}
