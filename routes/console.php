<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('create:anydesk-reminder')
    ->twiceMonthly(1, 15);

Schedule::command('statistics:recalculate')
    ->timezone('America/Toronto')
    ->at('01:00')
    ->daily();

Schedule::command('statistics:recalculate')
    ->timezone('America/Toronto')
    ->at('12:30')
    ->daily();

