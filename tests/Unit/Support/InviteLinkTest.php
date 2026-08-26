<?php

namespace Tests\Unit\Support;

use App\Enums\Role;
use App\Models\GrowthSession;
use App\Models\User;
use App\Support\GrowthSessionUnlocks;
use App\Support\InviteLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InviteLinkTest extends TestCase
{
    use RefreshDatabase;

    public function testAskingForALinkGeneratesAToken()
    {
        $growthSession = $this->privateGrowthSession();

        InviteLink::for($growthSession)->set(true);

        $this->assertNotEmpty($growthSession->share_token);
    }

    public function testAskingForALinkAgainKeepsTheTokenAlreadyHandedOut()
    {
        $growthSession = GrowthSession::factory()->unlisted()->make();
        $originalToken = $growthSession->share_token;

        InviteLink::for($growthSession)->set(true);

        $this->assertEquals($originalToken, $growthSession->share_token);
    }

    public function testGivingUpTheLinkClearsTheToken()
    {
        $growthSession = GrowthSession::factory()->unlisted()->make();

        InviteLink::for($growthSession)->set(false);

        $this->assertNull($growthSession->share_token);
    }

    public function testAPublicGrowthSessionNeverGetsALink()
    {
        $growthSession = GrowthSession::factory()->make(['is_public' => true]);

        InviteLink::for($growthSession)->set(true);

        $this->assertNull($growthSession->share_token);
    }

    public function testTurningAGrowthSessionPublicRevokesTheLinkItHadAlreadyHandedOut()
    {
        $growthSession = GrowthSession::factory()->unlisted()->make();
        $growthSession->is_public = true;

        InviteLink::for($growthSession)->set(true);

        $this->assertNull($growthSession->share_token);
    }

    public function testALinkExistsOnlyOnceItHasBeenAskedFor()
    {
        $growthSession = $this->privateGrowthSession();

        $this->assertFalse(InviteLink::for($growthSession)->exists());

        InviteLink::for($growthSession)->set(true);

        $this->assertTrue(InviteLink::for($growthSession)->exists());
    }

    public function testTheUrlPointsAtTheInvitationRoute()
    {
        $growthSession = GrowthSession::factory()->unlisted()->make();

        $this->assertEquals(
            route('growth_sessions.invitation', ['token' => $growthSession->share_token]),
            InviteLink::for($growthSession)->url()
        );
    }

    public function testThereIsNoUrlWithoutALink()
    {
        $this->assertNull(InviteLink::for($this->privateGrowthSession())->url());
    }

    public function testTheUrlIsVisibleToTheOwner()
    {
        $growthSession = $this->unlistedGrowthSession();

        $this->assertTrue(InviteLink::for($growthSession)->isVisibleTo($growthSession->owner));
    }

    public function testTheUrlIsHiddenFromAVehiklMemberWhoDoesNotOwnTheGrowthSession()
    {
        $growthSession = $this->unlistedGrowthSession();

        $this->assertFalse(InviteLink::for($growthSession)->isVisibleTo(User::factory()->vehiklMember()->create()));
    }

    public function testTheUrlIsHiddenFromGuestsAndFromVisitorsWhoAreNotSignedIn()
    {
        $growthSession = $this->unlistedGrowthSession();
        $inviteLink = InviteLink::for($growthSession);

        $this->assertFalse($inviteLink->isVisibleTo(User::factory()->vehiklMember(false)->create()));
        $this->assertFalse($inviteLink->isVisibleTo(null));
    }

    public function testThereIsNoUrlToShowWhenNoLinkHasBeenHandedOut()
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(), ['user_type_id' => Role::Owner->value], 'owners')
            ->create(['is_public' => false]);

        $this->assertFalse(InviteLink::for($growthSession)->isVisibleTo($growthSession->owner));
    }

    public function testALinkIsUnlockedOnlyAfterTheVisitorHasOpenedIt()
    {
        $this->startSession();
        $growthSession = GrowthSession::factory()->unlisted()->make();

        $this->assertFalse(InviteLink::for($growthSession)->hasBeenUnlocked());

        GrowthSessionUnlocks::unlock($growthSession->share_token);

        $this->assertTrue(InviteLink::for($growthSession)->hasBeenUnlocked());
    }

    public function testAGrowthSessionWithoutALinkIsNeverUnlocked()
    {
        $this->startSession();

        $this->assertFalse(InviteLink::for($this->privateGrowthSession())->hasBeenUnlocked());
    }

    private function privateGrowthSession(): GrowthSession
    {
        return GrowthSession::factory()->make(['is_public' => false]);
    }

    private function unlistedGrowthSession(): GrowthSession
    {
        return GrowthSession::factory()
            ->hasAttached(User::factory()->vehiklMember(), ['user_type_id' => Role::Owner->value], 'owners')
            ->unlisted()
            ->create();
    }
}
