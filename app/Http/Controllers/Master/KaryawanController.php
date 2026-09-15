<?php

namespace App\Http\Controllers\Master;

use App\Exports\KaryawanImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\KaryawanImportRequest;
use App\Http\Requests\Master\KaryawanRequest;
use App\Http\Requests\Master\ResetDataKaryawanRequest;
use App\Imports\KaryawanImport;
use App\Models\Departemen;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\User;
use App\Services\ResetDataService;
use App\Services\SaldoCutiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KaryawanController extends Controller
{
    public function index(Request $request): Response
    {
        $search = (string) $request->string('search');
        $departemenId = $request->integer('departemen_id') ?: null;

        $karyawans = Karyawan::query()
            ->with(['departemen', 'jabatan', 'user.roles'])
            ->when($search, fn ($query) => $query->where('nama', 'like', "%{$search}%")->orWhere('nip', 'like', "%{$search}%"))
            ->when($departemenId, fn ($query) => $query->where('departemen_id', $departemenId))
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('master/karyawan', [
            'karyawans' => $karyawans,
            'departemens' => Departemen::all(),
            'jabatans' => Jabatan::all(),
            'kepalaBagianPerDepartemen' => $this->kepalaBagianPerDepartemen(),
            'filters' => ['search' => $search, 'departemen_id' => $departemenId],
        ]);
    }

    public function store(KaryawanRequest $request, SaldoCutiService $saldoCutiService): RedirectResponse
    {
        $karyawan = Karyawan::query()->create($request->validated());

        $saldoCutiService->bootstrapUntukKaryawanBaru($karyawan);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Karyawan berhasil ditambahkan.']);

        return back();
    }

    public function update(KaryawanRequest $request, Karyawan $karyawan): RedirectResponse
    {
        $karyawan->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Karyawan berhasil diperbarui.']);

        return back();
    }

    public function import(KaryawanImportRequest $request, SaldoCutiService $saldoCutiService): RedirectResponse
    {
        $import = new KaryawanImport($saldoCutiService);

        Excel::import($import, $request->file('file'));

        Inertia::flash('toast', [
            'type' => $import->failures === [] ? 'success' : 'error',
            'message' => $import->failures === []
                ? "{$import->successCount} karyawan berhasil diimpor."
                : "{$import->successCount} karyawan berhasil diimpor, ".count($import->failures).' baris gagal.',
        ]);

        if ($import->failures !== []) {
            Inertia::flash('importFailures', $import->failures);
        }

        return back();
    }

    public function importTemplate(): BinaryFileResponse
    {
        return Excel::download(new KaryawanImportTemplateExport, 'template-import-karyawan.xlsx');
    }

    public function destroy(Karyawan $karyawan): RedirectResponse
    {
        if ($karyawan->user || $karyawan->pengajuanCutis()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Karyawan tidak bisa dihapus karena masih punya akun login atau riwayat cuti. Ubah status ke nonaktif saja.',
            ]);

            return back();
        }

        $karyawan->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Karyawan berhasil dihapus.']);

        return back();
    }

    /**
     * Hapus SEMUA karyawan + akun login mereka (dan seluruh data
     * turunannya), untuk memulai ulang dari data kosong sebelum import
     * data karyawan yang asli. Khusus role admin, dan wajib mengetik ulang
     * frasa konfirmasi (lihat ResetDataKaryawanRequest). Karyawan milik
     * admin yang menjalankan ini (kalau ada) sengaja tidak ikut terhapus.
     */
    public function resetData(ResetDataKaryawanRequest $request, ResetDataService $service): RedirectResponse
    {
        $hasil = $service->resetSemuaKaryawan($request->user()->karyawan);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Data direset: {$hasil['karyawan']} karyawan, {$hasil['user']} akun login, dan {$hasil['cuti_massal']} batch cuti massal berhasil dihapus.",
        ]);

        return back();
    }

    /**
     * Ringkasan Kepala Bagian tiap departemen, agar admin bisa melihat ke
     * mana pengajuan cuti karyawan sebuah departemen akan dirutekan.
     *
     * @return array<int, array{departemen: string, kepala_bagian: string|null}>
     */
    protected function kepalaBagianPerDepartemen(): array
    {
        $kepalaBagianKaryawanIds = User::role('kepala_bagian')->pluck('karyawan_id');
        $kepalaBagians = Karyawan::query()->whereIn('id', $kepalaBagianKaryawanIds)->with('departemen')->get()->keyBy('departemen_id');

        return Departemen::all()->map(fn (Departemen $departemen) => [
            'departemen' => $departemen->nama_departemen,
            'kepala_bagian' => $kepalaBagians->get($departemen->id)?->nama,
        ])->all();
    }
}
