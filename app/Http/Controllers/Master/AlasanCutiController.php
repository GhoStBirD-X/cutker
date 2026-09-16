<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\AlasanCutiRequest;
use App\Models\AlasanCuti;
use App\Models\JenisCuti;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlasanCutiController extends Controller
{
    use HasPerPage;

    public function index(Request $request): Response
    {
        $search = (string) $request->string('search');

        $alasanCutis = AlasanCuti::query()
            ->with('jenisCuti')
            ->when($search, fn ($query) => $query->where('nama_alasan', 'like', "%{$search}%"))
            ->orderBy('nama_alasan')
            ->paginate($this->resolvePerPage($request))
            ->withQueryString();

        return Inertia::render('master/alasan-cuti', [
            'alasanCutis' => $alasanCutis,
            'jenisCutis' => JenisCuti::query()->orderBy('nama_jenis')->get(),
            'filters' => ['search' => $search],
        ]);
    }

    public function store(AlasanCutiRequest $request): RedirectResponse
    {
        AlasanCuti::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Alasan cuti berhasil ditambahkan.']);

        return back();
    }

    public function update(AlasanCutiRequest $request, AlasanCuti $alasan_cuti): RedirectResponse
    {
        $alasan_cuti->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Alasan cuti berhasil diperbarui.']);

        return back();
    }

    public function destroy(AlasanCuti $alasan_cuti): RedirectResponse
    {
        if ($alasan_cuti->pengajuanCutis()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Alasan cuti tidak bisa dihapus karena sudah memiliki riwayat pengajuan.']);

            return back();
        }

        $alasan_cuti->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Alasan cuti berhasil dihapus.']);

        return back();
    }
}
