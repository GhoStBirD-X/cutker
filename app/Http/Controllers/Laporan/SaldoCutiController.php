<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaldoCutiController extends Controller
{
    public function index(Request $request): Response
    {
        $search = (string) $request->string('search');
        $departemenId = $request->integer('departemen_id') ?: null;

        $karyawans = Karyawan::query()
            ->with(['departemen', 'jabatan'])
            ->with(['saldoCutis' => fn ($query) => $query->aktif()->with('jenisCuti')->orderBy('jenis_cuti_id')])
            ->when($search, fn ($query) => $query->where(fn ($q) => $q->where('nama', 'like', "%{$search}%")->orWhere('nip', 'like', "%{$search}%")))
            ->when($departemenId, fn ($query) => $query->where('departemen_id', $departemenId))
            ->orderBy('nama')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('laporan/saldo-cuti', [
            'karyawans' => $karyawans,
            'departemens' => Departemen::query()->orderBy('nama_departemen')->get(['id', 'nama_departemen']),
            'filters' => [
                'search' => $search,
                'departemen_id' => $departemenId,
            ],
        ]);
    }
}
