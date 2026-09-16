<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('emails:send')->everyMinute()->withoutOverlapping();
Schedule::command('emails:unsent')->everyTenMinutes()->withoutOverlapping();
Schedule::command('emails:remove-unconfirmed-subscriber')->everyTenMinutes()->withoutOverlapping();
