<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        //
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule
            ->command('create:anydesk-reminder')
            ->twiceMonthly(1, 15);

        $schedule
            ->command('statistics:recalculate')
            ->timezone('America/Toronto')
            ->at('01:00')
            ->daily();

        $schedule
            ->command('statistics:recalculate')
            ->timezone('America/Toronto')
            ->at('12:30')
            ->daily();
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
