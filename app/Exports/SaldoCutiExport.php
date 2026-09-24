<?php

namespace App\Exports;

use App\Models\SaldoCuti;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<SaldoCuti>
 */
class SaldoCutiExport implements FromQuery, WithHeadings, WithMapping
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
        return ['NIP', 'Nama Karyawan', 'Departemen', 'Jabatan', 'Jenis Cuti', 'Kuota', 'Terpakai', 'Sisa'];
    }

    /**
     * @param  SaldoCuti  $saldo
     * @return array<int, int|string>
     */
    public function map($saldo): array
    {
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
}
