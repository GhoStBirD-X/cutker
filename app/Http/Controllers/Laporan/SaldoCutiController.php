<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\SaldoCuti;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaldoCutiController extends Controller
{
    public function index(Request $request): Response
    {
        $search = (string) $request->string('search');
        $departemenId = $request->integer('departemen_id') ?: null;

        $saldoCutis = SaldoCuti::query()
            ->with(['karyawan.departemen', 'karyawan.jabatan', 'jenisCuti'])
            ->aktif()
            ->when($search, fn ($query) => $query->whereHas('karyawan', fn ($q) => $q->where('nama', 'like', "%{$search}%")->orWhere('nip', 'like', "%{$search}%")))
            ->when($departemenId, fn ($query) => $query->whereHas('karyawan', fn ($q) => $q->where('departemen_id', $departemenId)))
            ->orderBy('karyawan_id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('laporan/saldo-cuti', [
            'saldoCutis' => $saldoCutis,
            'departemens' => Departemen::query()->orderBy('nama_departemen')->get(['id', 'nama_departemen']),
            'filters' => [
                'search' => $search,
                'departemen_id' => $departemenId,
            ],
        ]);
    }
}
