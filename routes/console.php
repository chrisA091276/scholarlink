<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('app:deadline-reminders')->dailyAt('08:00')->withoutOverlapping();
Schedule::command('app:renewal-reminders')->dailyAt('08:15')->withoutOverlapping();
Schedule::command('sanctum:prune-expired --hours=24')->daily()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
