<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\SocialMobWeek;
use App\SocialMob;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tests\TestCase;

class SocialMobWeekTest extends TestCase
{
    public function testItReturnsAnEmptyWeekStartingOnMondayIfThereAreNoSocialMobs()
    {
        Carbon::setTestNow('Last Tuesday');

        $weekResource = new SocialMobWeek(collect());

        $firstIndex = now()->startOfWeek()->toDateString();
        $week = $weekResource->toArray(Request::createFromGlobals());
        $this->assertCount(5, $week);
        $this->assertEquals($firstIndex, collect($week)->keys()->first());
        $this->assertEquals(now()->endOfWeek(Carbon::FRIDAY)->toDateString(), collect($week)->keys()->last());
    }

    public function testItReturnsFiveDaysWhenASocialMobExistsOnThatWeek()
    {
        Carbon::setTestNow('Last Tuesday');

        $weekResource = new SocialMobWeek(
            collect([SocialMob::factory()->create(['date' => now()])])
        );

        $firstIndex = now()->startOfWeek()->toDateString();
        $week = $weekResource->toArray(Request::createFromGlobals());
        $this->assertCount(5, $week);
        $this->assertEquals($firstIndex, collect($week)->keys()->first());
        $this->assertEquals(now()->endOfWeek(Carbon::FRIDAY)->toDateString(), collect($week)->keys()->last());
    }

    public function testItReturnsAnEmptyPastWeekWithNoSocialMobs()
    {
        $weekResource = new SocialMobWeek(collect());

        $twoWeeksAgo = now()->subWeeks(2);
        $expectedMonday = $twoWeeksAgo->startOfWeek()->toDateString();

        $_GET['date'] = $twoWeeksAgo->toDateString();
        $request = Request::createFromGlobals();

        $week = $weekResource->toArray($request);
        $this->assertCount(5, $week);
        $this->assertEquals($expectedMonday, collect($week)->keys()->first());
        $this->assertEquals($twoWeeksAgo->endOfWeek(Carbon::FRIDAY)->toDateString(), collect($week)->keys()->last());
    }
}
