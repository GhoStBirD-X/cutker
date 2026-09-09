<?php

use App\Http\Controllers\Laporan\LaporanCutiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:hrd|admin|manager'])->prefix('laporan')->name('laporan.')->group(function () {
    Route::get('/', [LaporanCutiController::class, 'index'])->name('index');
    Route::get('/export/excel', [LaporanCutiController::class, 'exportExcel'])->name('export.excel');
    Route::get('/export/pdf', [LaporanCutiController::class, 'exportPdf'])->name('export.pdf');
});
