<?php

use App\SocialMob;
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
                'start_time' => $monday->addDays(random_int(0, 4))->setHour(random_int(15, 16))
            ]);
        }
    }
}
