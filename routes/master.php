<?php

use App\Http\Controllers\Master\AlasanCutiController;
use App\Http\Controllers\Master\DepartemenController;
use App\Http\Controllers\Master\HariLiburController;
use App\Http\Controllers\Master\JabatanController;
use App\Http\Controllers\Master\JenisCutiController;
use App\Http\Controllers\Master\KaryawanController;
use App\Http\Controllers\Master\SaldoCutiController;
use App\Http\Controllers\Master\ShiftController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:hrd|admin'])->prefix('master')->name('master.')->group(function () {
    Route::resource('karyawan', KaryawanController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('karyawan/import', [KaryawanController::class, 'import'])->name('karyawan.import');
    Route::get('karyawan/import/template', [KaryawanController::class, 'importTemplate'])->name('karyawan.import.template');
    Route::resource('departemen', DepartemenController::class)->parameters(['departemen' => 'departemen'])->only(['index', 'store', 'update', 'destroy']);
    Route::resource('jabatan', JabatanController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('jenis-cuti', JenisCutiController::class)->parameters(['jenis-cuti' => 'jenis_cuti'])->only(['index', 'store', 'update', 'destroy']);
    Route::resource('alasan-cuti', AlasanCutiController::class)->parameters(['alasan-cuti' => 'alasan_cuti'])->only(['index', 'store', 'update', 'destroy']);
    Route::resource('shift', ShiftController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('hari-libur', HariLiburController::class)->parameters(['hari-libur' => 'hari_libur'])->only(['index', 'store', 'update', 'destroy']);
    Route::resource('saldo-cuti', SaldoCutiController::class)->parameters(['saldo-cuti' => 'saldo_cuti'])->only(['index', 'store', 'update']);
});
