<?php

namespace App\Http\Controllers\Cuti;

use App\Enums\StatusKompensasiCuti;
use App\Http\Controllers\Controller;
use App\Models\KompensasiCuti;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class KompensasiCutiController extends Controller
{
    public function index(Request $request): Response
    {
        $kompensasiCutis = KompensasiCuti::query()
            ->with(['karyawan', 'jenisCuti', 'diprosesOleh'])
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('cuti/kompensasi/index', [
            'kompensasiCutis' => $kompensasiCutis,
        ]);
    }

    public function proses(Request $request, KompensasiCuti $kompensasi_cuti): RedirectResponse
    {
        if ($kompensasi_cuti->status !== StatusKompensasiCuti::MenungguDiproses) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Kompensasi ini sudah diproses sebelumnya.']);

            return back();
        }

        $data = $request->validate([
            'rate_per_hari' => ['required', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $kompensasi_cuti->update([
            'rate_per_hari' => $data['rate_per_hari'],
            'total_rupiah' => $data['rate_per_hari'] * $kompensasi_cuti->jumlah_hari,
            'status' => StatusKompensasiCuti::Diproses,
            'diproses_oleh_id' => $request->user()->karyawan->id,
            'diproses_pada' => now(),
            'catatan' => $data['catatan'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kompensasi cuti berhasil diproses.']);

        return back();
    }

    public function prosesMassal(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kompensasi_cuti_ids' => ['required', 'array', 'min:1'],
            'kompensasi_cuti_ids.*' => ['integer', 'distinct', 'exists:kompensasi_cutis,id'],
            'rate_per_hari' => ['required', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $kompensasiCutis = KompensasiCuti::query()
            ->whereIn('id', $data['kompensasi_cuti_ids'])
            ->where('status', StatusKompensasiCuti::MenungguDiproses)
            ->get();

        if ($kompensasiCutis->isEmpty()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Kompensasi yang dipilih sudah diproses sebelumnya.']);

            return back();
        }

        DB::transaction(function () use ($kompensasiCutis, $data, $request): void {
            foreach ($kompensasiCutis as $kompensasiCuti) {
                $kompensasiCuti->update([
                    'rate_per_hari' => $data['rate_per_hari'],
                    'total_rupiah' => $data['rate_per_hari'] * $kompensasiCuti->jumlah_hari,
                    'status' => StatusKompensasiCuti::Diproses,
                    'diproses_oleh_id' => $request->user()->karyawan->id,
                    'diproses_pada' => now(),
                    'catatan' => $data['catatan'] ?? null,
                ]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => $kompensasiCutis->count().' kompensasi cuti berhasil diproses.']);

        return back();
    }
}
