<?php

namespace Tests\Feature\GrowthSessions;

use App\Enums\Role;
use App\Models\GrowthSession;
use App\Models\User;
use Tests\TestCase;

class GrowthSessionDestroyTest extends TestCase
{
    public function testTheOwnerCanDeleteAnExistingGrowthSession()
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => Role::Owner->value], 'owners')
            ->create();

        $this->actingAs($growthSession->owner)->deleteJson(route(
            'growth_sessions.destroy',
            ['growth_session' => $growthSession->id]
        ))->assertSuccessful();

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
