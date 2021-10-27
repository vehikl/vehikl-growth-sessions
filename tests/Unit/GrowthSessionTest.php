<?php

namespace Tests\Unit;

use App\GrowthSession;
use App\User;
use App\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrowthSessionTest extends TestCase
{
    use RefreshDatabase;

    public function testItSetsEndTimeTo5PMByDefault()
    {
        $growthSession = new GrowthSession([
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
        $growthSession = GrowthSession::factory()->create(['title' => $titleGiven]);

        $this->assertEquals($titleGiven, $growthSession->title);
    }

    public function testItHasTheAttendeeLimitToMaxIntByDefault()
    {
        $growthSession = new GrowthSession([
            'start_time' => '15:30:00',
            'date' => '2020-01-01',
            'topic' => 'does not matter',
            'location' => 'not important either'
        ]);
        $growthSession->save();

        $this->assertEquals(GrowthSession::NO_LIMIT, $growthSession->fresh()->attendee_limit);
    }

    public function testItCanGetAttendees()
    {
        $attendeeCount = 3;

        $growthSession = GrowthSession::factory()
            ->has(User::factory()->count($attendeeCount), 'attendees')
            ->create();

        $this->assertEquals($growthSession->attendees()->count(), $attendeeCount);
    }

    public function testItCanGetOwner()
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create();

        $this->assertNotEmpty($growthSession->owner);
    }

    public function testTheOwnerIsIncludedAsAnAttendee()
    {
        $newGrowthSession = GrowthSession::factory()->make()->toArray();
        $host = User::factory()->vehiklMember()->create();
        $response = $this->actingAs($host)->post(route('growth_sessions.store', $newGrowthSession))->assertSuccessful();
        $this->assertCount(1, GrowthSession::all()->first()->attendees);
    }
}

