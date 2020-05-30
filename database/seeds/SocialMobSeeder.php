<?php

use App\SocialMob;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class SocialMobSeeder extends Seeder
{
    public function run()
    {
        $MONDAY = 1;
        $monday = now()->isDayOfWeek($MONDAY) ? CarbonImmutable::today() : CarbonImmutable::parse('Last Monday');
        $numberOfFakeMobs = 5;
        for ($i = 0; $i < $numberOfFakeMobs; $i++) {
            factory(SocialMob::class)->create([
                'date' => $monday->addDays(random_int(0, 4)),
                'start_time' => Carbon::createFromTime(random_int(15, 16))
            ]);
        }
    }
}
