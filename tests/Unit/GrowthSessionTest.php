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

    public function test_it_sets_end_time_to5_pm_by_default()
    {
        $growthSession = new GrowthSession([
            'start_time' => '15:30:00',
            'date' => '2020-01-01',
            'topic' => 'does not matter',
            'location' => 'not important either',
        ]);
        $growthSession->save();

        $this->assertEquals('05:00 pm', $growthSession->fresh()->toArray()['end_time']);
    }

    public function test_it_can_have_a_custom_title()
    {
        $titleGiven = 'My title';
        $growthSession = GrowthSession::factory()->create(['title' => $titleGiven]);

        $this->assertEquals($titleGiven, $growthSession->title);
    }

    public function test_it_has_the_attendee_limit_to_max_int_by_default()
    {
        $growthSession = new GrowthSession([
            'start_time' => '15:30:00',
            'date' => '2020-01-01',
            'topic' => 'does not matter',
            'location' => 'not important either',
        ]);
        $growthSession->save();

        $this->assertEquals(GrowthSession::NO_LIMIT, $growthSession->fresh()->attendee_limit);
    }

    public function test_it_can_get_attendees()
    {
        $attendeeCount = 3;

        $growthSession = GrowthSession::factory()
            ->has(User::factory()->count($attendeeCount), 'attendees')
            ->create();

        $this->assertEquals($growthSession->attendees()->count(), $attendeeCount);
    }

    public function test_it_can_get_owner()
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create();

        $this->assertNotEmpty($growthSession->owner);
    }

    public function test_the_owner_is_included_as_an_attendee()
    {
        $newGrowthSession = GrowthSession::factory()->make()->toArray();
        $host = User::factory()->vehiklMember()->create();
        $this->actingAs($host)->post(route('growth_sessions.store', $newGrowthSession))->assertSuccessful();
        $this->assertCount(1, GrowthSession::all()->first()->attendees);
    }

    public function test_it_can_get_anydesk()
    {
        $growthSession = GrowthSession::factory()
            ->for(AnyDesk::factory())
            ->create();

        $this->assertInstanceOf(Anydesk::class, $growthSession->anydesk);
    }

    public function test_it_parses_the_topic_into_segments()
    {
        $growthSession = GrowthSession::factory()->create(['topic' => 'check out https://example.com']);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'check out '],
            ['type' => 'link', 'value' => 'https://example.com', 'opens_in_new_tab' => true],
        ], $growthSession->topic_segments);
    }

    public function test_it_parses_the_location_into_segments()
    {
        $growthSession = GrowthSession::factory()->create(['location' => 'join at https://example.com/room']);

        $this->assertEquals([
            ['type' => 'text', 'value' => 'join at '],
            ['type' => 'link', 'value' => 'https://example.com/room', 'opens_in_new_tab' => true],
        ], $growthSession->location_segments);
    }

    public function test_topic_and_location_segments_never_render_images_even_with_an_image_extension_url()
    {
        $imageUrl = 'https://example.com/funny.gif';
        $growthSession = GrowthSession::factory()->create(['topic' => $imageUrl, 'location' => $imageUrl]);

        $this->assertEquals([['type' => 'text', 'value' => $imageUrl]], $growthSession->topic_segments);
        $this->assertEquals([['type' => 'text', 'value' => $imageUrl]], $growthSession->location_segments);
    }
}
