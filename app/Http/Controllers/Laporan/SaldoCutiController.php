<?php

namespace App\Http\Controllers\Laporan;

use App\Enums\StatusPengajuan;
use App\Enums\TipeKaryawan;
use App\Exports\SaldoCutiExport;
use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\SaldoCuti;
use App\Support\SaldoSeverity;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaldoCutiController extends Controller
{
    use HasPerPage;

    /**
     * Kategori cuti tetap yang ditampilkan di kolom laporan matriks —
     * sengaja daftar tetap (bukan diambil dari tabel jenis_cutis) supaya
     * urutan & cakupan kolomnya sama persis dengan tabel di halaman
     * frontend (resources/js/pages/laporan/saldo-cuti.tsx).
     *
     * @var list<string>
     */
    private const KOLOM_JENIS_CUTI = ['Cuti Tahunan', 'Cuti Besar', 'Cuti Haid', 'Cuti Hamil'];

    public function index(Request $request): Response
    {
        $karyawans = $this->filteredKaryawanQuery($request)
            ->with(['departemen', 'jabatan'])
            ->with(['saldoCutis' => fn ($query) => $query->aktif()->with('jenisCuti')->orderBy('jenis_cuti_id')])
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
            'filters' => $this->filters($request),
        ]);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $karyawanIds = $this->filteredKaryawanQuery($request)->pluck('id');

        $query = SaldoCuti::query()
            ->with(['karyawan.departemen', 'karyawan.jabatan', 'jenisCuti'])
            ->whereIn('karyawan_id', $karyawanIds)
            ->aktif()
            ->orderBy('karyawan_id')
            ->orderBy('jenis_cuti_id');

        $nama = 'saldo-cuti-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new SaldoCutiExport($query), $nama);
    }

    /**
     * PDF matriks yang tampilannya disamakan dengan tabel di halaman
     * Laporan Saldo Cuti Karyawan (satu baris per karyawan, kolom
     * Terpakai/Sisa per kategori cuti, warna "Sisa" mengikuti urgensi
     * yang sama seperti di layar).
     */
    public function exportPdf(Request $request): HttpResponse
    {
        $filters = $this->filters($request);

        $karyawans = $this->filteredKaryawanQuery($request)
            ->with(['saldoCutis' => fn ($query) => $query->aktif()->with('jenisCuti')->orderBy('jenis_cuti_id')])
            ->orderBy('nama')
            ->get()
            ->map(fn (Karyawan $karyawan) => $this->barisLaporanPdf($karyawan));

        $pdf = Pdf::loadView('laporan.saldo-cuti-pdf', [
            'karyawans' => $karyawans,
            'kolomJenisCuti' => self::KOLOM_JENIS_CUTI,
            'departemen' => $filters['departemen_id'] ? Departemen::query()->find($filters['departemen_id']) : null,
            'filters' => $filters,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('saldo-cuti-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * Satu baris matriks untuk PDF: kolom cuti yang tidak relevan untuk
     * karyawan tsb (khusus_gender tidak cocok) atau belum punya baris
     * saldo aktif diisi null supaya blade cukup menampilkan "—".
     *
     * @return array{nama: string, nip: string, status_kontrak: string, kolom: list<array{terpakai: int, sisa: int|null, warna: string}|null>}
     */
    private function barisLaporanPdf(Karyawan $karyawan): array
    {
        return [
            'nama' => $karyawan->nama,
            'nip' => $karyawan->nip,
            'status_kontrak' => $this->statusKontrak($karyawan),
            'kolom' => collect(self::KOLOM_JENIS_CUTI)
                ->map(function (string $namaJenis) use ($karyawan) {
                    $saldo = $karyawan->saldoCutis->first(fn (SaldoCuti $saldo) => $saldo->jenisCuti?->nama_jenis === $namaJenis);

                    if (! $saldo) {
                        return null;
                    }

                    $khusus = $saldo->jenisCuti->khusus_gender;
                    if ($khusus !== null && $khusus !== $karyawan->jenis_kelamin) {
                        return null;
                    }

                    return [
                        'terpakai' => $saldo->terpakai,
                        'sisa' => $saldo->sisa,
                        'warna' => SaldoSeverity::warnaTeks(SaldoSeverity::hitung($saldo->sisa, $saldo->kuota)),
                    ];
                })
                ->all(),
        ];
    }

    /**
     * @return Builder<Karyawan>
     */
    protected function filteredKaryawanQuery(Request $request): Builder
    {
        $filters = $this->filters($request);

        return Karyawan::query()
            ->when($filters['search'], fn ($query) => $query->where(fn ($q) => $q->where('nama', 'like', "%{$filters['search']}%")->orWhere('nip', 'like', "%{$filters['search']}%")))
            ->when($filters['departemen_id'], fn ($query) => $query->where('departemen_id', $filters['departemen_id']));
    }

    /**
     * @return array{search: string, departemen_id: int|null}
     */
    protected function filters(Request $request): array
    {
        return [
            'search' => (string) $request->string('search'),
            'departemen_id' => $request->integer('departemen_id') ?: null,
        ];
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
