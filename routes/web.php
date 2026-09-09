<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/notifications.php';
require __DIR__.'/siklus-cuti.php';
require __DIR__.'/cuti.php';
require __DIR__.'/approval.php';
require __DIR__.'/master.php';
require __DIR__.'/jadwal-shift.php';
require __DIR__.'/laporan.php';
require __DIR__.'/users.php';
