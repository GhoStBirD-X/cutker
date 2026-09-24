<?php

namespace App\Exports;

use App\Enums\StatusPengajuan;
use App\Models\PengajuanCuti;
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
 * @implements WithMapping<PengajuanCuti>
 */
class PengajuanCutiExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    private const KOLOM_JUMLAH_HARI = 'F';

    private const KOLOM_STATUS = 'G';

    /**
     * Warna status per baris (indeks 0 = baris data pertama), diisi saat
     * map() supaya bisa dipakai lagi di styles(). Palet mengikuti badge
     * status yang sama dengan resources/js/components/status-badge.tsx
     * (menunggu=amber, disetujui=emerald, ditolak=red, dibatalkan=abu-abu)
     * — kalau warna di sana berubah, sesuaikan juga warnaStatus() di bawah.
     *
     * @var list<array{fill: string, teks: string}>
     */
    private array $warnaStatus = [];

    /**
     * @param  Builder<PengajuanCuti>  $query
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
        return ['Nama Karyawan', 'Departemen', 'Jenis Cuti', 'Tanggal Mulai', 'Tanggal Selesai', 'Jumlah Hari', 'Status', 'Tanggal Pengajuan'];
    }

    /**
     * @param  PengajuanCuti  $pengajuan
     * @return array<int, int|string>
     */
    public function map($pengajuan): array
    {
        $this->warnaStatus[] = $this->warnaUntuk($pengajuan->status);

        return [
            $pengajuan->karyawan->nama,
            $pengajuan->karyawan->departemen->nama_departemen,
            $pengajuan->jenisCuti->nama_jenis,
            $pengajuan->tanggal_mulai->toDateString(),
            $pengajuan->tanggal_selesai->toDateString(),
            $pengajuan->jumlah_hari,
            $pengajuan->status->label(),
            $pengajuan->tanggal_pengajuan->toDateTimeString(),
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        $highestRow = $sheet->getHighestRow();

        ExcelStyler::header($sheet, 'A1:H1');
        ExcelStyler::border($sheet, "A1:H{$highestRow}");

        $sheet->getStyle(self::KOLOM_JUMLAH_HARI.'2:'.self::KOLOM_JUMLAH_HARI.$highestRow)
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        foreach ($this->warnaStatus as $indeks => $warna) {
            $baris = $indeks + 2;
            ExcelStyler::tandaiSel($sheet, self::KOLOM_STATUS.$baris, $warna['fill'], $warna['teks']);
        }

        $sheet->setAutoFilter('A1:H1');
        $sheet->freezePane('A2');

        return null;
    }

    /**
     * @return array{fill: string, teks: string}
     */
    private function warnaUntuk(StatusPengajuan $status): array
    {
        return match ($status) {
            StatusPengajuan::Pending => ['fill' => 'FEF3C7', 'teks' => '92400E'], // amber
            StatusPengajuan::Disetujui => ['fill' => 'D1FAE5', 'teks' => '065F46'], // emerald
            StatusPengajuan::Ditolak => ['fill' => 'FEE2E2', 'teks' => '991B1B'], // red
            StatusPengajuan::Dibatalkan => ['fill' => 'F3F4F6', 'teks' => '4B5563'], // abu-abu
        };
    }
}
