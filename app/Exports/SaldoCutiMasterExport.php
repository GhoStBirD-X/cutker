<?php

namespace App\Exports;

use App\Models\SaldoCuti;
use App\Support\ExcelStyler;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export saldo aktif Master Saldo Cuti (mengikuti filter halaman) yang
 * sekaligus berfungsi sebagai template import: HRD cukup mengubah kolom
 * kuota/terpakai/sisa lalu mengunggahnya kembali (lihat SaldoCutiImport).
 * Kolom A–F hanya informasi & diberi latar abu-abu; kolom id dipakai untuk
 * mencocokkan baris sehingga tidak boleh diubah. Kolom G–I (yang diisi HRD)
 * diberi latar kuning. WithStrictNullComparison wajib: tanpa itu angka 0
 * ditulis sebagai sel kosong, padahal kosong berarti "tanpa batas".
 *
 * @implements WithMapping<SaldoCuti>
 */
class SaldoCutiMasterExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStrictNullComparison, WithStyles, WithTitle
{
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
        return ['id', 'nip', 'nama', 'departemen', 'jenis_cuti', 'periode', 'kuota', 'terpakai', 'sisa'];
    }

    /**
     * @param  SaldoCuti  $saldo
     * @return array<int, int|string|null>
     */
    public function map($saldo): array
    {
        return [
            $saldo->id,
            $saldo->karyawan->nip,
            $saldo->karyawan->nama,
            $saldo->karyawan->departemen?->nama_departemen,
            $saldo->jenisCuti->nama_jenis,
            $saldo->periode_ke ? "Periode ke-{$saldo->periode_ke}" : "Tahun {$saldo->tahun}",
            $saldo->kuota,
            $saldo->terpakai,
            $saldo->sisa,
        ];
    }

    public function title(): string
    {
        return 'Saldo Cuti';
    }

    public function styles(Worksheet $sheet): ?array
    {
        $highestRow = $sheet->getHighestRow();

        ExcelStyler::header($sheet, 'A1:I1');
        ExcelStyler::border($sheet, "A1:I{$highestRow}");

        if ($highestRow > 1) {
            $sheet->getStyle("A2:F{$highestRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');
            $sheet->getStyle("A2:F{$highestRow}")->getFont()->getColor()->setRGB('6B7280');
            $sheet->getStyle("G2:I{$highestRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF9C3');
        }

        $sheet->getComment('A1')->getText()->createText('Jangan diubah — dipakai untuk mencocokkan baris saat import.');
        $sheet->getComment('G1')->getText()->createText('Isi jumlah hari. Kosongkan untuk jenis cuti tanpa batas.');
        $sheet->getComment('H1')->getText()->createText('Jumlah hari yang sudah terpakai (wajib, isi 0 kalau belum ada).');
        $sheet->getComment('I1')->getText()->createText('Sisa hari. Kosongkan untuk jenis cuti tanpa batas.');
        $sheet->setAutoFilter("A1:I{$highestRow}");
        $sheet->freezePane('D2');

        return null;
    }
}
