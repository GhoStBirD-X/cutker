<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('cuti:reset-saldo-tahunan')->yearlyOn(1, 1)->withoutOverlapping();
Schedule::command('cuti:proses-siklus-tahunan')->daily()->withoutOverlapping();
Schedule::command('libur:sync-nasional')->yearlyOn(1, 1)->withoutOverlapping();
Schedule::call(fn () => Artisan::call('libur:sync-nasional', ['--tahun' => now()->year + 1]))->yearlyOn(12, 1);
