<?php

namespace App\Console\Commands;

use App\GrowthSession;
use App\User;
use App\UserType;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AnydeskReminderSessionsCommand extends Command
{
    protected $signature = 'create:anydesk-reminder';

    protected $description = 'Creates reminder mobs for anydesks';

    public function handle()
    {
        $vehikl = User::query()->where('email', 'go@vehikl.com')->firstOrFail();

        $date = today()->weekday() === CarbonInterface::FRIDAY
            ? today()
            : Carbon::parse('next Friday');

        $doesReminderAlreadyExist = $vehikl->growthSessions()
            ->where('date', $date->format('Y-m-d'))
            ->exists();
        if ($doesReminderAlreadyExist) {
            return;
        }
        $newGrowthSession = GrowthSession::query()->create([
            'title' => 'Updating Workstations (but not AnyDesk)',
            'topic' =>
                "Friendly reminder to check if anything needs updating on your squad's Workstation before breaking out for the weekend.

A few things to update:
- Anydesk Password (If it's been more than a month since the last update)
- Mac User Password (If it's been more than a month since the last update)
- Docker
- Chrome
- PHPStorm
- Brew
",
            'location' => 'N/A',
            'date' => $date,
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
