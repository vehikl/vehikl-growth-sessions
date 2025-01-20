<?php

namespace App\Console\Commands;

use App\Actions\Statistics;
use App\GrowthSession;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandCodes;

class RecalculateStatisticsCommand extends Command
{
    protected $signature = 'statistics:recalculate';

    protected $description = 'Recalculates the statistics by clearing the cache';

    public function handle()
    {
        $this->info('Recalculating participation statistics...');

        $oldestGrowthSessionDate = GrowthSession::query()->orderBy('date')->first()?->date;

        if (!$oldestGrowthSessionDate) {
            return CommandCodes::SUCCESS;
        }

        $startDate = $oldestGrowthSessionDate->toDateString();
        $endDate = today()->toDateString();

        \Cache::forget("statistics-{$startDate}-{$endDate}");
        app(Statistics::class)->getFormattedStatisticsFor($startDate, $endDate);

        return CommandCodes::SUCCESS;
    }
}
