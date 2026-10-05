<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Gửi deadline reminder mỗi sáng lúc 08:00.
Schedule::command('notifications:deadline-reminders')->dailyAt('08:00');
