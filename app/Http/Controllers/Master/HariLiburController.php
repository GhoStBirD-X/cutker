<?php

namespace App\Http\Controllers\Master;

use App\Enums\SumberHariLibur;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\HariLiburRequest;
use App\Models\HariLibur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HariLiburController extends Controller
{
    public function index(Request $request): Response
    {
        $search = (string) $request->string('search');

        $hariLiburs = HariLibur::query()
            ->when($search, fn ($query) => $query->where('keterangan', 'like', "%{$search}%"))
            ->orderByDesc('tanggal')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('master/hari-libur', [
            'hariLiburs' => $hariLiburs,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(HariLiburRequest $request): RedirectResponse
    {
        HariLibur::query()->create([...$request->validated(), 'sumber' => SumberHariLibur::Perusahaan]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Hari libur berhasil ditambahkan.']);

        return back();
    }

    public function update(HariLiburRequest $request, HariLibur $hari_libur): RedirectResponse
    {
        $hari_libur->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Hari libur berhasil diperbarui.']);

        return back();
    }

    public function destroy(HariLibur $hari_libur): RedirectResponse
    {
        $hari_libur->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Hari libur berhasil dihapus.']);

        return back();
    }
}
