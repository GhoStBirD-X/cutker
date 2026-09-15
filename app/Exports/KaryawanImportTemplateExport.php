<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class KaryawanImportTemplateExport implements Export, WithMultipleSheets
{
    /**
     * @return array<int, object>
     */
    public function sheets(): array
    {
        return [
            new KaryawanImportDataSheet,
            new KaryawanImportPanduanRoleSheet,
        ];
    }
}
