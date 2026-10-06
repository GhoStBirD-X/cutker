<?php

namespace App\Http\Controllers\Cuti;

use App\Enums\StatusKompensasiCuti;
use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\KompensasiCuti;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class KompensasiCutiController extends Controller
{
    use HasPerPage;

    public function index(Request $request): Response
    {
        $status = StatusKompensasiCuti::tryFrom((string) $request->string('status')) ?? StatusKompensasiCuti::MenungguDiproses;
        $filters = [
            'status' => $status->value,
            'search' => (string) $request->string('search'),
            'departemen_id' => $request->integer('departemen_id') ?: null,
            'jenis_cuti_id' => $request->integer('jenis_cuti_id') ?: null,
        ];
        $search = $filters['search'];

        $kompensasiCutis = KompensasiCuti::query()
            ->with(['karyawan.departemen', 'jenisCuti', 'diprosesOleh', 'riwayatSaldoCuti'])
            ->where('status', $status)
            ->when($search !== '', fn (Builder $query) => $query->whereHas('karyawan', fn (Builder $q) => $q->where('nama', 'like', "%{$search}%")->orWhere('nip', 'like', "%{$search}%")))
            ->when($filters['departemen_id'], fn (Builder $query, int $id) => $query->whereHas('karyawan', fn (Builder $q) => $q->where('departemen_id', $id)))
            ->when($filters['jenis_cuti_id'], fn (Builder $query, int $id) => $query->where('jenis_cuti_id', $id))
            ->when(
                $status === StatusKompensasiCuti::MenungguDiproses,
                fn (Builder $query) => $query->orderBy(Karyawan::query()->select('nip')->whereColumn('karyawans.id', 'kompensasi_cutis.karyawan_id')),
                fn (Builder $query) => $query->latest('diproses_pada'),
            )
            ->orderBy('id')
            ->paginate($this->resolvePerPage($request, $status === StatusKompensasiCuti::MenungguDiproses ? 100 : 30))
            ->withQueryString();

        $jumlahPerStatus = KompensasiCuti::query()->selectRaw('status, count(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status');

        return Inertia::render('cuti/kompensasi/index', [
            'kompensasiCutis' => $kompensasiCutis,
            'jumlahMenunggu' => (int) ($jumlahPerStatus[StatusKompensasiCuti::MenungguDiproses->value] ?? 0),
            'jumlahDiproses' => (int) ($jumlahPerStatus[StatusKompensasiCuti::Diproses->value] ?? 0),
            'departemens' => Departemen::query()->orderBy('nama_departemen')->get(['id', 'nama_departemen']),
            'jenisCutis' => JenisCuti::query()->whereNotNull('masa_kerja_minimal_bulan')->orderBy('nama_jenis')->get(['id', 'nama_jenis']),
            'filters' => $filters,
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

    /**
     * Proses banyak kompensasi sekaligus, masing-masing dengan rate per
     * harinya sendiri (rate biasanya beda per karyawan karena ikut gaji).
     * Yang sudah diproses sebelumnya dilewati.
     */
    public function prosesMassal(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'distinct', 'exists:kompensasi_cutis,id'],
            'items.*.rate_per_hari' => ['required', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], [
            'items.required' => 'Isi rate untuk minimal satu karyawan.',
        ], [
            'items.*.rate_per_hari' => 'rate per hari',
        ]);

        $ratePerId = collect($data['items'])->mapWithKeys(fn (array $item): array => [(int) $item['id'] => $item['rate_per_hari']]);

        $kompensasiCutis = KompensasiCuti::query()
            ->whereKey($ratePerId->keys())
            ->where('status', StatusKompensasiCuti::MenungguDiproses)
            ->get();

        if ($kompensasiCutis->isEmpty()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Kompensasi yang dipilih sudah diproses sebelumnya.']);

            return back();
        }

        DB::transaction(function () use ($kompensasiCutis, $ratePerId, $data, $request): void {
            foreach ($kompensasiCutis as $kompensasiCuti) {
                $rate = $ratePerId[$kompensasiCuti->id];

                $kompensasiCuti->update([
                    'rate_per_hari' => $rate,
                    'total_rupiah' => $rate * $kompensasiCuti->jumlah_hari,
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
