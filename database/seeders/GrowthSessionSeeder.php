<?php

namespace Database\Seeders;

use App\Models\GrowthSession;
use App\Models\User;
use App\Models\UserType;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;

class GrowthSessionSeeder extends Seeder
{
    public function run()
    {
        $dayOfWeek = today()->dayOfWeek;
        $monday = match (TRUE) {
            $dayOfWeek === CarbonInterface::MONDAY => CarbonImmutable::today(),
            $dayOfWeek < CarbonInterface::MONDAY => CarbonImmutable::parse('Next Monday'),
            $dayOfWeek > CarbonInterface::MONDAY => CarbonImmutable::parse('Last Monday'),
        };

        $numberOfFakeGrowthSessions = 5;
        for ($i = 0; $i < $numberOfFakeGrowthSessions; $i++) {
            $growthSession = GrowthSession::factory()->create([
                'date' => $monday->addDays(random_int(0, 4)),
                'start_time' => Carbon::createFromTime(random_int(15, 16))
            ]);
            $owner = User::factory()->vehiklMember()->create();
            $owner->growthSessions()->attach($growthSession, ['user_type_id' => UserType::OWNER_ID]);
        }

        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(false), [], 'attendees')
            ->create([
                'title' => 'This one has guests',
                'date' => $monday,
            ]);
        User::find(1)->growthSessions()->attach($growthSession, ['user_type_id' => UserType::OWNER_ID]);

        $privateSession = GrowthSession::factory()
            ->create([
                'title' => 'This one is private',
                'date' => $monday->addDays(random_int(0, 4)),
                'is_public' => false
            ]);
        User::find(1)->growthSessions()->attach($privateSession, ['user_type_id' => UserType::OWNER_ID]);
    }
}
