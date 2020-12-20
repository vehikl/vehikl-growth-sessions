<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\GrowthSession as GrowthSessionResource;
use App\GrowthSession;
use Illuminate\Http\Request;
use Tests\TestCase;

class GrowthSessionTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testTheAttendeeLimitIsRemovedForNoLimitMobs()
    {
        $resource = new GrowthSessionResource(GrowthSession::factory()->make(['attendee_limit' => GrowthSession::NO_LIMIT]));

        $anyOldRequest = Request::createFromGlobals();
        $this->assertNull($resource->toArray($anyOldRequest)['attendee_limit']);
    }

    public function testTheAttendeeLimitIsNotRemovedForMobsWithLimits()
    {
        $expectedLimit = 4;
        $resource = new GrowthSessionResource(GrowthSession::factory()->make(['attendee_limit' => $expectedLimit]));

        $anyOldRequest = Request::createFromGlobals();
        $this->assertEquals($expectedLimit, $resource->toArray($anyOldRequest)['attendee_limit']);
    }
}
