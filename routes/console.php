<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\Sla;

// Inspiring command (this is an example)
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly(); // Runs hourly

// SLA Task command
Artisan::command('sla:run', function () {
    Artisan::call('app:sla');
})->everyMinute();
