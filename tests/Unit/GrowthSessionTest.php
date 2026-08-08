<?php

namespace Tests\Unit;

use App\Models\AnyDesk;
use App\Models\GrowthSession;
use App\Models\User;
use App\Models\UserType;
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
        $this->actingAs($host)->post(route('growth_sessions.store', $newGrowthSession))->assertSuccessful();
        $this->assertCount(1, GrowthSession::all()->first()->attendees);
    }

    public function testItCanGetAnydesk()
    {
        $growthSession = GrowthSession::factory()
            ->for(AnyDesk::factory())
            ->create();

        $this->assertInstanceOf(Anydesk::class, $growthSession->anydesk);
    }

    public function testItParsesTheTopicIntoSegments()
    {
        $growthSession = GrowthSession::factory()->create(['topic' => 'check out https://example.com']);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'check out '],
            ['type' => 'link', 'value' => 'https://example.com'],
        ], $growthSession->topic_segments);
    }

    public function testItParsesTheLocationIntoSegments()
    {
        $growthSession = GrowthSession::factory()->create(['location' => 'join at https://example.com/room']);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'join at '],
            ['type' => 'link', 'value' => 'https://example.com/room'],
        ], $growthSession->location_segments);
    }

    public function testTopicAndLocationSegmentsNeverRenderImagesEvenWithAnImageExtensionUrl()
    {
        $imageUrl = 'https://example.com/funny.gif';
        $growthSession = GrowthSession::factory()->create(['topic' => $imageUrl, 'location' => $imageUrl]);

        $this->assertEquals([['type' => 'text', 'value' => $imageUrl]], $growthSession->topic_segments);
        $this->assertEquals([['type' => 'text', 'value' => $imageUrl]], $growthSession->location_segments);
    }
}

