<?php

namespace Tests\Feature\GrowthSessions;

use App\Models\AnyDesk;
use App\Models\GrowthSession;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class GrowthSessionInvitationTest extends TestCase
{
    private GrowthSession $growthSession;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setTestNowToASafeWednesday();

        $this->growthSession = GrowthSession::factory()->unlisted()->create();
    }

    public function testAValidInviteLinkRedirectsToTheBoardDeepLinkedToTheGrowthSession()
    {
        $this->openInviteLink($this->growthSession)
            ->assertRedirect(route('home', [
                'date' => $this->growthSession->date->toDateString(),
                'session' => $this->growthSession->id,
            ]));
    }

    public function testTheInviteLinkIsAsShortAsItCanBeSoItPastesWellIntoAChat()
    {
        $this->assertSame(
            url("/invitations/{$this->growthSession->share_token}"),
            route('growth_sessions.invitation', ['token' => $this->growthSession->share_token]),
        );
    }

    public function testAnUnlistedGrowthSessionIsMarkedAsSuchSoTheBoardKeepsShowingItToInvitedGuests()
    {
        $this->openInviteLink($this->growthSession);

        $this->showGrowthSession($this->growthSession)
            ->assertSuccessful()
            ->assertJsonPath('is_unlisted', true)
            ->assertJsonPath('is_public', false);
    }

    public function testAPublicGrowthSessionIsNotMarkedAsUnlisted()
    {
        $publicGrowthSession = GrowthSession::factory()->create(['is_public' => true]);

        $this->showGrowthSession($publicGrowthSession)
            ->assertSuccessful()
            ->assertJsonPath('is_unlisted', false);
    }

    public function testAnUnknownInviteTokenIsNotFound()
    {
        $this->get(route('growth_sessions.invitation', ['token' => 'a-token-that-belongs-to-nobody']))
            ->assertNotFound();
    }

    public function testAnAnonymousVisitorWhoUnlockedTheGrowthSessionSeesItsFullNonSensitiveDetail()
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory()->times(2), [], 'attendees')
            ->hasAttached(Tag::factory()->times(3), [], 'tags')
            ->unlisted()
            ->create([
                'title' => 'A Session With A Client',
                'topic' => 'Pairing on their project',
                'date' => '2020-01-15',
                'start_time' => '03:30 pm',
                'end_time' => '05:00 pm',
            ]);

        $this->openInviteLink($growthSession);

        $this->showGrowthSession($growthSession)
            ->assertSuccessful()
            ->assertJsonPath('title', 'A Session With A Client')
            ->assertJsonPath('topic', 'Pairing on their project')
            ->assertJsonPath('date', '2020-01-15')
            ->assertJsonPath('start_time', '03:30 pm')
            ->assertJsonPath('end_time', '05:00 pm')
            ->assertJsonCount(3, 'tags')
            ->assertJsonCount(2, 'attendees');
    }

    public function testAnUnlockedVisitorFollowingTheGrowthSessionsOwnUrlReachesTheBoard()
    {
        $this->openInviteLink($this->growthSession);

        $this->get(route('growth_sessions.show', $this->growthSession))
            ->assertRedirect(route('home', [
                'date' => $this->growthSession->date->toDateString(),
                'session' => $this->growthSession->id,
            ]));
    }

    public function testTheLocationRemainsMaskedForAnUnlockedVisitor()
    {
        $this->openInviteLink($this->growthSession);

        $this->getJson(route('growth_sessions.show', $this->growthSession))
            ->assertSuccessful()
            ->assertDontSee('At AnyDesk XYZ - abcdefg');
    }

    public function testAnyDeskDetailsAndGuestIdentitiesRemainMaskedForAnUnlockedVisitor()
    {
        $guest = User::factory()->vehiklMember(false)->create(['name' => 'Some Other Guest']);
        $anyDesk = AnyDesk::factory()->create();
        $growthSession = GrowthSession::factory()
            ->unlisted()
            ->hasAttached($guest, [], 'attendees')
            ->create(['anydesk_id' => $anyDesk->id]);

        $this->openInviteLink($growthSession);

        $this->showGrowthSession($growthSession)
            ->assertSuccessful()
            ->assertJsonPath('anydesk', null)
            ->assertJsonFragment(['name' => 'Guest'])
            ->assertJsonMissing(['name' => 'Some Other Guest']);
    }

    public function testAVisitorWithoutTheInviteLinkCannotSeeAnUnlistedGrowthSession()
    {
        $this->showGrowthSession($this->growthSession)->assertNotFound();
    }

    public function testALoggedInNonVehiklUserWithoutTheInviteLinkCannotSeeAnUnlistedGrowthSession()
    {
        $nonVehiklUser = User::factory()->vehiklMember(false)->create();

        $this->actingAs($nonVehiklUser);

        $this->showGrowthSession($this->growthSession)->assertNotFound();
    }

    public function testAVisitorWhoUnlockedADifferentGrowthSessionCannotSeeThisOne()
    {
        $anotherGrowthSession = GrowthSession::factory()->unlisted()->create();

        $this->openInviteLink($this->growthSession);

        $this->showGrowthSession($anotherGrowthSession)->assertNotFound();
    }

    public function testRevokingTheTokenTakesEffectImmediatelyForAnAlreadyUnlockedVisitor()
    {
        $this->openInviteLink($this->growthSession);
        $this->showGrowthSession($this->growthSession)->assertSuccessful();

        $this->growthSession->share_token = null;
        $this->growthSession->save();

        $this->showGrowthSession($this->growthSession)->assertNotFound();
    }

    public function testVehiklMembersAndTheOwnerSeeAnUnlistedGrowthSessionWithoutTheLink()
    {
        $owner = User::factory()->vehiklMember(false)->create();
        $growthSession = GrowthSession::factory()
            ->unlisted()
            ->hasAttached($owner, ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create();
        $vehiklMember = User::factory()->vehiklMember()->create();

        $this->actingAs($vehiklMember);
        $this->showGrowthSession($growthSession)->assertSuccessful();

        $this->actingAs($owner);
        $this->showGrowthSession($growthSession)->assertSuccessful();
    }

    public function testTheShareTokenIsAbsentFromTheShowPayload()
    {
        $this->actingAs(User::factory()->vehiklMember()->create());

        $this->showGrowthSession($this->growthSession)
            ->assertSuccessful()
            ->assertJsonMissing(['share_token' => $this->growthSession->share_token]);
    }

    public function testTheShareTokenIsAbsentFromTheWeekListing()
    {
        $this->actingAs(User::factory()->vehiklMember()->create());

        $this->getJson(route('growth_sessions.week'))
            ->assertSuccessful()
            ->assertJsonMissing(['share_token' => $this->growthSession->share_token]);
    }

    public function testTheShareTokenIsAbsentFromTheDayListing()
    {
        $this->actingAs(User::factory()->vehiklMember()->create());

        $this->getJson(route('growth_sessions.day'))
            ->assertSuccessful()
            ->assertJsonMissing(['share_token' => $this->growthSession->share_token]);
    }

    public function testAnUnlistedGrowthSessionDoesNotAppearInTheWeekListingForAnonymousVisitors()
    {
        $this->getJson(route('growth_sessions.week'))
            ->assertSuccessful()
            ->assertJsonMissing(['id' => $this->growthSession->id]);
    }

    public function testAnUnlockedVisitorSeesTheGrowthSessionInTheirWeekListing()
    {
        $this->openInviteLink($this->growthSession);

        $this->getJson(route('growth_sessions.week'))
            ->assertSuccessful()
            ->assertJsonFragment(['id' => $this->growthSession->id]);
    }

    public function testAnAttendeeSeesTheGrowthSessionWithoutEverHoldingTheToken()
    {
        $attendee = User::factory()->vehiklMember(false)->create();
        $this->growthSession->attendees()->attach($attendee, ['user_type_id' => UserType::ATTENDEE_ID]);

        $this->actingAs($attendee);

        $this->showGrowthSession($this->growthSession)->assertSuccessful();
    }

    public function testAWatcherSeesTheGrowthSessionWithoutEverHoldingTheToken()
    {
        $watcher = User::factory()->vehiklMember(false)->create();
        $this->growthSession->watchers()->attach($watcher, ['user_type_id' => UserType::WATCHER_ID]);

        $this->actingAs($watcher);

        $this->showGrowthSession($this->growthSession)->assertSuccessful();
    }

    public function testAClientWhoJoinedViaAnInviteLinkStillSeesItAfterTheirBrowserSessionExpires()
    {
        $client = User::factory()->vehiklMember(false)->create();
        $this->actingAs($client);

        $this->openInviteLink($this->growthSession);
        $this->postJson(route('growth_sessions.join', $this->growthSession))->assertSuccessful();

        $this->flushSession();

        $this->showGrowthSession($this->growthSession)->assertSuccessful();
    }

    private function openInviteLink(GrowthSession $growthSession): TestResponse
    {
        return $this->get(route('growth_sessions.invitation', ['token' => $growthSession->share_token]));
    }

    private function showGrowthSession(GrowthSession $growthSession): TestResponse
    {
        return $this->getJson(route('growth_sessions.show', $growthSession));
    }
}
