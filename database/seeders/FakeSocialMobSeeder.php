<?php

namespace Database\Seeders;

use App\SocialMob;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class FakeSocialMobSeeder extends Seeder
{
    public function run()
    {
        $MONDAY = 1;
        $monday = today()->isDayOfWeek($MONDAY) ? CarbonImmutable::today() : CarbonImmutable::parse('Last Monday');
        $numberOfFakeMobs = 5;
        for ($i = 0; $i < $numberOfFakeMobs; $i++) {
            SocialMob::factory()->create([
                'date' => $monday->addDays(random_int(0, 4)),
                'start_time' => Carbon::createFromTime(random_int(15, 16))
            ]);
        }
    }
}
