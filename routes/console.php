<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// SSMS-UC-500: daily alert to officers about applications past the 14-day target.
// Needs the scheduler (php artisan schedule:work) or run it by hand: php artisan speed:notify-overdue
Schedule::command('speed:notify-overdue')->dailyAt('08:00');
