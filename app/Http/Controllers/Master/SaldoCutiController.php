<?php

namespace App\Http\Controllers\Master;

use App\Enums\AksiMassalSaldoCuti;
use App\Exports\SaldoCutiMasterExport;
use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SaldoCutiAksiMassalRequest;
use App\Http\Requests\Master\SaldoCutiImportRequest;
use App\Http\Requests\Master\SaldoCutiRequest;
use App\Http\Requests\Master\SaldoCutiStoreMassalRequest;
use App\Http\Requests\Master\SaldoCutiUpdateMassalRequest;
use App\Imports\SaldoCutiImport;
use App\Models\Departemen;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\SaldoCuti;
use App\Services\SaldoCutiService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaldoCutiController extends Controller
{
    use HasPerPage;

    public function index(Request $request): Response
    {
        $filters = $this->filters($request);

        $saldoTersaring = $this->queryTersaring($filters);

        // Dipaginasi per karyawan (bukan per baris saldo) supaya semua jenis
        // cuti milik satu orang selalu tampil utuh dalam satu grup.
        $grupKaryawan = Karyawan::query()
            ->whereIn('id', (clone $saldoTersaring)->reorder()->select('karyawan_id'))
            ->with('departemen')
            ->orderBy('nama')
            ->orderBy('id')
            ->paginate($this->resolvePerPage($request, 20), ['id', 'nama', 'nip', 'departemen_id'])
            ->withQueryString();

        $saldoPerKaryawan = (clone $saldoTersaring)
            ->whereIn('karyawan_id', $grupKaryawan->pluck('id'))
            ->with(['jenisCuti', 'diubahOleh'])
            ->get()
            ->groupBy('karyawan_id');

        $grupKaryawan->each(fn (Karyawan $karyawan) => $karyawan->setRelation(
            'saldoCutis',
            $saldoPerKaryawan->get($karyawan->id, collect())->values(),
        ));

        return Inertia::render('master/saldo-cuti', [
            'grupKaryawan' => $grupKaryawan,
            'totalBaris' => $saldoTersaring->count(),
            'karyawans' => Karyawan::query()->orderBy('nama')->get(['id', 'nama', 'nip', 'departemen_id']),
            'jenisCutis' => JenisCuti::query()->orderBy('nama_jenis')->get(),
            'departemens' => Departemen::query()->orderBy('nama_departemen')->get(['id', 'nama_departemen']),
            'tahunTersedia' => SaldoCuti::query()->aktif()->distinct()->orderByDesc('tahun')->pluck('tahun'),
            'aksiMassal' => collect(AksiMassalSaldoCuti::cases())->map(fn (AksiMassalSaldoCuti $aksi): array => [
                'value' => $aksi->value,
                'label' => $aksi->label(),
                'butuh_nilai' => $aksi->butuhNilai(),
            ]),
            'filters' => $filters,
        ]);
    }

    public function store(SaldoCutiRequest $request, SaldoCutiService $saldoCutiService): RedirectResponse
    {
        $saldoCutiService->buatManual([
            'karyawan_id' => $request->integer('karyawan_id'),
            'jenis_cuti_id' => $request->integer('jenis_cuti_id'),
            'tahun' => $request->filled('tahun') ? $request->integer('tahun') : null,
            'periode_ke' => $request->filled('periode_ke') ? $request->integer('periode_ke') : null,
            'kuota' => $request->filled('kuota') ? $request->integer('kuota') : null,
            'terpakai' => $request->integer('terpakai'),
            'sisa' => $request->filled('sisa') ? $request->integer('sisa') : null,
            'catatan' => $request->string('catatan')->toString(),
        ], $request->user()->karyawan);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saldo cuti berhasil ditambahkan.']);

        return back();
    }

    public function update(SaldoCutiRequest $request, SaldoCuti $saldo_cuti, SaldoCutiService $saldoCutiService): RedirectResponse
    {
        $saldoCutiService->sesuaikanManual($saldo_cuti, [
            'kuota' => $request->filled('kuota') ? $request->integer('kuota') : null,
            'terpakai' => $request->integer('terpakai'),
            'sisa' => $request->filled('sisa') ? $request->integer('sisa') : null,
            'catatan' => $request->string('catatan')->toString(),
        ], $request->user()->karyawan);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saldo cuti berhasil disesuaikan.']);

        return back();
    }

    public function destroy(Request $request, SaldoCuti $saldo_cuti): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('master-data.manage'), 403);

        $saldo_cuti->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Baris saldo cuti berhasil dihapus.']);

        return back();
    }

    /**
     * Nomor periode yang belum tercatat untuk kombinasi karyawan + jenis
     * cuti bertipe periode, lengkap dengan tanggal mulai/selesai yang
     * sudah dihitung otomatis — dipakai mengisi dropdown "Periode Ke-" di
     * form tambah saldo manual.
     */
    public function periodeTersedia(Request $request, SaldoCutiService $saldoCutiService): JsonResponse
    {
        $request->validate([
            'karyawan_id' => ['required', 'integer', 'exists:karyawans,id'],
            'jenis_cuti_id' => ['required', 'integer', 'exists:jenis_cutis,id'],
        ]);

        $karyawan = Karyawan::query()->findOrFail($request->integer('karyawan_id'));
        $jenisCuti = JenisCuti::query()->findOrFail($request->integer('jenis_cuti_id'));

        return response()->json([
            'periodes' => $saldoCutiService->periodeTersediaUntuk($karyawan, $jenisCuti),
        ]);
    }

    /**
     * Simpan hasil edit langsung di tabel (atau import Excel yang sudah
     * dikonfirmasi) sekaligus, dengan satu catatan audit untuk semua baris.
     */
    public function updateMassal(SaldoCutiUpdateMassalRequest $request, SaldoCutiService $saldoCutiService): RedirectResponse
    {
        $perubahan = collect($request->validated('perubahan'))->map(fn (array $baris): array => [
            'id' => (int) $baris['id'],
            'kuota' => isset($baris['kuota']) ? (int) $baris['kuota'] : null,
            'terpakai' => (int) $baris['terpakai'],
            'sisa' => isset($baris['sisa']) ? (int) $baris['sisa'] : null,
        ])->all();

        $jumlah = $saldoCutiService->sesuaikanMassal($perubahan, $request->string('catatan')->toString(), $request->user()->karyawan);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$jumlah} baris saldo cuti berhasil disimpan."]);

        return back();
    }

    public function storeMassal(SaldoCutiStoreMassalRequest $request, SaldoCutiService $saldoCutiService): RedirectResponse
    {
        $hasil = $saldoCutiService->buatMassal(
            array_map('intval', $request->validated('karyawan_ids')),
            JenisCuti::query()->findOrFail($request->integer('jenis_cuti_id')),
            $request->filled('tahun') ? $request->integer('tahun') : null,
            $request->filled('kuota') ? $request->integer('kuota') : null,
            $request->integer('terpakai'),
            $request->string('catatan')->toString(),
            $request->user()->karyawan,
        );

        $pesan = "{$hasil['dibuat']} saldo cuti berhasil ditambahkan.";

        if ($hasil['dilewati'] !== []) {
            $pesan .= ' Dilewati: '.implode(', ', $hasil['dilewati']).'.';
        }

        Inertia::flash('toast', ['type' => $hasil['dibuat'] > 0 ? 'success' : 'error', 'message' => $pesan]);

        return back();
    }

    /**
     * Pratinjau sebelum → sesudah aksi massal, tanpa menyimpan apa pun.
     */
    public function pratinjauAksiMassal(SaldoCutiAksiMassalRequest $request, SaldoCutiService $saldoCutiService): JsonResponse
    {
        $hasil = $saldoCutiService->hitungAksiMassal(
            $this->targetAksiMassal($request)->with(['karyawan', 'jenisCuti'])->get(),
            AksiMassalSaldoCuti::from($request->string('aksi')->toString()),
            $request->filled('nilai') ? $request->integer('nilai') : null,
        );

        return response()->json([
            'baris' => array_map(fn (array $baris): array => [
                'id' => $baris['saldo']->id,
                'nama' => $baris['saldo']->karyawan->nama,
                'nip' => $baris['saldo']->karyawan->nip,
                'jenis_cuti' => $baris['saldo']->jenisCuti->nama_jenis,
                'periode' => $baris['saldo']->periode_ke ? "Periode ke-{$baris['saldo']->periode_ke}" : "Tahun {$baris['saldo']->tahun}",
                'sebelum' => ['kuota' => $baris['saldo']->kuota, 'terpakai' => $baris['saldo']->terpakai, 'sisa' => $baris['saldo']->sisa],
                'sesudah' => ['kuota' => $baris['kuota'], 'terpakai' => $baris['terpakai'], 'sisa' => $baris['sisa']],
                'galat' => $baris['galat'],
            ], $hasil),
        ]);
    }

    public function aksiMassal(SaldoCutiAksiMassalRequest $request, SaldoCutiService $saldoCutiService): RedirectResponse
    {
        $aksi = AksiMassalSaldoCuti::from($request->string('aksi')->toString());

        $hasil = $saldoCutiService->terapkanAksiMassal(
            $this->targetAksiMassal($request)->get(),
            $aksi,
            $request->filled('nilai') ? $request->integer('nilai') : null,
            $request->string('catatan')->toString(),
            $request->user()->karyawan,
        );

        $pesan = $aksi === AksiMassalSaldoCuti::Hapus
            ? "{$hasil['diterapkan']} baris saldo cuti dihapus."
            : "{$hasil['diterapkan']} baris saldo cuti diperbarui.";

        if ($hasil['dilewati'] > 0) {
            $pesan .= " {$hasil['dilewati']} baris dilewati karena hasilnya tidak valid.";
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $pesan]);

        return back();
    }

    public function export(Request $request): BinaryFileResponse
    {
        $query = $this->queryTersaring($this->filters($request))
            ->with(['karyawan.departemen', 'jenisCuti']);

        return Excel::download(new SaldoCutiMasterExport($query), 'saldo-cuti-'.now()->format('Y-m-d').'.xlsx');
    }

    /**
     * Baca file Excel hasil export yang sudah diedit, lalu kirim daftar
     * perubahannya ke halaman sebagai pratinjau. Belum ada yang disimpan —
     * HRD mengonfirmasi dari pratinjau, lalu frontend mengirimnya ke
     * updateMassal().
     */
    public function pratinjauImport(SaldoCutiImportRequest $request): RedirectResponse
    {
        $import = new SaldoCutiImport;

        Excel::import($import, $request->file('file'));

        Inertia::flash('importPratinjau', [
            'perubahan' => $import->perubahan,
            'failures' => $import->failures,
            'tidak_berubah' => $import->tidakBerubah,
        ]);

        return back();
    }

    /**
     * @return array{search: string, jenis_cuti_id: int|null, departemen_id: int|null, tahun: int|null}
     */
    private function filters(Request $request): array
    {
        return [
            'search' => (string) $request->string('search'),
            'jenis_cuti_id' => $request->integer('jenis_cuti_id') ?: null,
            'departemen_id' => $request->integer('departemen_id') ?: null,
            'tahun' => $request->integer('tahun') ?: null,
        ];
    }

    /**
     * Query saldo aktif sesuai filter halaman — dipakai bersama oleh daftar,
     * export Excel, dan aksi massal "semua hasil filter" supaya yang dilihat
     * HRD sama dengan yang diproses.
     *
     * @param  array{search: string, jenis_cuti_id: int|null, departemen_id: int|null, tahun: int|null}  $filters
     * @return Builder<SaldoCuti>
     */
    private function queryTersaring(array $filters): Builder
    {
        $search = $filters['search'];

        return SaldoCuti::query()
            ->aktif()
            ->when($search !== '', fn (Builder $query) => $query->whereHas('karyawan', fn (Builder $q) => $q->where('nama', 'like', "%{$search}%")->orWhere('nip', 'like', "%{$search}%")))
            ->when($filters['jenis_cuti_id'], fn (Builder $query, int $id) => $query->where('jenis_cuti_id', $id))
            ->when($filters['departemen_id'], fn (Builder $query, int $id) => $query->whereHas('karyawan', fn (Builder $q) => $q->where('departemen_id', $id)))
            ->when($filters['tahun'], fn (Builder $query, int $tahun) => $query->where('tahun', $tahun))
            ->orderBy(Karyawan::query()->select('nama')->whereColumn('karyawans.id', 'saldo_cutis.karyawan_id'))
            ->orderBy('jenis_cuti_id')
            ->orderBy('id');
    }

    /**
     * @return Builder<SaldoCuti>
     */
    private function targetAksiMassal(SaldoCutiAksiMassalRequest $request): Builder
    {
        $query = $this->queryTersaring($this->filters($request));

        return $request->boolean('semua')
            ? $query
            : $query->whereKey(array_map('intval', $request->input('ids', [])));
    }
}
