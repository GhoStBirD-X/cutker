<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ShiftRequest;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShiftController extends Controller
{
    public function index(Request $request): Response
    {
        $search = (string) $request->string('search');

        $shifts = Shift::query()
            ->when($search, fn ($query) => $query->where('nama_shift', 'like', "%{$search}%"))
            ->orderBy('nama_shift')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('master/shift', [
            'shifts' => $shifts,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(ShiftRequest $request): RedirectResponse
    {
        Shift::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Shift berhasil ditambahkan.']);

        return back();
    }

    public function update(ShiftRequest $request, Shift $shift): RedirectResponse
    {
        $shift->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Shift berhasil diperbarui.']);

        return back();
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        if ($shift->jadwalShifts()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Shift tidak bisa dihapus karena masih dipakai di jadwal.']);

            return back();
        }

        $shift->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Shift berhasil dihapus.']);

        return back();
    }
}
