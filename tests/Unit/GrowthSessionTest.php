<?php

namespace Tests\Unit;

use App\SocialMob as GrowthSession;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrowthSessionTest extends TestCase
{
    use RefreshDatabase;

    public function testItSetsEndTimeTo5PMByDefault()
    {
        $growthSession = new GrowthSession([
            'owner_id' => User::factory()->create()->id,
            'start_time' => '15:30:00',
            'date' => '2020-01-01',
            'topic' => 'does not matter',
            'location' => 'not important either'
        ]);
        $growthSession->save();

        $this->assertEquals('05:00 pm', $growthSession->fresh()->toArray()['end_time']);
    }

    public function testItCanHaveACustomTitle()
    {
        $titleGiven = 'My title';
        $mob = GrowthSession::factory()->create(['title' => $titleGiven]);

        $this->assertEquals($titleGiven, $mob->title);
    }

    public function testItHasTheAttendeeLimitToMaxIntByDefault()
    {
        $mob = new GrowthSession([
            'owner_id' => User::factory()->create()->id,
            'start_time' => '15:30:00',
            'date' => '2020-01-01',
            'topic' => 'does not matter',
            'location' => 'not important either'
        ]);
        $mob->save();

        $this->assertEquals(GrowthSession::NO_LIMIT, $mob->fresh()->attendee_limit);
    }
}
