<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('ournotes:sync-bdon-events --no-images')->everySixHours()->withoutOverlapping();
Schedule::command('ournotes:sync-bdon-gachas --no-images')->everySixHours()->withoutOverlapping();
