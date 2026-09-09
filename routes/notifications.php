<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::post('/{notification}/read', [NotificationController::class, 'read'])->name('read');
    Route::post('/read-all', [NotificationController::class, 'readAll'])->name('read-all');
});
