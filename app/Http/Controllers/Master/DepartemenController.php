<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\DepartemenRequest;
use App\Models\Departemen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartemenController extends Controller
{
    use HasPerPage;

    public function index(Request $request): Response
    {
        $search = (string) $request->string('search');

        $departemens = Departemen::query()
            ->withCount('karyawans')
            ->when($search, fn ($query) => $query->where('nama_departemen', 'like', "%{$search}%")->orWhere('kode', 'like', "%{$search}%"))
            ->orderBy('nama_departemen')
            ->paginate($this->resolvePerPage($request))
            ->withQueryString();

        return Inertia::render('master/departemen', [
            'departemens' => $departemens,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(DepartemenRequest $request): RedirectResponse
    {
        Departemen::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Departemen berhasil ditambahkan.']);

        return back();
    }

    public function update(DepartemenRequest $request, Departemen $departemen): RedirectResponse
    {
        $departemen->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Departemen berhasil diperbarui.']);

        return back();
    }

    public function destroy(Departemen $departemen): RedirectResponse
    {
        if ($departemen->karyawans()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Departemen tidak bisa dihapus karena masih memiliki karyawan.']);

            return back();
        }

        $departemen->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Departemen berhasil dihapus.']);

        return back();
    }
}
