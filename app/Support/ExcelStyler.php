<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Gaya tabel baku untuk semua sheet ekspor Excel di aplikasi ini (Master
 * Data, Laporan Cuti, Laporan Saldo Cuti) supaya setiap file yang diunduh
 * punya tampilan yang sama & konsisten dengan warna brand aplikasi
 * (indigo), bukan tabel polos bawaan PhpSpreadsheet.
 */
class ExcelStyler
{
    public const WARNA_HEADER = '4F46E5';

    public const WARNA_BORDER = 'D1D5DB';

    public static function header(Worksheet $sheet, string $range, int $tinggiBaris = 20, bool $wrapText = false): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::WARNA_HEADER);
        $sheet->getStyle($range)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText($wrapText);
        $sheet->getRowDimension(1)->setRowHeight($tinggiBaris);
    }

    public static function border(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::WARNA_BORDER);
    }

    /**
     * Warnai satu range sel (fill lembut + teks tebal berwarna senada) —
     * dipakai untuk menandai status/urgensi baris (mis. sisa cuti kritis,
     * status pengajuan) secara konsisten dengan badge di halaman web.
     */
    public static function tandaiSel(Worksheet $sheet, string $range, string $warnaFill, string $warnaTeks): void
    {
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($warnaFill);
        $sheet->getStyle($range)->getFont()->setBold(true)->getColor()->setRGB($warnaTeks);
    }
}
