<?php

namespace App\Exports;

use App\Support\PetaRoleJabatan;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet panduan supaya HRD tahu penulisan kolom "jabatan" yang persis
 * (tidak boleh beda ejaan) untuk mendapatkan role login otomatis yang
 * sesuai. Isinya dibaca langsung dari PetaRoleJabatan::PETA supaya tidak
 * pernah beda dengan logika import yang sebenarnya.
 */
class KaryawanImportPanduanRoleSheet implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        $baris = [];

        foreach (PetaRoleJabatan::PETA as $namaJabatan => $role) {
            $baris[] = [str_replace('Hrd', 'HRD', ucwords($namaJabatan)), $role];
        }

        $baris[] = ['(nama jabatan lainnya)', PetaRoleJabatan::ROLE_DEFAULT.' (default)'];

        return $baris;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Tulis persis di kolom "jabatan" untuk dapat role ini otomatis',
            'Role akun login yang dibuat',
        ];
    }

    public function title(): string
    {
        return 'Panduan Role';
    }

    public function styles(Worksheet $sheet): ?array
    {
        $sheet->getStyle('A1:B1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4F46E5');
        $sheet->getStyle('A1:B1')->getAlignment()->setVertical('center')->setWrapText(true);
        $sheet->getRowDimension(1)->setRowHeight(32);

        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle("A1:B{$highestRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');

        // Baris terakhir ("nama jabatan lainnya") cuma penjelasan, bukan
        // nilai literal — dibedakan pakai italic + abu-abu.
        $sheet->getStyle("A{$highestRow}:B{$highestRow}")->getFont()->setItalic(true)->getColor()->setRGB('6B7280');

        $sheet->freezePane('A2');

        return null;
    }
}
