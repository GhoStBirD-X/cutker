<?php

use App\Http\Controllers\Cuti\CutiMassalController;
use App\Http\Controllers\Cuti\KompensasiCutiController;
use App\Http\Controllers\Cuti\KonfirmasiKontrakController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:hrd|admin', 'karyawan.linked'])->prefix('cuti')->name('cuti.')->group(function () {
    Route::get('konfirmasi-kontrak', [KonfirmasiKontrakController::class, 'index'])->name('konfirmasi-kontrak.index');
    Route::post('konfirmasi-kontrak/{konfirmasi_kontrak}', [KonfirmasiKontrakController::class, 'konfirmasi'])->name('konfirmasi-kontrak.konfirmasi');

    Route::get('kompensasi', [KompensasiCutiController::class, 'index'])->name('kompensasi.index');
    Route::post('kompensasi/proses-massal', [KompensasiCutiController::class, 'prosesMassal'])->name('kompensasi.proses-massal');
    Route::post('kompensasi/{kompensasi_cuti}', [KompensasiCutiController::class, 'proses'])->name('kompensasi.proses');

    Route::get('massal', [CutiMassalController::class, 'index'])->name('massal.index');
    Route::get('massal/buat', [CutiMassalController::class, 'create'])->name('massal.create');
    Route::post('massal', [CutiMassalController::class, 'store'])->name('massal.store');
    Route::get('massal/{cuti_massal}', [CutiMassalController::class, 'show'])->name('massal.show');
    Route::post('massal/{cuti_massal}/tambah-karyawan-baru', [CutiMassalController::class, 'tambahKaryawanBaru'])->name('massal.tambah-karyawan-baru');
    Route::patch('massal/{cuti_massal}/batalkan', [CutiMassalController::class, 'batalkan'])->name('massal.batalkan');
});
