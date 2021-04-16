<?php

namespace Database\Seeders;

use App\GrowthSession;
use App\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class GrowthSessionSeeder extends Seeder
{
    public function run()
    {
        $MONDAY = 1;
        $monday = today()->isDayOfWeek($MONDAY) ? CarbonImmutable::today() : CarbonImmutable::parse('Last Monday');
        $numberOfFakeGrowthSessions = 5;
        for ($i = 0; $i < $numberOfFakeGrowthSessions; $i++) {
            $growthSession = GrowthSession::factory()->create([
                'date' => $monday->addDays(random_int(0, 4)),
                'start_time' => Carbon::createFromTime(random_int(15, 16))
            ]);
            $owner = User::factory()->vehiklMember()->create();
            $owner->growthSessions()->attach($growthSession, ['user_type' => User::OWNER]);
        }
    }
}
