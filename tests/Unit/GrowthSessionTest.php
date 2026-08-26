<?php

namespace Tests\Unit;

use App\Enums\Role;
use App\Models\AnyDesk;
use App\Models\GrowthSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
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

    /**
     * roleOf() has to rule out every role before it can answer null, so it reads all four of
     * VISIBILITY_RELATIONS. Eager-loading those four is the whole contract: the second growth
     * session is here to arm the lazy-loading guard (Eloquent only arms it on hydration of more
     * than one row), so a fifth relation creeping into the answer fails here rather than in a
     * browser.
     */
    public function test_it_names_the_role_a_member_holds_from_the_visibility_relations()
    {
        $this->assertTrue(Model::preventsLazyLoading());
        [$owner, $attendee, $watcher, $queued, $stranger] = User::factory()->count(5)->create()->all();
        $growthSession = GrowthSession::factory()->create();
        $growthSession->owners()->attach($owner, ['user_type_id' => Role::Owner->value]);
        $growthSession->attendees()->attach($attendee, ['user_type_id' => Role::Attendee->value]);
        $growthSession->watchers()->attach($watcher, ['user_type_id' => Role::Watcher->value]);
        $growthSession->waitlist()->attach($queued, ['user_type_id' => Role::Waitlisted->value]);
        GrowthSession::factory()->create();

        $loaded = GrowthSession::query()
            ->with(GrowthSession::VISIBILITY_RELATIONS)
            ->get()
            ->firstWhere('id', $growthSession->id);

        $this->assertSame(Role::Owner, $loaded->roleOf($owner));
        $this->assertSame(Role::Attendee, $loaded->roleOf($attendee));
        $this->assertSame(Role::Watcher, $loaded->roleOf($watcher));
        $this->assertSame(Role::Waitlisted, $loaded->roleOf($queued));
        $this->assertNull($loaded->roleOf($stranger));
        $this->assertNull($loaded->roleOf(null));
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
            ->hasAttached(User::factory(), ['user_type_id' => Role::Owner->value], 'owners')
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
