<?php

namespace App\Imports;

use App\Models\SaldoCuti;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Membaca file hasil "Export Excel" Master Saldo Cuti yang sudah diedit HRD
 * dan menyusun daftar perubahan (sebelum → sesudah) TANPA menyimpan apa pun.
 * Penyimpanan baru terjadi setelah HRD mengonfirmasi pratinjau, lewat
 * endpoint update massal yang sama dengan edit langsung di tabel — jadi
 * validasi akhirnya tetap satu pintu.
 *
 * Hanya mengoreksi baris yang sudah ada (dicocokkan lewat kolom `id`);
 * kolom NIP ikut dicek supaya baris yang tergeser/tertukar di Excel tidak
 * diam-diam mengubah saldo orang lain.
 */
class SaldoCutiImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    /**
     * @var list<array{id: int, baris: int, nip: string, nama: string, jenis_cuti: string, periode: string, sebelum: array{kuota: int|null, terpakai: int, sisa: int|null}, sesudah: array{kuota: int|null, terpakai: int, sisa: int|null}}>
     */
    public array $perubahan = [];

    /**
     * @var list<array{row: int, errors: list<string>}>
     */
    public array $failures = [];

    public int $tidakBerubah = 0;

    /**
     * @param  Collection<int, Collection<string, mixed>>  $rows
     */
    public function collection(Collection $rows): void
    {
        $ids = $rows->pluck('id')->filter(fn (mixed $id): bool => is_numeric($id))->map(fn (mixed $id): int => (int) $id)->all();
        $saldos = SaldoCuti::query()->with(['karyawan', 'jenisCuti'])->whereKey($ids)->get()->keyBy('id');
        $sudahDibaca = [];

        foreach ($rows as $index => $row) {
            $baris = $index + 2;
            $errors = [];

            $id = is_numeric($row['id'] ?? null) ? (int) $row['id'] : null;
            $saldo = $id !== null ? $saldos->get($id) : null;

            if ($saldo === null) {
                $this->failures[] = ['row' => $baris, 'errors' => ['Kolom id kosong atau baris saldo tidak ditemukan. Jangan ubah kolom id dari file export.']];

                continue;
            }

            if (isset($sudahDibaca[$id])) {
                $this->failures[] = ['row' => $baris, 'errors' => ["Baris saldo yang sama sudah ada di baris {$sudahDibaca[$id]}."]];

                continue;
            }

            $sudahDibaca[$id] = $baris;

            if (trim((string) ($row['nip'] ?? '')) !== $saldo->karyawan->nip) {
                $errors[] = "NIP tidak cocok dengan id saldo ini (seharusnya {$saldo->karyawan->nip}).";
            }

            [$kuota, $galatKuota] = $this->angka($row['kuota'] ?? null, 'Kuota', boleKosong: true, maksimal: 365);
            [$terpakai, $galatTerpakai] = $this->angka($row['terpakai'] ?? null, 'Terpakai', boleKosong: false);
            [$sisa, $galatSisa] = $this->angka($row['sisa'] ?? null, 'Sisa', boleKosong: true);

            $errors = [...$errors, ...array_filter([$galatKuota, $galatTerpakai, $galatSisa])];

            if ($errors !== []) {
                $this->failures[] = ['row' => $baris, 'errors' => array_values($errors)];

                continue;
            }

            $sebelum = ['kuota' => $saldo->kuota, 'terpakai' => $saldo->terpakai, 'sisa' => $saldo->sisa];
            $sesudah = ['kuota' => $kuota, 'terpakai' => $terpakai, 'sisa' => $sisa];

            if ($sebelum === $sesudah) {
                $this->tidakBerubah++;

                continue;
            }

            $this->perubahan[] = [
                'id' => $saldo->id,
                'baris' => $baris,
                'nip' => $saldo->karyawan->nip,
                'nama' => $saldo->karyawan->nama,
                'jenis_cuti' => $saldo->jenisCuti->nama_jenis,
                'periode' => $saldo->periode_ke ? "Periode ke-{$saldo->periode_ke}" : "Tahun {$saldo->tahun}",
                'sebelum' => $sebelum,
                'sesudah' => $sesudah,
            ];
        }
    }

    /**
     * @return array{0: int|null, 1: string|null}
     */
    private function angka(mixed $nilai, string $label, bool $boleKosong, ?int $maksimal = null): array
    {
        $nilai = is_string($nilai) ? trim($nilai) : $nilai;

        if ($nilai === null || $nilai === '') {
            return $boleKosong ? [null, null] : [null, "{$label} wajib diisi."];
        }

        if (! is_numeric($nilai) || (float) $nilai !== floor((float) $nilai)) {
            return [null, "{$label} '{$nilai}' harus bilangan bulat."];
        }

        $angka = (int) $nilai;

        if ($angka < 0 || ($maksimal !== null && $angka > $maksimal)) {
            return [null, $maksimal !== null ? "{$label} harus di antara 0 dan {$maksimal}." : "{$label} tidak boleh minus."];
        }

        return [$angka, null];
    }
}
