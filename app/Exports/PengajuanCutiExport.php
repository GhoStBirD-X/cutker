<?php

namespace App\Exports;

use App\Models\PengajuanCuti;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<PengajuanCuti>
 */
class PengajuanCutiExport implements FromQuery, WithHeadings, WithMapping
{
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
}
