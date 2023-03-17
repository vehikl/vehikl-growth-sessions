<?php

namespace App\Console\Commands;

use App\GrowthSession;
use App\User;
use App\UserType;
use Illuminate\Console\Command;

class AnydeskReminderSessionsCommand extends Command
{
    protected $signature = 'create:anydesk-reminder';

    protected $description = 'Creates reminder mobs for anydesks';

    public function handle()
    {

        $vehikl = User::query()->where('email', 'go@vehikl.com')->firstOrFail();
        $newGrowthSession = GrowthSession::query()->create([
            'title' => 'foobar',
            'topic' => 'foobar',
            'location' => 'foobar',
            'date' => today(),
            'start_time' => '4:30 pm',
            'end_time' => '5:00 pm',
            'attendee_limit' => 0,
            'is_public' => FALSE,
            'allow_watchers' => FALSE,
        ]);

        $vehikl->growthSessions()->attach($newGrowthSession, ['user_type_id' => UserType::OWNER_ID]);
        return Command::SUCCESS;
    }
}
