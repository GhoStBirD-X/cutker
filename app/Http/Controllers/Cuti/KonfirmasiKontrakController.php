<?php

namespace App\Http\Controllers\Cuti;

use App\Enums\StatusKonfirmasiKontrak;
use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\KonfirmasiKontrakCuti;
use App\Services\PeriodeCutiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class KonfirmasiKontrakController extends Controller
{
    use HasPerPage;

    private const KEPUTUSAN_PERPANJANG = 'perpanjang';

    private const KEPUTUSAN_ANGKAT_TETAP = 'angkat_tetap';

    private const KEPUTUSAN_TIDAK_DIPERPANJANG = 'tidak_diperpanjang';

    public function index(Request $request): Response
    {
        $konfirmasiKontraks = KonfirmasiKontrakCuti::query()
            ->with(['karyawan', 'saldoCuti.jenisCuti'])
            ->latest('id')
            ->paginate($this->resolvePerPage($request, 15))
            ->withQueryString();

        $menunggu = KonfirmasiKontrakCuti::query()->where('status', StatusKonfirmasiKontrak::Menunggu)->get(['id', 'periode_ke']);

        return Inertia::render('cuti/konfirmasi-kontrak/index', [
            'konfirmasiKontraks' => $konfirmasiKontraks,
            // Akhir K5 selalu dilewati perpanjangan massal, jadi tidak ikut dihitung.
            'jumlahBisaDiperpanjangMassal' => $menunggu->reject(fn (KonfirmasiKontrakCuti $konfirmasi) => $konfirmasi->diAkhirSiklusKontrak())->count(),
        ]);
    }

    public function konfirmasi(Request $request, KonfirmasiKontrakCuti $konfirmasi_kontrak, PeriodeCutiService $periodeCutiService): RedirectResponse
    {
        if ($konfirmasi_kontrak->status !== StatusKonfirmasiKontrak::Menunggu) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Konfirmasi ini sudah diproses sebelumnya.']);

            return back();
        }

        $keputusan = (string) $request->string('keputusan');
        $diperpanjang = $keputusan === self::KEPUTUSAN_PERPANJANG;
        $kontrakUlangKeK1 = $diperpanjang && $konfirmasi_kontrak->diAkhirSiklusKontrak();

        $data = $request->validate([
            'keputusan' => ['required', Rule::in([self::KEPUTUSAN_PERPANJANG, self::KEPUTUSAN_ANGKAT_TETAP, self::KEPUTUSAN_TIDAK_DIPERPANJANG])],
            // Kontrak ulang ke K1 setelah K5 adalah keadaan khusus — wajib
            // beralasan supaya tercatat kenapa karyawan tidak diangkat tetap.
            'catatan' => [$kontrakUlangKeK1 ? 'required' : 'nullable', 'string', 'max:500'],
            // "after:tanggal_batas" (bukan "after:today") supaya konfirmasi
            // yang telat diproses (mis. karyawan yang periodenya sudah
            // menunggak beberapa siklus) tetap bisa diisi dengan tanggal
            // kontrak baru yang secara historis benar, walau tanggal itu
            // sendiri sudah lewat dari hari ini.
            'tanggal_akhir_kontrak_baru' => [
                $diperpanjang ? 'required' : 'nullable',
                'date',
                'after:'.$konfirmasi_kontrak->tanggal_batas->toDateString(),
            ],
        ], [
            'catatan.required' => 'Alasan wajib diisi untuk kontrak ulang ke K1 setelah K5.',
        ]);

        if ($keputusan === self::KEPUTUSAN_ANGKAT_TETAP) {
            $periodeCutiService->angkatKaryawanTetap($konfirmasi_kontrak, $request->user()->karyawan, $data['catatan'] ?? null);

            Inertia::flash('toast', ['type' => 'success', 'message' => 'Karyawan berhasil diangkat menjadi karyawan tetap.']);

            return back();
        }

        $periodeCutiService->konfirmasiPerpanjangan(
            $konfirmasi_kontrak,
            $request->user()->karyawan,
            $diperpanjang,
            $data['catatan'] ?? null,
            $data['tanggal_akhir_kontrak_baru'] ?? null,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Konfirmasi perpanjangan kontrak berhasil disimpan.']);

        return back();
    }

    /**
     * Perpanjang 1 tahun untuk konfirmasi yang dicentang, atau `semua`
     * konfirmasi yang masih menunggu (lintas halaman pagination).
     */
    public function perpanjangMassal(Request $request, PeriodeCutiService $periodeCutiService): RedirectResponse
    {
        $data = $request->validate([
            'semua' => ['boolean'],
            'konfirmasi_ids' => [Rule::requiredIf(fn () => ! $request->boolean('semua')), 'array'],
            'konfirmasi_ids.*' => ['integer', 'distinct', 'exists:konfirmasi_kontrak_cutis,id'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], [
            'konfirmasi_ids.required' => 'Pilih minimal satu karyawan.',
        ]);

        $konfirmasis = KonfirmasiKontrakCuti::query()
            ->with(['saldoCuti', 'karyawan'])
            ->where('status', StatusKonfirmasiKontrak::Menunggu)
            ->when(! $request->boolean('semua'), fn ($query) => $query->whereKey($data['konfirmasi_ids']))
            ->get();

        if ($konfirmasis->isEmpty()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Konfirmasi yang dipilih sudah diproses sebelumnya.']);

            return back();
        }

        $jumlah = $periodeCutiService->perpanjangSatuTahunMassal($konfirmasis, $request->user()->karyawan, $data['catatan'] ?? null);
        $dilewatiAkhirK5 = $konfirmasis->filter(fn (KonfirmasiKontrakCuti $konfirmasi) => $konfirmasi->diAkhirSiklusKontrak())->count();

        if ($jumlah === 0) {
            Inertia::flash('toast', ['type' => 'error', 'message' => "Tidak ada kontrak yang diperpanjang. {$dilewatiAkhirK5} karyawan di akhir K5 harus diputuskan satu per satu (angkat tetap atau kontrak ulang ke K1)."]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Kontrak {$jumlah} karyawan berhasil diperpanjang 1 tahun."
                .($dilewatiAkhirK5 > 0 ? " {$dilewatiAkhirK5} karyawan di akhir K5 dilewati — putuskan satu per satu (angkat tetap atau kontrak ulang ke K1)." : ''),
        ]);

        return back();
    }
}
