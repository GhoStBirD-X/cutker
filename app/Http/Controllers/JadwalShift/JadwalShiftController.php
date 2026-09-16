<?php

namespace App\Http\Controllers\JadwalShift;

use App\Http\Controllers\Controller;
use App\Http\Requests\JadwalShift\StoreJadwalShiftRequest;
use App\Http\Requests\JadwalShift\UpdateLemburRequest;
use App\Models\Departemen;
use App\Models\JadwalShift;
use App\Models\Karyawan;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class JadwalShiftController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $karyawan = $user->karyawan;

        $bulan = (int) ($request->input('bulan') ?? now()->month);
        $tahun = (int) ($request->input('tahun') ?? now()->year);

        $bisaKelola = $user->hasRole(['hrd', 'admin', 'koordinator_shift']);
        $bisaLihatSemua = $user->hasRole(['hrd', 'admin', 'kepala_bagian', 'manager', 'koordinator_shift']);
        $bisaLihatDepartemen = ! $bisaLihatSemua && $karyawan && $user->hasPermissionTo('jadwal-shift.manage');

        $departemenId = match (true) {
            $bisaLihatSemua => $request->integer('departemen_id') ?: null,
            $bisaLihatDepartemen => $karyawan->departemen_id,
            default => null,
        };

        $awal = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $akhir = $awal->copy()->endOfMonth();

        // Karyawan biasa/atasan tanpa hak kelola hanya boleh melihat jadwalnya
        // sendiri. Jika akun ini bahkan belum terhubung ke data karyawan sama
        // sekali, jangan tampilkan apa pun (bukan malah membocorkan semua
        // jadwal pabrik karena filter di bawah tidak punya id untuk dipakai).
        if (! $bisaLihatSemua && ! $bisaLihatDepartemen && ! $karyawan) {
            $jadwals = collect();
        } else {
            $jadwals = JadwalShift::query()
                ->with(['karyawan', 'shift'])
                ->whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])
                ->when($departemenId, fn ($query) => $query->whereHas('karyawan', fn ($q) => $q->where('departemen_id', $departemenId)))
                ->when(! $bisaLihatSemua && ! $bisaLihatDepartemen, fn ($query) => $query->where('karyawan_id', $karyawan->id))
                ->orderBy('tanggal')
                ->get();
        }

        // Daftar karyawan untuk formulir "Tambah Jadwal Shift" hanya perlu
        // dikirim ke role yang benar-benar bisa mengelola jadwal (hrd, admin,
        // koordinator_shift). Kepala bagian/manager hanya melihat jadwal,
        // jadi tidak perlu daftar ini.
        $karyawans = match (true) {
            ! $bisaKelola => collect(),
            $bisaLihatSemua => Karyawan::orderBy('nama')->get(['id', 'nama', 'departemen_id']),
            $bisaLihatDepartemen => Karyawan::where('departemen_id', $karyawan->departemen_id)->orderBy('nama')->get(['id', 'nama', 'departemen_id']),
            default => collect(),
        };

        return Inertia::render('jadwal-shift/calendar', [
            'jadwals' => $jadwals,
            'shifts' => Shift::all(),
            'karyawans' => $karyawans,
            'departemens' => Departemen::all(),
            'filters' => ['bulan' => $bulan, 'tahun' => $tahun, 'departemen_id' => $departemenId],
            'departemenTerkunci' => $bisaLihatDepartemen,
        ]);
    }

    /**
     * Menjadwalkan satu shift untuk banyak karyawan sekaligus dalam satu
     * rentang tanggal. Jika karyawan sudah punya jadwal di tanggal tersebut,
     * shift-nya akan ditimpa (bukan duplikat) karena kombinasi
     * karyawan+tanggal bersifat unik.
     */
    public function store(StoreJadwalShiftRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $karyawans = Karyawan::query()->whereIn('id', $data['karyawan_ids'])->get();

        foreach ($karyawans as $karyawan) {
            $this->authorize('create', [JadwalShift::class, $karyawan]);
        }

        $awal = Carbon::parse($data['tanggal_mulai']);
        $akhir = Carbon::parse($data['tanggal_selesai']);

        DB::transaction(function () use ($karyawans, $data, $awal, $akhir) {
            for ($tanggal = $awal->copy(); $tanggal->lte($akhir); $tanggal->addDay()) {
                foreach ($karyawans as $karyawan) {
                    JadwalShift::query()->updateOrCreate(
                        ['karyawan_id' => $karyawan->id, 'tanggal' => $tanggal->toDateString()],
                        ['shift_id' => $data['shift_id']],
                    );
                }
            }
        });

        $jumlahHari = $awal->diffInDays($akhir) + 1;
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Jadwal shift berhasil disimpan untuk {$karyawans->count()} karyawan selama {$jumlahHari} hari.",
        ]);

        // Arahkan ke bulan tempat jadwal baru dibuat dan lepas filter
        // departemen: form ini bisa memilih karyawan lintas departemen,
        // jadi kalau tetap pakai filter bulan/departemen lama, karyawan
        // yang baru diinput bisa jatuh di luar filter dan terlihat seolah
        // tidak tersimpan padahal datanya sudah masuk ke database.
        return redirect()->route('jadwal-shift.index', [
            'bulan' => $awal->month,
            'tahun' => $awal->year,
        ]);
    }

    public function updateLembur(UpdateLemburRequest $request, JadwalShift $jadwalShift): RedirectResponse
    {
        $this->authorize('update', $jadwalShift);

        $jadwalShift->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jam lembur berhasil disimpan.']);

        return back();
    }

    public function destroy(JadwalShift $jadwalShift): RedirectResponse
    {
        $this->authorize('delete', $jadwalShift);

        $jadwalShift->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jadwal shift berhasil dihapus.']);

        return back();
    }

    public function destroyMassal(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'jadwal_shift_ids' => ['required', 'array', 'min:1'],
            'jadwal_shift_ids.*' => ['integer', 'exists:jadwal_shifts,id'],
        ]);

        $jadwals = JadwalShift::query()->whereIn('id', $data['jadwal_shift_ids'])->get();

        foreach ($jadwals as $jadwal) {
            $this->authorize('delete', $jadwal);
        }

        $jumlah = $jadwals->count();
        JadwalShift::query()->whereIn('id', $jadwals->pluck('id'))->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$jumlah} jadwal shift berhasil dihapus."]);

        return back();
    }
}
