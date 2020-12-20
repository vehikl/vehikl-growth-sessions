<?php

namespace App\Console\Commands;

use App\SocialMob as GrowthSession;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoGrowthSessionCommand extends Command
{
    protected $signature = 'mob:auto';

    protected $description = 'Creates the automatic mobs of the week';

    public function handle()
    {
        $this->generateQuesMobs();
    }

    private function generateQuesMobs(): void
    {
        $host = User::query()->where('email', 'a.frank@vehikl.com')->firstOrFail();
        $dates = collect(['Monday', 'Tuesday', 'Wednesday'])->map(function (string $weekday) {
            return Carbon::parse($weekday)->toDateString();
        });

        $dates->each(function (string $date) use ($host) {
            if ($host->socialMobs()->where('date', $date)->first()) {
                return;
            }
            $host->socialMobs()->save(new GrowthSession([
                'topic' => 'Learn about Unity and C# with the QUES Team',
                'location' => 'discord',
                'date' => $date,
                'start_time' => '4:00 pm',
                'end_time' => '5:00 pm',
            ]));
        });
    }
}
