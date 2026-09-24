<?php

namespace App\Exports;

use App\Support\ExcelStyler;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KaryawanImportDataSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['20250001', 'Budi Santoso', 'budi.santoso@pabrik.com', '081234567890', 'laki_laki', 'Produksi', 'Operator Produksi', '2019-03-01', 'aktif', 'tetap', ''],
            ['20250002', 'Siti Aminah', 'siti.aminah@pabrik.com', '081298765432', 'perempuan', 'Quality Control', 'Staff Quality Control', '2022-07-15', 'aktif', 'kontrak', '2026-07-15'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['nip', 'nama', 'email', 'no_hp', 'jenis_kelamin', 'departemen', 'jabatan', 'tanggal_masuk', 'status', 'tipe_karyawan', 'tanggal_akhir_kontrak'];
    }

    public function title(): string
    {
        return 'Data Karyawan';
    }

    public function styles(Worksheet $sheet): ?array
    {
        ExcelStyler::header($sheet, 'A1:K1');
        ExcelStyler::border($sheet, 'A1:K'.$sheet->getHighestRow());

        $sheet->freezePane('A2');

        return null;
    }
}
