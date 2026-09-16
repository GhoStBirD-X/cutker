<?php

namespace App\Http\Controllers\Laporan;

use App\Enums\StatusPengajuan;
use App\Enums\TipeKaryawan;
use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\SaldoCuti;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class SaldoCutiController extends Controller
{
    use HasPerPage;

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
            ->paginate($this->resolvePerPage($request, 20))
            ->withQueryString();

        $karyawans->through(fn (Karyawan $karyawan) => [
            ...$karyawan->toArray(),
            'status_kontrak' => $this->statusKontrak($karyawan),
        ]);

        return Inertia::render('laporan/saldo-cuti', [
            'karyawans' => $karyawans,
            'departemens' => Departemen::query()->orderBy('nama_departemen')->get(['id', 'nama_departemen']),
            'filters' => [
                'search' => $search,
                'departemen_id' => $departemenId,
            ],
        ]);
    }

    /**
     * Riwayat pengajuan cuti yang membentuk angka "terpakai" pada satu baris
     * saldo aktif — dibatasi ke rentang periode/tahun baris saldo tersebut
     * supaya sesuai dengan yang sudah dipotong pada baris ini saja, bukan
     * riwayat cuti karyawan secara keseluruhan.
     */
    public function riwayat(SaldoCuti $saldoCuti): Response
    {
        $saldoCuti->load(['karyawan.departemen', 'jenisCuti']);

        $mulai = $saldoCuti->periode_mulai ?? Carbon::create($saldoCuti->tahun, 1, 1);
        $selesai = $saldoCuti->periode_selesai ?? Carbon::create($saldoCuti->tahun, 12, 31);

        $pengajuans = PengajuanCuti::query()
            ->where('karyawan_id', $saldoCuti->karyawan_id)
            ->where('jenis_cuti_id', $saldoCuti->jenis_cuti_id)
            ->where('status', StatusPengajuan::Disetujui)
            ->whereBetween('tanggal_mulai', [$mulai->toDateString(), $selesai->toDateString()])
            ->orderByDesc('tanggal_mulai')
            ->get();

        return Inertia::render('laporan/saldo-cuti-riwayat', [
            'saldoCuti' => $saldoCuti,
            'pengajuans' => $pengajuans,
        ]);
    }

    /**
     * "K1"/"K2"/"K3" dst. untuk karyawan kontrak (dihitung dari periode_ke
     * saldo Cuti Tahunan yang aktif — setiap pergantian periode adalah
     * titik konfirmasi perpanjangan kontrak), atau "KT" untuk karyawan
     * tetap.
     */
    private function statusKontrak(Karyawan $karyawan): string
    {
        if ($karyawan->tipe_karyawan === TipeKaryawan::Tetap) {
            return 'KT';
        }

        $cutiTahunan = $karyawan->saldoCutis
            ->first(fn (SaldoCuti $saldo) => $saldo->jenisCuti?->nama_jenis === 'Cuti Tahunan');

        return 'K'.($cutiTahunan->periode_ke ?? 1);
    }
}
