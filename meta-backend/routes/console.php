<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Semester start: September 1 and February 1 at midnight.
Schedule::command('scores:snapshot')
    ->cron('0 0 1 2,9 *')
    ->withoutOverlapping();

// Monthly academy sync: 2nd of each month at midnight
Schedule::command('academies:sync')
    ->monthlyOn(2, '00:00')
    ->withoutOverlapping();
