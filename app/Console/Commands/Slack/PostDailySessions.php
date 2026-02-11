<?php

namespace App\Console\Commands\Slack;

use App\Models\GrowthSession;
use App\Services\Slack\SessionPoster;
use Illuminate\Console\Command;

class PostDailySessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slack:post-daily-sessions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get a list of the growth sessions so far for today, and post them into slack';

    /**
     * Execute the console command.
     */
    public function handle(SessionPoster $slack)
    {
        GrowthSession::query()
            ->where('date', now()->timezone('America/Toronto')->toDateString())
            ->each(function (GrowthSession $session) use ($slack) {
                $slack->post($session);
            });
    }
}
