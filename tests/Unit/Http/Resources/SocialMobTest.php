<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\GrowthSession as SocialMobResource;
use App\GrowthSession as SocialMobModel;
use Illuminate\Http\Request;
use Tests\TestCase;

class SocialMobTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testTheAttendeeLimitIsRemovedForNoLimitMobs()
    {
        $resource = new SocialMobResource(SocialMobModel::factory()->make(['attendee_limit' => SocialMobModel::NO_LIMIT]));

        $anyOldRequest = Request::createFromGlobals();
        $this->assertNull($resource->toArray($anyOldRequest)['attendee_limit']);
    }

    public function testTheAttendeeLimitIsNotRemovedForMobsWithLimits()
    {
        $expectedLimit = 4;
        $resource = new SocialMobResource(SocialMobModel::factory()->make(['attendee_limit' => $expectedLimit]));

        $anyOldRequest = Request::createFromGlobals();
        $this->assertEquals($expectedLimit, $resource->toArray($anyOldRequest)['attendee_limit']);
    }
}
