<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait HasPerPage
{
    /**
     * Ambil jumlah baris per halaman dari query string `per_page`, dibatasi
     * ke pilihan yang tersedia di selektor supaya tidak bisa disalahgunakan
     * untuk menarik seluruh tabel sekaligus (mis. ?per_page=999999).
     */
    protected function resolvePerPage(Request $request, int $default = 10): int
    {
        $perPage = $request->integer('per_page');

        return in_array($perPage, [10, 20, 30, 50], true) ? $perPage : $default;
    }
}
