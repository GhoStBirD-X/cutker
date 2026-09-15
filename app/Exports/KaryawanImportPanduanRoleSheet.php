<?php

namespace App\Exports;

use App\Support\PetaRoleJabatan;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Sheet panduan supaya HRD tahu penulisan kolom "jabatan" yang persis
 * (tidak boleh beda ejaan) untuk mendapatkan role login otomatis yang
 * sesuai. Isinya dibaca langsung dari PetaRoleJabatan::PETA supaya tidak
 * pernah beda dengan logika import yang sebenarnya.
 */
class KaryawanImportPanduanRoleSheet implements FromArray, WithHeadings, WithTitle
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
}
