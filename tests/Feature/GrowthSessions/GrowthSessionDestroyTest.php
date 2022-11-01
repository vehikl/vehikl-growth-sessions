<?php

namespace Tests\Feature\GrowthSessions;

use App\GrowthSession;
use App\User;
use App\UserType;
use Tests\TestCase;

class GrowthSessionDestroyTest extends TestCase
{
    public function testTheOwnerCanDeleteAnExistingGrowthSession()
    {
        $this->withoutExceptionHandling();
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create();

        $this->actingAs($growthSession->owner)->deleteJson(route(
            'growth_sessions.destroy',
            ['growth_session' => $growthSession->id]
        ))->assertInvalid()
            ->assertSuccessful();

        $this->assertEmpty($growthSession->fresh());
    }

    public function testAUserThatIsNotAnOwnerOfAGrowthSessionCannotDeleteIt()
    {
        $growthSession = GrowthSession::factory()->create();
        /** @var User $notTheOwner */
        $notTheOwner = User::factory()->create();

        $this->actingAs($notTheOwner)->deleteJson(route(
            'growth_sessions.destroy',
            ['growth_session' => $growthSession->id]
        ))
            ->assertForbidden();
    }
}
