<?php

namespace App\Http\Controllers\Cuti;

use App\Exceptions\CutiMassalSudahDibatalkanException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cuti\StoreCutiMassalRequest;
use App\Models\CutiMassal;
use App\Models\JenisCuti;
use App\Services\CutiMassalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class CutiMassalController extends Controller
{
    public function index(Request $request): Response
    {
        $cutiMassals = CutiMassal::query()
            ->with(['jenisCuti', 'dibuatOleh'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('cuti/massal/index', [
            'cutiMassals' => $cutiMassals,
        ]);
    }

    public function create(Request $request, CutiMassalService $service): Response
    {
        $eligibleKaryawans = null;

        $data = $request->validate([
            'jenis_cuti_id' => ['nullable', 'integer', 'exists:jenis_cutis,id'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        if (! empty($data['jenis_cuti_id']) && ! empty($data['tanggal_mulai']) && ! empty($data['tanggal_selesai'])) {
            $jenisCuti = JenisCuti::query()->find((int) $data['jenis_cuti_id']);

            if ($jenisCuti) {
                $eligibleKaryawans = $service->previewKaryawan(
                    $jenisCuti,
                    Carbon::parse($data['tanggal_mulai']),
                    Carbon::parse($data['tanggal_selesai']),
                );
            }
        }

        return Inertia::render('cuti/massal/create', [
            'jenisCutis' => JenisCuti::all(),
            'eligibleKaryawans' => $eligibleKaryawans,
            'filter' => $data,
        ]);
    }

    public function store(StoreCutiMassalRequest $request, CutiMassalService $service): RedirectResponse
    {
        $data = $request->validated();

        $cutiMassal = $service->buat([
            'jenis_cuti_id' => (int) $data['jenis_cuti_id'],
            'tanggal_mulai' => (string) $data['tanggal_mulai'],
            'tanggal_selesai' => (string) $data['tanggal_selesai'],
            'alasan' => (string) $data['alasan'],
            'karyawan_ids' => array_map(intval(...), $data['karyawan_ids']),
        ], $request->user()->karyawan);

        $jumlahDilewati = count($cutiMassal->dilewati ?? []);
        $pesan = "Cuti massal berhasil dibuat untuk {$cutiMassal->jumlah_karyawan} karyawan.";

        if ($jumlahDilewati > 0) {
            $pesan .= " {$jumlahDilewati} karyawan dilewati, lihat detail untuk alasannya.";
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $pesan]);

        return to_route('cuti.massal.show', $cutiMassal);
    }

    public function show(Request $request, CutiMassal $cuti_massal): Response
    {
        $cuti_massal->load(['jenisCuti', 'dibuatOleh', 'dibatalkanOleh', 'pengajuanCutis.karyawan']);

        return Inertia::render('cuti/massal/show', [
            'cutiMassal' => $cuti_massal,
        ]);
    }

    public function batalkan(Request $request, CutiMassal $cuti_massal, CutiMassalService $service): RedirectResponse
    {
        $data = $request->validate([
            'catatan_pembatalan' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $service->batalkan($cuti_massal, $request->user()->karyawan, $data['catatan_pembatalan'] ?? null);
        } catch (CutiMassalSudahDibatalkanException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cuti massal berhasil dibatalkan, saldo karyawan terdampak telah dikembalikan.']);

        return back();
    }
}
