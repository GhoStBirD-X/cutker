<?php

use App\Http\Controllers\JadwalShift\JadwalShiftController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('jadwal-shift')->name('jadwal-shift.')->group(function () {
    Route::get('/', [JadwalShiftController::class, 'index'])->name('index');

    Route::middleware('role:hrd|admin|koordinator_shift')->group(function () {
        Route::post('/', [JadwalShiftController::class, 'store'])->name('store');
        Route::post('/hapus-massal', [JadwalShiftController::class, 'destroyMassal'])->name('hapus-massal');
        Route::patch('/{jadwalShift}/lembur', [JadwalShiftController::class, 'updateLembur'])->name('update-lembur');
        Route::delete('/{jadwalShift}', [JadwalShiftController::class, 'destroy'])->name('destroy');
    });
});
