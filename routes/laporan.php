<?php

use App\Http\Controllers\Laporan\LaporanCutiController;
use App\Http\Controllers\Laporan\SaldoCutiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:hrd|admin|manager'])->prefix('laporan')->name('laporan.')->group(function () {
    Route::get('/', [LaporanCutiController::class, 'index'])->name('index');
    Route::get('/export/excel', [LaporanCutiController::class, 'exportExcel'])->name('export.excel');
    Route::get('/export/pdf', [LaporanCutiController::class, 'exportPdf'])->name('export.pdf');
});

Route::middleware(['auth', 'verified', 'role:hrd|admin|manager|kepala_bagian'])->prefix('laporan')->name('laporan.')->group(function () {
    Route::get('/saldo-cuti', [SaldoCutiController::class, 'index'])->name('saldo-cuti');
    Route::get('/saldo-cuti/export/excel', [SaldoCutiController::class, 'exportExcel'])->name('saldo-cuti.export.excel');
    Route::get('/saldo-cuti/export/pdf', [SaldoCutiController::class, 'exportPdf'])->name('saldo-cuti.export.pdf');
    Route::get('/saldo-cuti/{saldoCuti}/riwayat', [SaldoCutiController::class, 'riwayat'])->name('saldo-cuti.riwayat');
});
