<?php

namespace Database\Seeders;

use App\GrowthSession;
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
            GrowthSession::factory()->create([
                'date' => $monday->addDays(random_int(0, 4)),
                'start_time' => Carbon::createFromTime(random_int(15, 16))
            ]);
        }
    }
}
