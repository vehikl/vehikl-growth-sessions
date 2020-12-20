<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\GrowthSessionWeek;
use App\GrowthSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tests\TestCase;

class GrowthSessionWeekTest extends TestCase
{
    public function testItReturnsAnEmptyWeekStartingOnMondayIfThereAreNoGrowthSessions()
    {
        Carbon::setTestNow('Last Tuesday');

        $weekResource = new GrowthSessionWeek(collect());

        $firstIndex = now()->startOfWeek()->toDateString();
        $week = $weekResource->toArray(Request::createFromGlobals());
        $this->assertCount(5, $week);
        $this->assertEquals($firstIndex, collect($week)->keys()->first());
        $this->assertEquals(now()->endOfWeek(Carbon::FRIDAY)->toDateString(), collect($week)->keys()->last());
    }

    public function testItReturnsFiveDaysWhenAGrowthSessionExistsOnThatWeek()
    {
        Carbon::setTestNow('Last Tuesday');

        $weekResource = new GrowthSessionWeek(
            collect([GrowthSession::factory()->create(['date' => now()])])
        );

        $firstIndex = now()->startOfWeek()->toDateString();
        $week = $weekResource->toArray(Request::createFromGlobals());
        $this->assertCount(5, $week);
        $this->assertEquals($firstIndex, collect($week)->keys()->first());
        $this->assertEquals(now()->endOfWeek(Carbon::FRIDAY)->toDateString(), collect($week)->keys()->last());
    }

    public function testItReturnsAnEmptyPastWeekWithNoGrowthSessions()
    {
        $weekResource = new GrowthSessionWeek(collect());

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
