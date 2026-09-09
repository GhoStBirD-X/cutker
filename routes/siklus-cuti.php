<?php

use App\Http\Controllers\Cuti\KompensasiCutiController;
use App\Http\Controllers\Cuti\KonfirmasiKontrakController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:hrd|admin', 'karyawan.linked'])->prefix('cuti')->name('cuti.')->group(function () {
    Route::get('konfirmasi-kontrak', [KonfirmasiKontrakController::class, 'index'])->name('konfirmasi-kontrak.index');
    Route::post('konfirmasi-kontrak/{konfirmasi_kontrak}', [KonfirmasiKontrakController::class, 'konfirmasi'])->name('konfirmasi-kontrak.konfirmasi');

    Route::get('kompensasi', [KompensasiCutiController::class, 'index'])->name('kompensasi.index');
    Route::post('kompensasi/{kompensasi_cuti}', [KompensasiCutiController::class, 'proses'])->name('kompensasi.proses');
});
