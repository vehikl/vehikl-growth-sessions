<?php

namespace App\Console\Commands;

use App\GrowthSession;
use App\User;
use App\UserType;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class AnydeskReminderSessionsCommand extends Command
{
    protected $signature = 'create:anydesk-reminder';

    protected $description = 'Creates reminder mobs for anydesks. You can change the text of the reminder by updating anydeskReminder.json. (The file is optional)';

    protected $vehikl;
    protected $date;

    public function handle()
    {
        $this->vehikl = User::query()
            ->whereIn('github_nickname', config('auth.vehikl_names'))
            ->firstOrFail();

        $this->date = today()->weekday() === CarbonInterface::FRIDAY
            ? today()
            : Carbon::parse('next Friday');

        $doesReminderAlreadyExist = $this->vehikl->growthSessions()
            ->where('date', $this->date->format('Y-m-d'))
            ->exists();

        if ($doesReminderAlreadyExist) {
            return;
        }

        $defaultAttributes = $this->getDefaultAttributes();
        $overrides = [];
        if (Storage::has('anydeskReminder.json')) {
            $overrides = json_decode(Storage::get('anydeskReminder.json'), TRUE);
        }
        $newGrowthSession = GrowthSession::query()->create([...$defaultAttributes, ...$overrides]);

        $this->vehikl->growthSessions()->attach($newGrowthSession, ['user_type_id' => UserType::OWNER_ID]);
        return Command::SUCCESS;
    }

    public function getDefaultAttributes(): array
    {
        return [
            'title' => 'Updating Workstations',
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
            'date' => $this->date,
            'start_time' => '4:30 pm',
            'end_time' => '5:00 pm',
            'attendee_limit' => 0,
            'is_public' => FALSE,
            'allow_watchers' => FALSE,
        ];
    }
}
