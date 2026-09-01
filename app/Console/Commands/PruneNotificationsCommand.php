<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;
use Symfony\Component\Console\Command\Command as CommandCodes;

class PruneNotificationsCommand extends Command
{
    protected $signature = 'notifications:prune {--days=7 : Keep notifications created within this many days}';

    protected $description = 'Deletes notifications older than the retention window';

    private const CHUNK_SIZE = 1000;

    public function handle(): int
    {
        $days = $this->option('days');

        if (!is_numeric($days) || (int) $days < 0) {
            $this->error('The --days option must be a non-negative number.');

            return CommandCodes::FAILURE;
        }

        $cutoff = now()->subDays((int) $days);

        $deleted = 0;

        do {
            $deletedInChunk = DatabaseNotification::query()
                ->where('created_at', '<', $cutoff)
                ->limit(self::CHUNK_SIZE)
                ->delete();

            $deleted += $deletedInChunk;
        } while ($deletedInChunk > 0);

        $this->info("Deleted {$deleted} notification(s) created before {$cutoff->toDateTimeString()}.");

        return CommandCodes::SUCCESS;
    }
}
