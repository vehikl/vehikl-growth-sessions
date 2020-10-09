<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\SocialMob;
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
        $resource = new SocialMob(factory(\App\SocialMob::class)->make(['attendee_limit' => \App\SocialMob::NO_LIMIT]));

        $anyOldRequest = Request::createFromGlobals();
        $this->assertNull($resource->toArray($anyOldRequest)['attendee_limit']);
    }

    public function testTheAttendeeLimitIsNotRemovedForMobsWithLimits()
    {
        $expectedLimit = 4;
        $resource = new SocialMob(factory(\App\SocialMob::class)->make(['attendee_limit' => $expectedLimit]));

        $anyOldRequest = Request::createFromGlobals();
        $this->assertEquals($expectedLimit, $resource->toArray($anyOldRequest)['attendee_limit']);
    }
}
