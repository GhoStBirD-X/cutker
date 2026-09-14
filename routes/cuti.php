<?php

use App\Http\Controllers\Cuti\PengajuanCutiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'karyawan.linked'])->prefix('cuti')->name('cuti.')->group(function () {
    Route::get('/', [PengajuanCutiController::class, 'index'])->name('index');
    Route::get('/ajukan', [PengajuanCutiController::class, 'create'])->name('create');
    Route::get('/ajukan-mendadak', [PengajuanCutiController::class, 'createMendadak'])->name('create-mendadak');
    Route::post('/', [PengajuanCutiController::class, 'store'])->name('store');
    Route::get('/{pengajuan}', [PengajuanCutiController::class, 'show'])->name('show');
    Route::patch('/{pengajuan}/batalkan', [PengajuanCutiController::class, 'batalkan'])->name('batalkan');
});
