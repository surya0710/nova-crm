<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('recruitment:process-integration-retries')->everyFiveMinutes();

Schedule::command('projects:generate-recurring-tasks')->hourly();

Schedule::command('schedule:heartbeat')->everyMinute();
