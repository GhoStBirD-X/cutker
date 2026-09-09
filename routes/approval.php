<?php

use App\Http\Controllers\Approval\ApprovalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:kepala_bagian|hrd|manager', 'karyawan.linked'])->prefix('approval')->name('approval.')->group(function () {
    Route::get('/', [ApprovalController::class, 'index'])->name('index');
    Route::get('/{approval}', [ApprovalController::class, 'show'])->name('show');
    Route::post('/{approval}/approve', [ApprovalController::class, 'approve'])->name('approve');
    Route::post('/{approval}/reject', [ApprovalController::class, 'reject'])->name('reject');
});
