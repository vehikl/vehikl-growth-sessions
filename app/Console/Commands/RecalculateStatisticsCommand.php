<?php

namespace App\Console\Commands;

use App\Actions\Statistics;
use App\GrowthSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Command\Command as CommandCodes;

class RecalculateStatisticsCommand extends Command
{
    protected $signature = 'statistics:recalculate';

    protected $description = 'Recalculates the statistics by clearing the cache';

    public function handle()
    {
        $this->info('Recalculating participation statistics...');

        $start_date = GrowthSession::query()->orderBy('date')->first()?->date?->toDateString();
        $end_date = today()->toDateString();

        if (is_null($start_date) || is_null($end_date)) {
            return CommandCodes::SUCCESS;
        }

        app(Statistics::class)->getFormattedStatisticsFor($start_date, $end_date);

        return CommandCodes::SUCCESS;
    }
}
