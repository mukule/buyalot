<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Release cart reservations whose TTL has elapsed and return stock to variants.
// Runs every minute so that the 3-minute initial window and 10-minute retry window
// are respected with at most ~1 minute of drift.
Schedule::command('reservations:release-expired')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
