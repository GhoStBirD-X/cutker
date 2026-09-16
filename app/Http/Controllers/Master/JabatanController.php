<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\JabatanRequest;
use App\Models\Jabatan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JabatanController extends Controller
{
    use HasPerPage;

    public function index(Request $request): Response
    {
        $search = (string) $request->string('search');

        $jabatans = Jabatan::query()
            ->withCount('karyawans')
            ->when($search, fn ($query) => $query->where('nama_jabatan', 'like', "%{$search}%"))
            ->orderBy('nama_jabatan')
            ->paginate($this->resolvePerPage($request))
            ->withQueryString();

        return Inertia::render('master/jabatan', [
            'jabatans' => $jabatans,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(JabatanRequest $request): RedirectResponse
    {
        Jabatan::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jabatan berhasil ditambahkan.']);

        return back();
    }

    public function update(JabatanRequest $request, Jabatan $jabatan): RedirectResponse
    {
        $jabatan->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jabatan berhasil diperbarui.']);

        return back();
    }

    public function destroy(Jabatan $jabatan): RedirectResponse
    {
        if ($jabatan->karyawans()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Jabatan tidak bisa dihapus karena masih dipakai karyawan.']);

            return back();
        }

        $jabatan->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jabatan berhasil dihapus.']);

        return back();
    }
}
