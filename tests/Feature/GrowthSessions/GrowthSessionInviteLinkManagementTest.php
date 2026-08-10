<?php

namespace Tests\Feature\GrowthSessions;

use App\Models\GrowthSession;
use App\Models\User;
use App\Models\UserType;
use App\Support\InviteLink;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class GrowthSessionInviteLinkManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setTestNowToASafeWednesday();
    }

    public function testCreatingAGrowthSessionWithTheInviteOptionEnabledGeneratesAShareToken()
    {
        $owner = User::factory()->vehiklMember()->create();

        $this->actingAs($owner)
            ->postJson(route('growth_sessions.store'), $this->creationParameters(['has_invite_link' => true]))
            ->assertSuccessful();

        $this->assertNotEmpty(GrowthSession::first()->share_token);
    }

    public function testCreatingAGrowthSessionWithoutTheInviteOptionLeavesItWithoutAShareToken()
    {
        $owner = User::factory()->vehiklMember()->create();

        $this->actingAs($owner)
            ->postJson(route('growth_sessions.store'), $this->creationParameters())
            ->assertSuccessful();

        $this->assertNull(GrowthSession::first()->share_token);
    }

    public function testEnablingTheInviteOptionOnAnExistingGrowthSessionGeneratesAShareToken()
    {
        $growthSession = $this->ownedGrowthSession();

        $this->actingAs($growthSession->owner)
            ->putJson(route('growth_sessions.update', $growthSession), ['has_invite_link' => true])
            ->assertSuccessful();

        $this->assertNotEmpty($growthSession->fresh()->share_token);
    }

    public function testEnablingTheInviteOptionAgainKeepsTheTokenAlreadyHandedOut()
    {
        $growthSession = $this->ownedGrowthSession(unlisted: true);
        $originalToken = $growthSession->share_token;

        $this->actingAs($growthSession->owner)
            ->putJson(route('growth_sessions.update', $growthSession), ['has_invite_link' => true])
            ->assertSuccessful();

        $this->assertEquals($originalToken, $growthSession->fresh()->share_token);
    }

    public function testAnUpdateThatSaysNothingAboutTheInviteOptionLeavesTheTokenAlone()
    {
        $growthSession = $this->ownedGrowthSession(unlisted: true);
        $originalToken = $growthSession->share_token;

        $this->actingAs($growthSession->owner)
            ->putJson(route('growth_sessions.update', $growthSession), ['topic' => 'A brand new topic'])
            ->assertSuccessful();

        $this->assertEquals($originalToken, $growthSession->fresh()->share_token);
    }

    public function testAPublicGrowthSessionIsCreatedWithoutAnInviteLinkEvenIfOneIsAskedFor()
    {
        $owner = User::factory()->vehiklMember()->create();

        $this->actingAs($owner)
            ->postJson(route('growth_sessions.store'), $this->creationParameters([
                'is_public' => true,
                'has_invite_link' => true,
            ]))
            ->assertSuccessful();

        $this->assertNull(GrowthSession::first()->share_token);
    }

    public function testAnInviteLinkCannotBeEnabledOnAPublicGrowthSession()
    {
        $growthSession = $this->ownedGrowthSession(public: true);

        $this->actingAs($growthSession->owner)
            ->putJson(route('growth_sessions.update', $growthSession), ['has_invite_link' => true])
            ->assertSuccessful();

        $this->assertNull($growthSession->fresh()->share_token);
    }

    public function testMakingAGrowthSessionPublicRevokesItsInviteLink()
    {
        $growthSession = $this->ownedGrowthSession(unlisted: true);

        $this->actingAs($growthSession->owner)
            ->putJson(route('growth_sessions.update', $growthSession), ['is_public' => true])
            ->assertSuccessful();

        $this->assertNull($growthSession->fresh()->share_token);
    }

    public function testAShareTokenSuppliedByTheClientIsIgnoredOnCreation()
    {
        $owner = User::factory()->vehiklMember()->create();

        $this->actingAs($owner)
            ->postJson(route('growth_sessions.store'), $this->creationParameters([
                'has_invite_link' => true,
                'share_token' => 'a-token-the-client-chose',
            ]))
            ->assertSuccessful();

        $this->assertNotEquals('a-token-the-client-chose', GrowthSession::first()->share_token);
    }

    public function testAShareTokenSuppliedByTheClientIsIgnoredOnUpdate()
    {
        $growthSession = $this->ownedGrowthSession(unlisted: true);
        $originalToken = $growthSession->share_token;

        $this->actingAs($growthSession->owner)
            ->putJson(route('growth_sessions.update', $growthSession), ['share_token' => 'a-token-the-client-chose'])
            ->assertSuccessful();

        $this->assertEquals($originalToken, $growthSession->fresh()->share_token);
    }

    public function testDisablingTheInviteOptionClearsTheShareToken()
    {
        $growthSession = $this->ownedGrowthSession(unlisted: true);

        $this->actingAs($growthSession->owner)
            ->putJson(route('growth_sessions.update', $growthSession), ['has_invite_link' => false])
            ->assertSuccessful();

        $this->assertNull($growthSession->fresh()->share_token);
    }

    public function testAVisitorWhoHadUnlockedTheGrowthSessionIsRefusedOnTheirNextRequestAfterRevocation()
    {
        $growthSession = $this->ownedGrowthSession(unlisted: true);

        $this->get(route('growth_sessions.invitation', ['token' => $growthSession->share_token]));
        $this->showGrowthSession($growthSession)->assertSuccessful();

        $this->actingAs($growthSession->owner)
            ->putJson(route('growth_sessions.update', $growthSession), ['has_invite_link' => false])
            ->assertSuccessful();

        $this->app['auth']->forgetGuards();
        $this->showGrowthSession($growthSession)->assertNotFound();
    }

    public function testRevocationDoesNotRemoveSomeoneWhoHasAlreadyJoined()
    {
        $growthSession = $this->ownedGrowthSession(unlisted: true);
        $client = User::factory()->vehiklMember(false)->create();

        $this->actingAs($client)->get(route('growth_sessions.invitation', ['token' => $growthSession->share_token]));
        $this->actingAs($client)->postJson(route('growth_sessions.join', $growthSession))->assertSuccessful();

        $this->actingAs($growthSession->owner)
            ->putJson(route('growth_sessions.update', $growthSession), ['has_invite_link' => false])
            ->assertSuccessful();

        $this->assertTrue($growthSession->fresh()->hasAttendee($client));

        $this->flushSession();
        $this->actingAs($client);
        $this->showGrowthSession($growthSession)->assertSuccessful();
    }

    public function testTheShareUrlIsVisibleToTheOwner()
    {
        $growthSession = $this->ownedGrowthSession(unlisted: true);

        $this->actingAs($growthSession->owner);

        $this->showGrowthSession($growthSession)
            ->assertSuccessful()
            ->assertJsonPath('share_url', route('growth_sessions.invitation', ['token' => $growthSession->share_token]));
    }

    public function testTheShareUrlIsAbsentForAVehiklMemberWhoIsNotTheOwner()
    {
        $growthSession = $this->ownedGrowthSession(unlisted: true);

        $this->actingAs(User::factory()->vehiklMember()->create());

        $this->showGrowthSession($growthSession)
            ->assertSuccessful()
            ->assertJsonMissingPath('share_url');
    }

    public function testTheShareUrlIsAbsentForAVehiklAttendeeWhoIsNotTheOwner()
    {
        $growthSession = $this->ownedGrowthSession(unlisted: true);
        $colleague = User::factory()->vehiklMember()->create();
        $growthSession->attendees()->attach($colleague, ['user_type_id' => UserType::ATTENDEE_ID]);

        $this->actingAs($colleague);

        $this->showGrowthSession($growthSession)
            ->assertSuccessful()
            ->assertJsonMissingPath('share_url');
    }

    public function testTheShareUrlIsAbsentForAnUnlockedGuest()
    {
        $growthSession = $this->ownedGrowthSession(unlisted: true);

        $this->get(route('growth_sessions.invitation', ['token' => $growthSession->share_token]));

        $this->showGrowthSession($growthSession)
            ->assertSuccessful()
            ->assertJsonMissingPath('share_url');
    }

    public function testTheShareUrlIsAbsentForANonVehiklAttendee()
    {
        $growthSession = $this->ownedGrowthSession(unlisted: true);
        $client = User::factory()->vehiklMember(false)->create();
        $growthSession->attendees()->attach($client, ['user_type_id' => UserType::ATTENDEE_ID]);

        $this->actingAs($client);

        $this->showGrowthSession($growthSession)
            ->assertSuccessful()
            ->assertJsonMissingPath('share_url');
    }

    public function testTheShareUrlIsAbsentFromTheUnauthenticatedDayListing()
    {
        $growthSession = $this->ownedGrowthSession(unlisted: true);

        $this->getJson(route('growth_sessions.day'))
            ->assertSuccessful()
            ->assertJsonMissing(['share_url' => InviteLink::for($growthSession)->url()]);
    }

    public function testTheShareUrlIsAbsentFromTheWeekListingForAnUnlockedGuest()
    {
        $growthSession = $this->ownedGrowthSession(unlisted: true);

        $this->get(route('growth_sessions.invitation', ['token' => $growthSession->share_token]));

        $this->getJson(route('growth_sessions.week'))
            ->assertSuccessful()
            ->assertJsonMissing(['share_url' => InviteLink::for($growthSession)->url()]);
    }

    public function testTheShareUrlIsAbsentWhenNoInviteLinkIsActive()
    {
        $growthSession = $this->ownedGrowthSession();

        $this->actingAs($growthSession->owner);

        $this->showGrowthSession($growthSession)
            ->assertSuccessful()
            ->assertJsonMissingPath('share_url');
    }

    public function testSomeoneOtherThanTheOwnerCannotEnableAnInviteLink()
    {
        $growthSession = $this->ownedGrowthSession();
        $anotherVehiklMember = User::factory()->vehiklMember()->create();

        $this->actingAs($anotherVehiklMember)
            ->putJson(route('growth_sessions.update', $growthSession), ['has_invite_link' => true])
            ->assertForbidden();

        $this->assertNull($growthSession->fresh()->share_token);
    }

    public function testSomeoneOtherThanTheOwnerCannotRevokeAnInviteLink()
    {
        $growthSession = $this->ownedGrowthSession(unlisted: true);
        $anotherVehiklMember = User::factory()->vehiklMember()->create();

        $this->actingAs($anotherVehiklMember)
            ->putJson(route('growth_sessions.update', $growthSession), ['has_invite_link' => false])
            ->assertForbidden();

        $this->assertNotEmpty($growthSession->fresh()->share_token);
    }

    public function testTheInviteOptionHasToBeABoolean()
    {
        $growthSession = $this->ownedGrowthSession();

        $this->actingAs($growthSession->owner)
            ->putJson(route('growth_sessions.update', $growthSession), ['has_invite_link' => 'bananas'])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('has_invite_link');
    }

    private function ownedGrowthSession(bool $unlisted = false, bool $public = false): GrowthSession
    {
        $factory = GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(), ['user_type_id' => UserType::OWNER_ID], 'owners');

        return $unlisted
            ? $factory->unlisted()->create()
            : $factory->create(['is_public' => $public]);
    }

    private function creationParameters(array $params = []): array
    {
        return array_merge([
            'topic' => 'The fundamentals of foo',
            'title' => 'Foo',
            'location' => 'At the central mobbing area',
            'start_time' => now()->format('h:i a'),
            'end_time' => now()->addHour()->format('h:i a'),
            'date' => today(),
            'is_public' => false,
        ], $params);
    }

    private function showGrowthSession(GrowthSession $growthSession): TestResponse
    {
        return $this->getJson(route('growth_sessions.show', $growthSession));
    }
}
