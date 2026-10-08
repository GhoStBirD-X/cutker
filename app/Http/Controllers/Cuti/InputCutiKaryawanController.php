<?php

namespace App\Http\Controllers\Cuti;

use App\Enums\StatusKaryawan;
use App\Exceptions\SaldoCutiTidakCukupException;
use App\Http\Controllers\Concerns\DataFormPengajuanCuti;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cuti\StoreInputCutiKaryawanRequest;
use App\Models\Karyawan;
use App\Services\PengajuanCutiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * HRD/Admin mencatat cuti atas nama karyawan lain — termasuk cuti yang
 * sudah lama lewat (backdate) — dan langsung disetujui tanpa alur approval.
 */
class InputCutiKaryawanController extends Controller
{
    use DataFormPengajuanCuti;

    /**
     * Data form (jenis cuti, saldo, dst.) baru dikirim setelah karyawan
     * dipilih lewat query `karyawan_id`. Hari libur diambil sejak 2 tahun
     * lalu supaya pratinjau hari kerja tetap akurat untuk backdate.
     */
    public function create(Request $request): Response
    {
        $karyawan = Karyawan::query()->find($request->integer('karyawan_id'));

        return Inertia::render('cuti/input-karyawan', [
            'karyawans' => Karyawan::query()
                ->where('status', StatusKaryawan::Aktif)
                ->orderBy('nama')
                ->get(['id', 'nama', 'nip']),
            'karyawanId' => $karyawan?->id,
            ...($karyawan ? $this->dataFormCuti($karyawan, today()->subYears(2)) : []),
        ]);
    }

    public function store(StoreInputCutiKaryawanRequest $request, PengajuanCutiService $service): RedirectResponse
    {
        $data = $request->validated();

        try {
            $pengajuan = $service->catatOlehHrd(
                Karyawan::query()->findOrFail((int) $data['karyawan_id']),
                [
                    'jenis_cuti_id' => (int) $data['jenis_cuti_id'],
                    'alasan_cuti_id' => isset($data['alasan_cuti_id']) ? (int) $data['alasan_cuti_id'] : null,
                    'tanggal_mulai' => (string) $data['tanggal_mulai'],
                    'tanggal_selesai' => (string) $data['tanggal_selesai'],
                    'alasan' => (string) $data['alasan'],
                ],
                $request->user()->karyawan,
                $request->file('lampiran'),
            );
        } catch (SaldoCutiTidakCukupException $e) {
            return back()->withErrors(['tanggal_selesai' => $e->getMessage()])->withInput();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cuti karyawan berhasil dicatat dan langsung disetujui.']);

        return to_route('cuti.show', $pengajuan);
    }
}
