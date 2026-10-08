<?php

namespace App\Imports;

use App\Models\SaldoCuti;
use Illuminate\Database\Eloquent\Builder;
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
 * Hanya mengoreksi baris saldo aktif yang sudah ada. Kalau kolom `id`
 * terisi, baris dicocokkan lewat id dan kolom NPK ikut dicek supaya baris
 * yang tergeser/tertukar di Excel tidak diam-diam mengubah saldo orang
 * lain. Kalau kolom `id` kosong/tidak ada, baris dicocokkan lewat NPK +
 * jenis cuti (ditambah kolom periode bila karyawan punya lebih dari satu
 * saldo aktif untuk jenis cuti yang sama).
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
        $saldoTanpaId = $this->saldoAktifPerNpkDanJenis($rows);
        $sudahDibaca = [];

        foreach ($rows as $index => $row) {
            $baris = $index + 2;
            $errors = [];

            $npk = $this->npk($row);
            $isiId = trim((string) ($row['id'] ?? ''));

            if ($isiId !== '') {
                $saldo = is_numeric($isiId) ? $saldos->get((int) $isiId) : null;

                if ($saldo === null) {
                    $this->failures[] = ['row' => $baris, 'errors' => ["Baris saldo dengan id {$isiId} tidak ditemukan. Kosongkan kolom id untuk mencocokkan lewat NPK + jenis cuti."]];

                    continue;
                }

                if ($npk !== $saldo->karyawan->nip) {
                    $errors[] = "NPK tidak cocok dengan id saldo ini (seharusnya {$saldo->karyawan->nip}).";
                }
            } else {
                [$saldo, $galat] = $this->cariTanpaId($saldoTanpaId, $npk, $row);

                if ($saldo === null) {
                    $this->failures[] = ['row' => $baris, 'errors' => [$galat]];

                    continue;
                }
            }

            if (isset($sudahDibaca[$saldo->id])) {
                $this->failures[] = ['row' => $baris, 'errors' => ["Baris saldo yang sama sudah ada di baris {$sudahDibaca[$saldo->id]}."]];

                continue;
            }

            $sudahDibaca[$saldo->id] = $baris;

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
                'periode' => $this->labelPeriode($saldo),
                'sebelum' => $sebelum,
                'sesudah' => $sesudah,
            ];
        }
    }

    /**
     * Saldo aktif milik NPK yang barisnya tidak menyertakan id, dikelompokkan
     * per "npk|jenis cuti" (nama jenis cuti tanpa membedakan huruf besar/kecil).
     *
     * @param  Collection<int, Collection<string, mixed>>  $rows
     * @return Collection<string, Collection<int, SaldoCuti>>
     */
    private function saldoAktifPerNpkDanJenis(Collection $rows): Collection
    {
        $npks = $rows
            ->filter(fn (Collection $row): bool => trim((string) ($row['id'] ?? '')) === '')
            ->map(fn (Collection $row): string => $this->npk($row))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($npks === []) {
            return collect();
        }

        return SaldoCuti::query()
            ->aktif()
            ->with(['karyawan', 'jenisCuti'])
            ->whereHas('karyawan', fn (Builder $query) => $query->whereIn('nip', $npks))
            ->get()
            ->groupBy(fn (SaldoCuti $saldo): string => $this->kunci($saldo->karyawan->nip, $saldo->jenisCuti->nama_jenis));
    }

    /**
     * @param  Collection<string, Collection<int, SaldoCuti>>  $saldoTanpaId
     * @param  Collection<string, mixed>  $row
     * @return array{0: SaldoCuti|null, 1: string|null}
     */
    private function cariTanpaId(Collection $saldoTanpaId, string $npk, Collection $row): array
    {
        $jenisCuti = trim((string) ($row['jenis_cuti'] ?? ''));

        if ($npk === '' || $jenisCuti === '') {
            return [null, 'Kolom id kosong, jadi NPK dan jenis_cuti wajib diisi untuk mencocokkan baris saldo.'];
        }

        $kandidat = $saldoTanpaId->get($this->kunci($npk, $jenisCuti), collect());

        if ($kandidat->count() > 1) {
            $periode = mb_strtolower(trim((string) ($row['periode'] ?? '')));
            $kandidat = $kandidat->filter(fn (SaldoCuti $saldo): bool => mb_strtolower($this->labelPeriode($saldo)) === $periode);

            if ($kandidat->count() !== 1) {
                return [null, "NPK {$npk} punya lebih dari satu saldo aktif {$jenisCuti}. Isi kolom periode (mis. \"Periode ke-2\" atau \"Tahun 2026\") atau kolom id."];
            }
        }

        $saldo = $kandidat->first();

        return $saldo !== null
            ? [$saldo, null]
            : [null, "Saldo aktif {$jenisCuti} untuk NPK {$npk} tidak ditemukan."];
    }

    /**
     * @param  Collection<string, mixed>  $row
     */
    private function npk(Collection $row): string
    {
        return trim((string) ($row['npk'] ?? $row['nip'] ?? ''));
    }

    private function kunci(string $npk, string $jenisCuti): string
    {
        return $npk.'|'.mb_strtolower(trim($jenisCuti));
    }

    private function labelPeriode(SaldoCuti $saldo): string
    {
        return $saldo->periode_ke ? "Periode ke-{$saldo->periode_ke}" : "Tahun {$saldo->tahun}";
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
