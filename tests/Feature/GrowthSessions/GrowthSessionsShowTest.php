<?php

namespace Tests\Feature\GrowthSessions;

use App\Models\GrowthSession;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserType;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GrowthSessionsShowTest extends TestCase
{
    public function testTheGetGrowthSessionEndpointReturnsAViewIfTheRequestIsNotExpectingJson()
    {
        $growthSession = GrowthSession::factory()->create();
        $this->get(route('growth_sessions.show', $growthSession))
            ->assertInertia(function (AssertableInertia $page) use ($growthSession) {
                return $page
                    ->where('growthSessionJson.id', $growthSession->id)
                    ->etc();
            });
    }

    public function testTheGetGrowthSessionEndpointReturnsAJsonPayloadIfTheRequestIsExpectingJson()
    {
        $growthSession = GrowthSession::factory()->create();
        $this->getJson(route('growth_sessions.show', $growthSession))
            ->assertJsonFragment(['id' => $growthSession->id]);
    }

    public function testItDoesNotProvideLocationOfAGrowthSessionForAnonymousUser()
    {
        $this->setTestNow('2020-01-15');
        $monday = CarbonImmutable::parse('Last Monday');
        $growthSession = GrowthSession::factory()->create(['date' => $monday, 'start_time' => '03:30 pm']);

        $response = $this->get(route('growth_sessions.show', ['growth_session' => $growthSession]));

        $response->assertSuccessful();
        $response->assertDontSee('At AnyDesk XYZ - abcdefg');
    }

    public function testItDoesNotProvideLocationOfAGrowthSessionToNonVehikaliensBeforeTheyJoinTheGrowthSession()
    {
        $this->setTestNow('2020-01-15');
        $growthSession = GrowthSession::factory()->create(['is_public' => true]);

        /** @var User $user */
        $user = User::factory()->create(['is_vehikl_member' => false]);

        $response = $this->actingAs($user)->get(route('growth_sessions.show', ['growth_session' => $growthSession]));

        $response->assertSuccessful();
        $response->assertDontSee('At AnyDesk XYZ - abcdefg');
    }

    public function testItProvidesLocationOfAGrowthSessionToNonVehikaliensAfterTheyJoinTheGrowthSession()
    {
        $this->setTestNow('2020-01-15');
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(false), [], 'attendees')
            ->create(['is_public' => true]);

        /** @var User $user */
        $user = $growthSession->attendees()->first();

        $this->actingAs($user)
            ->get(route('growth_sessions.show', ['growth_session' => $growthSession]))
            ->assertSuccessful()
            ->assertSee('At AnyDesk XYZ - abcdefg');
    }

    public function testItProvidesLocationOfAGrowthSessionToVehikaliensAfterTheyJoinTheGrowthSession()
    {
        $this->setTestNow('2020-01-15');
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(true), [], 'attendees')
            ->create(['is_public' => true]);

        /** @var User $user */
        $user = $growthSession->attendees()->first();

        $this->actingAs($user)
            ->get(route('growth_sessions.show', ['growth_session' => $growthSession]))
            ->assertSuccessful()
            ->assertSee('At AnyDesk XYZ - abcdefg');
    }

    public function testTheSlackBotCanSeeTheGrowthSessionLocation()
    {
        $this->seed();
        $growthSession = GrowthSession::factory()->create(['date' => today()]);
        $this->getJson(route('growth_sessions.day'), ['Authorization' => 'Bearer ' . config('auth.slack_token')])
            ->assertJsonFragment(['location' => $growthSession->location]);
    }

    public function testANonMemberUserCannotAccessAPrivateGrowthSession()
    {
        $growthSession = GrowthSession::factory()->create(['is_public' => false]);

        /** @var User $user */
        $user = User::factory()->create(['is_vehikl_member' => false]);

        $this->actingAs($user)
            ->get(route('growth_sessions.show', $growthSession))
            ->assertNotFound();
    }

    public function testAGuestCannotAccessAPrivateGrowthSession()
    {
        $growthSession = GrowthSession::factory()->create(['is_public' => false]);

        $this->get(route('growth_sessions.show', $growthSession))
            ->assertNotFound();
    }

    public function testAMemberCanAccessAPrivateGrowthSession()
    {
        $growthSession = GrowthSession::factory()->create(['is_public' => false]);
        /** @var User $user */
        $user = User::factory()->create(['is_vehikl_member' => true]);

        $this->actingAs($user)
            ->get(route('growth_sessions.show', $growthSession))
            ->assertSuccessful()
            ->assertSee($growthSession->title);
    }

    public function testItProvidesTheGrowthSessionVisibilityInThePayload()
    {
        $this->setTestNow('2020-01-15');
        $growthSession = GrowthSession::factory()->create(['is_public' => FALSE]);
        /** @var User $user */
        $user = User::factory()->create(['is_vehikl_member' => true]);

        $response = $this->actingAs($user)
            ->get(route('growth_sessions.week', $growthSession));

        $this->assertArrayHasKey('is_public', $response->json(today()->format("Y-m-d"))[0]);
    }

    #[DataProvider('providesGrowthSessionGuests')]
    public function testItDoesNotShowGuestDetailsToNonVehiklUsers($guestUserType, $guestRelationship)
    {
        /** @var User $nonVehiklMember */
        $nonVehiklMember = User::factory()->create();

        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(false), $guestUserType, $guestRelationship)
            ->create();

        $guestMember = $growthSession->$guestRelationship()->first();

        $this->actingAs($nonVehiklMember)->get(route('growth_sessions.show', $growthSession))
            ->assertSee('Guest')
            ->assertSee('images\\/guest-avatar.webp')
            ->assertDontSee($guestMember->name)
            ->assertDontSee($growthSession->avatar)
            ->assertDontSee($guestMember->github_nickname);
    }

    #[DataProvider('providesGrowthSessionGuests')]
    public function testItCanShowGuestDetailsForVehiklUsers($guestUserType, $guestRelationship)
    {
        $vehiklMember = User::factory()->vehiklMember()->create();

        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(false), $guestUserType, $guestRelationship)
            ->create();

        $guestMember = $growthSession->$guestRelationship()->first();

        $this->actingAs($vehiklMember)->get(route('growth_sessions.show', $growthSession))
            ->assertSee($guestMember->name)
            ->assertSee($growthSession->avatar)
            ->assertSee($guestMember->github_nickname)
            ->assertDontSee('images\\/guest-avatar.webp')
            ->assertDontSee('Guest');
    }

    #[DataProvider('providesGrowthSessionGuests')]
    public function testItShowsAGuestTheirOwnDetails($guestUserType, $guestRelationship)
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(FALSE), $guestUserType, $guestRelationship)
            ->create();

        $guestMember = $growthSession->$guestRelationship()->first();

        $this->actingAs($guestMember)->get(route('growth_sessions.show', $growthSession))
            ->assertSee($guestMember->name)
            ->assertSee($growthSession->avatar)
            ->assertSee($guestMember->github_nickname)
            ->assertDontSee('images\\/guest-avatar.webp')
            ->assertDontSee('Guest');
    }

    #[DataProvider('providesGrowthSessionGuests')]
    public function testItCannotShowGuestDetailsForUnauthenicatedUsers($guestUserType, $guestRelationship)
    {
        $this->markTestSkipped('Update this to work with Inertia. There are more tests like this one.');
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(FALSE), $guestUserType, $guestRelationship)
            ->hasAttached(User::factory()->vehiklMember(FALSE), $guestUserType, $guestRelationship)
            ->create();

        $guest = $growthSession->fresh()->$guestRelationship->first();
        $anotherGuest = $growthSession->fresh()->$guestRelationship->last();

        $this->actingAs($guest)->get(route('growth_sessions.show', $growthSession))
            ->assertSee('Guest')
            ->assertSee('images\\/guest-avatar.webp')
            ->assertDontSee($anotherGuest->name)
            ->assertDontSee($anotherGuest->avatar)
            ->assertDontSee($anotherGuest->github_nickname)
            ->assertSee($guest->name)
            ->assertSee($guest->avatar)
            ->assertSee($guest->github_nickname);
    }

    public function testItProvidesTheListOfAttendeesAndWatchers()
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

    public function testItProvidesAListOfTags()
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

    public static function providesGrowthSessionGuests(): array
    {
        return [
            'The guest is an attendee' => [['user_type_id' => UserType::ATTENDEE_ID], 'attendees'],
            'The guest is a watcher' => [['user_type_id' => UserType::WATCHER_ID], 'watchers'],
        ];
    }
}
