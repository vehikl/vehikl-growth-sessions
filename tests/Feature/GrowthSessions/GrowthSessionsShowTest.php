<?php

namespace Tests\Feature\GrowthSessions;

use App\Models\GrowthSession;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserType;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GrowthSessionsShowTest extends TestCase
{
    public function test_a_browser_visit_is_sent_to_the_board_with_that_growth_sessions_drawer_open()
    {
        $this->setTestNow('2020-01-15');
        $growthSession = GrowthSession::factory()->create();

        $this->get(route('growth_sessions.show', $growthSession))
            ->assertRedirect(route('home', [
                'date' => $growthSession->date->toDateString(),
                'session' => $growthSession->id,
            ]));
    }

    public function test_the_redirect_carries_the_week_the_growth_session_is_in()
    {
        $this->setTestNow('2020-01-15');
        $growthSession = GrowthSession::factory()->create(['date' => '2019-11-06']);

        $this->get(route('growth_sessions.show', $growthSession))
            ->assertRedirect(route('home', ['date' => '2019-11-06', 'session' => $growthSession->id]));
    }

    public function test_the_get_growth_session_endpoint_returns_a_json_payload_if_the_request_is_expecting_json()
    {
        $growthSession = GrowthSession::factory()->create();
        $this->getJson(route('growth_sessions.show', $growthSession))
            ->assertJsonFragment(['id' => $growthSession->id]);
    }

    public function test_it_does_not_provide_location_of_a_growth_session_for_anonymous_user()
    {
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');
        $growthSession = GrowthSession::factory()->create(['date' => $monday, 'start_time' => '03:30 pm']);

        $response = $this->getJson(route('growth_sessions.show', ['growth_session' => $growthSession]));

        $response->assertSuccessful();
        $response->assertDontSee('At AnyDesk XYZ - abcdefg');
    }

    public function test_it_does_not_provide_location_of_a_growth_session_to_non_vehikaliens_before_they_join_the_growth_session()
    {
        $this->setTestNow('2020-01-15');
        $growthSession = GrowthSession::factory()->create(['is_public' => true]);

        /** @var User $user */
        $user = User::factory()->create(['is_vehikl_member' => false]);

        $response = $this->actingAs($user)->getJson(route('growth_sessions.show', ['growth_session' => $growthSession]));

        $response->assertSuccessful();
        $response->assertDontSee('At AnyDesk XYZ - abcdefg');
    }

    public function test_it_provides_location_of_a_growth_session_to_non_vehikaliens_after_they_join_the_growth_session()
    {
        $this->setTestNow('2020-01-15');
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(false), [], 'attendees')
            ->create(['is_public' => true]);

        /** @var User $user */
        $user = $growthSession->attendees()->first();

        $this->actingAs($user)
            ->getJson(route('growth_sessions.show', ['growth_session' => $growthSession]))
            ->assertSuccessful()
            ->assertSee('At AnyDesk XYZ - abcdefg');
    }

    public function test_it_provides_location_of_a_growth_session_to_vehikaliens_after_they_join_the_growth_session()
    {
        $this->setTestNow('2020-01-15');
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(true), [], 'attendees')
            ->create(['is_public' => true]);

        /** @var User $user */
        $user = $growthSession->attendees()->first();

        $this->actingAs($user)
            ->getJson(route('growth_sessions.show', ['growth_session' => $growthSession]))
            ->assertSuccessful()
            ->assertSee('At AnyDesk XYZ - abcdefg');
    }

    public function test_a_non_member_user_cannot_access_a_private_growth_session()
    {
        $growthSession = GrowthSession::factory()->create(['is_public' => false]);

        /** @var User $user */
        $user = User::factory()->create(['is_vehikl_member' => false]);

        $this->actingAs($user)
            ->get(route('growth_sessions.show', $growthSession))
            ->assertNotFound();
    }

    public function test_a_guest_cannot_access_a_private_growth_session()
    {
        $growthSession = GrowthSession::factory()->create(['is_public' => false]);

        $this->get(route('growth_sessions.show', $growthSession))
            ->assertNotFound();
    }

    public function test_a_member_can_access_a_private_growth_session()
    {
        $growthSession = GrowthSession::factory()->create(['is_public' => false]);
        /** @var User $user */
        $user = User::factory()->create(['is_vehikl_member' => true]);

        $this->actingAs($user)
            ->getJson(route('growth_sessions.show', $growthSession))
            ->assertSuccessful()
            ->assertJsonPath('title', $growthSession->title);
    }

    public function test_a_member_following_a_link_to_a_private_growth_session_is_sent_to_the_board()
    {
        $growthSession = GrowthSession::factory()->create(['is_public' => false]);
        /** @var User $user */
        $user = User::factory()->create(['is_vehikl_member' => true]);

        $this->actingAs($user)
            ->get(route('growth_sessions.show', $growthSession))
            ->assertRedirect(route('home', [
                'date' => $growthSession->date->toDateString(),
                'session' => $growthSession->id,
            ]));
    }

    public function test_it_provides_the_growth_session_visibility_in_the_payload()
    {
        $this->setTestNow('2020-01-15');
        $growthSession = GrowthSession::factory()->create(['is_public' => false]);
        /** @var User $user */
        $user = User::factory()->create(['is_vehikl_member' => true]);

        $response = $this->actingAs($user)
            ->get(route('growth_sessions.week', $growthSession));

        $this->assertArrayHasKey('is_public', $response->json(today()->format('Y-m-d'))[0]);
    }

    #[DataProvider('providesGrowthSessionGuests')]
    public function test_it_does_not_show_guest_details_to_non_vehikl_users($guestUserType, $guestRelationship)
    {
        /** @var User $nonVehiklMember */
        $nonVehiklMember = User::factory()->create();

        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(false), $guestUserType, $guestRelationship)
            ->create();

        $guestMember = $growthSession->$guestRelationship()->first();

        $this->actingAs($nonVehiklMember)->getJson(route('growth_sessions.show', $growthSession))
            ->assertJsonPath("$guestRelationship.0.name", 'Guest')
            ->assertJsonPath("$guestRelationship.0.avatar", asset('images/guest-avatar.webp'))
            ->assertJsonPath("$guestRelationship.0.github_nickname", '');
    }

    #[DataProvider('providesGrowthSessionGuests')]
    public function test_it_can_show_guest_details_for_vehikl_users($guestUserType, $guestRelationship)
    {
        $vehiklMember = User::factory()->vehiklMember()->create();

        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(false), $guestUserType, $guestRelationship)
            ->create();

        $guestMember = $growthSession->$guestRelationship()->first();

        $this->actingAs($vehiklMember)->getJson(route('growth_sessions.show', $growthSession))
            ->assertJsonPath("$guestRelationship.0.name", $guestMember->name)
            ->assertJsonPath("$guestRelationship.0.avatar", $guestMember->avatar)
            ->assertJsonPath("$guestRelationship.0.github_nickname", $guestMember->github_nickname);
    }

    #[DataProvider('providesGrowthSessionGuests')]
    public function test_it_shows_a_guest_their_own_details($guestUserType, $guestRelationship)
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(false), $guestUserType, $guestRelationship)
            ->create();

        $guestMember = $growthSession->$guestRelationship()->first();

        $this->actingAs($guestMember)->getJson(route('growth_sessions.show', $growthSession))
            ->assertJsonPath("$guestRelationship.0.name", $guestMember->name)
            ->assertJsonPath("$guestRelationship.0.avatar", $guestMember->avatar)
            ->assertJsonPath("$guestRelationship.0.github_nickname", $guestMember->github_nickname);
    }

    #[DataProvider('providesGrowthSessionGuests')]
    public function test_it_cannot_show_guest_details_for_unauthenicated_users($guestUserType, $guestRelationship)
    {
        $this->markTestSkipped('Update this to work with Inertia. There are more tests like this one.');
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(false), $guestUserType, $guestRelationship)
            ->hasAttached(User::factory()->vehiklMember(false), $guestUserType, $guestRelationship)
            ->create();

        $guest = $growthSession->fresh()->$guestRelationship->first();
        $anotherGuest = $growthSession->fresh()->$guestRelationship->last();

        $this->actingAs($guest)->getJson(route('growth_sessions.show', $growthSession))
            ->assertJsonFragment([
                'name' => 'Guest',
                'avatar' => asset('images/guest-avatar.webp'),
                'github_nickname' => '',
            ])
            ->assertJsonMissing(['name' => $anotherGuest->name])
            ->assertJsonMissing(['avatar' => $anotherGuest->avatar])
            ->assertJsonMissing(['github_nickname' => $anotherGuest->github_nickname])
            ->assertJsonFragment([
                'name' => $guest->name,
                'avatar' => $guest->avatar,
                'github_nickname' => $guest->github_nickname,
            ]);
    }

    public function test_it_provides_the_list_of_attendees_and_watchers()
    {
        $numberOfAttendees = 2;
        $numberOfWatchers = 5;
        $growthSession = GrowthSession::factory()
            ->hasAttached(
                User::factory()->vehiklMember(true)->times($numberOfAttendees),
                ['user_type_id' => UserType::ATTENDEE_ID],
                'attendees'
            )
            ->hasAttached(User::factory()->vehiklMember(true)->times($numberOfWatchers),
                ['user_type_id' => UserType::WATCHER_ID],
                'watchers'
            )
            ->create();

        $this->getJson(route('growth_sessions.show', $growthSession))
            ->assertJsonCount($numberOfAttendees, 'attendees')
            ->assertJsonCount($numberOfWatchers, 'watchers');
    }

    public function test_it_provides_a_list_of_tags()
    {
        $numberOfTags = 3;

        $growthSession = GrowthSession::factory()
            ->hasAttached(
                Tag::factory()->times($numberOfTags),
                [],
                'tags'
            )
            ->create();

        $this->getJson(route('growth_sessions.show', $growthSession))
            ->assertJsonCount($numberOfTags, 'tags');
    }

    public function test_it_provides_topic_and_location_segments_in_the_payload()
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(true), [], 'attendees')
            ->create([
                'topic' => 'check out https://example.com',
                'location' => 'join at https://example.com/room',
            ]);

        /** @var User $user */
        $user = $growthSession->attendees()->first();

        $this->actingAs($user)
            ->getJson(route('growth_sessions.show', $growthSession))
            ->assertJsonPath('topic_segments', [
                ['type' => 'text', 'value' => 'check out '],
                ['type' => 'link', 'value' => 'https://example.com', 'opens_in_new_tab' => true],
            ])
            ->assertJsonPath('location_segments', [
                ['type' => 'text', 'value' => 'join at '],
                ['type' => 'link', 'value' => 'https://example.com/room', 'opens_in_new_tab' => true],
            ]);
    }

    public function test_location_segments_are_redacted_for_non_participants_instead_of_leaking_the_real_location()
    {
        $growthSession = GrowthSession::factory()->create([
            'is_public' => true,
            'location' => 'join at https://example.com/secret-room',
        ]);

        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('growth_sessions.show', $growthSession))
            ->assertJsonPath('location_segments', [
                ['type' => 'text', 'value' => '< Join to see location >'],
            ])
            ->assertDontSee('secret-room');
    }

    /** Pins the exact top-level field set of the growth session API contract. */
    public function test_the_growth_session_payload_contains_exactly_the_expected_top_level_fields()
    {
        $this->setTestNow('2020-01-15');

        /** @var User $owner */
        $owner = User::factory()->vehiklMember()->create();
        $growthSession = GrowthSession::factory()->create([
            'date' => '2020-01-20',
            'start_time' => '03:30 pm',
            'end_time' => '05:00 pm',
            'attendee_limit' => 4,
        ]);
        $growthSession->owners()->attach($owner, ['user_type_id' => UserType::OWNER_ID]);

        $response = $this->actingAs($owner)
            ->getJson(route('growth_sessions.show', $growthSession))
            ->assertSuccessful();

        $this->assertEqualsCanonicalizing([
            'id', 'title', 'topic', 'topic_segments', 'location', 'location_segments', 'date',
            'start_time', 'end_time', 'is_public', 'allow_watchers', 'attendee_limit',
            'discord_channel_id', 'slack_thread_ts', 'owner', 'attendees', 'watchers', 'comments',
            'anydesk', 'tags', 'is_unlisted',
        ], array_keys($response->json()));

        $response->assertJsonPath('date', '2020-01-20');
        $response->assertJsonPath('start_time', '03:30 pm');
        $response->assertJsonPath('end_time', '05:00 pm');

        $this->assertEqualsCanonicalizing([
            'id', 'github_nickname', 'name', 'avatar', 'is_vehikl_member',
        ], array_keys($response->json('owner')));
    }

    public static function providesGrowthSessionGuests(): array
    {
        return [
            'The guest is an attendee' => [['user_type_id' => UserType::ATTENDEE_ID], 'attendees'],
            'The guest is a watcher' => [['user_type_id' => UserType::WATCHER_ID], 'watchers'],
        ];
    }
}
