<?php

namespace App\Exports;

use App\Models\SaldoCuti;
use App\Support\ExcelStyler;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * @implements WithMapping<SaldoCuti>
 */
class SaldoCutiExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    private const KOLOM_KUOTA = 'F';

    private const KOLOM_TERPAKAI = 'G';

    private const KOLOM_SISA = 'H';

    /**
     * Warna sel "Sisa" per baris (indeks 0 = baris data pertama), diisi
     * saat map() supaya bisa dipakai lagi di styles() tanpa query ulang.
     * Nilainya {fill: warna latar, teks: warna font} mengikuti palet
     * urgensi saldo yang sama dengan resources/js/lib/saldo-severity.ts
     * (unlimited=sky, aman=blue, menipis=amber, minus=red) — kalau ambang
     * batas di sana berubah, sesuaikan juga severityUntuk() di bawah.
     *
     * @var list<array{fill: string, teks: string}>
     */
    private array $warnaSisa = [];

    /**
     * @param  Builder<SaldoCuti>  $query
     */
    public function __construct(
        protected Builder $query,
    ) {}

    public function query(): Builder
    {
        return $this->query;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['NIP', 'Nama Karyawan', 'Departemen', 'Jabatan', 'Jenis Cuti', 'Kuota', 'Terpakai', 'Sisa'];
    }

    /**
     * @param  SaldoCuti  $saldo
     * @return array<int, int|string>
     */
    public function map($saldo): array
    {
        $this->warnaSisa[] = $this->severityUntuk($saldo->sisa, $saldo->kuota);

        return [
            $saldo->karyawan->nip,
            $saldo->karyawan->nama,
            $saldo->karyawan->departemen->nama_departemen,
            $saldo->karyawan->jabatan->nama_jabatan,
            $saldo->jenisCuti->nama_jenis,
            $saldo->kuota ?? 'Tanpa Batas',
            $saldo->terpakai,
            $saldo->sisa ?? 'Tanpa Batas',
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        $highestRow = $sheet->getHighestRow();

        ExcelStyler::header($sheet, 'A1:H1');
        ExcelStyler::border($sheet, "A1:H{$highestRow}");

        $sheet->getStyle(self::KOLOM_KUOTA.'2:'.self::KOLOM_SISA.$highestRow)
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        foreach ($this->warnaSisa as $indeks => $warna) {
            $baris = $indeks + 2;
            ExcelStyler::tandaiSel($sheet, self::KOLOM_SISA.$baris, $warna['fill'], $warna['teks']);
        }

        $sheet->setAutoFilter('A1:H1');
        $sheet->freezePane('A2');

        return null;
    }

    /**
     * @return array{fill: string, teks: string}
     */
    private function severityUntuk(?int $sisa, ?int $kuota): array
    {
        if ($sisa === null || $kuota === null) {
            return ['fill' => 'E0F2FE', 'teks' => '075985']; // sky — tanpa batas
        }

        if ($sisa < 0) {
            return ['fill' => 'FEE2E2', 'teks' => '991B1B']; // red — minus
        }

        $rasio = $kuota > 0 ? $sisa / $kuota : ($sisa > 0 ? 1 : 0);

        if ($rasio <= 0.25) {
            return ['fill' => 'FEF3C7', 'teks' => '92400E']; // amber — menipis
        }

        return ['fill' => 'DBEAFE', 'teks' => '1E40AF']; // blue — aman
    }
}
