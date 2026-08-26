<?php

namespace Tests\Unit\Policies;

use App\Enums\Role;
use App\Models\GrowthSession;
use App\Models\User;
use App\Policies\GrowthSessionPolicy;
use App\Support\Seating;
use Tests\TestCase;

/**
 * Eligibility is the policy's question alone, so it is asked of the policy directly. Through a route
 * every refusal looks the same - one 403 - and a rule stated in two layers cannot be told apart.
 */
class GrowthSessionPolicyTest extends TestCase
{
    private GrowthSessionPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new GrowthSessionPolicy;
    }

    public function test_a_member_waiting_in_line_cannot_watch_instead()
    {
        $growthSession = $this->fullGrowthSession(['allow_watchers' => true]);
        $hopeful = $this->memberInLineAt($growthSession);

        $this->assertFalse($this->policy->watch($hopeful, $growthSession->fresh()));
    }

    public function test_a_member_waiting_in_line_cannot_ask_for_a_seat_again()
    {
        $growthSession = $this->fullGrowthSession();
        $hopeful = $this->memberInLineAt($growthSession);

        $this->assertFalse($this->policy->join($hopeful, $growthSession->fresh()));
    }

    public function test_a_member_waiting_in_line_may_give_up_their_place()
    {
        $growthSession = $this->fullGrowthSession();
        $hopeful = $this->memberInLineAt($growthSession);

        $this->assertTrue($this->policy->leave($hopeful, $growthSession->fresh()));
    }

    public function test_an_attendee_cannot_watch_as_well()
    {
        $growthSession = GrowthSession::factory()->create(['allow_watchers' => true]);
        $attendee = User::factory()->create();
        $growthSession->attendees()->attach($attendee, ['user_type_id' => Role::Attendee->value]);

        $this->assertFalse($this->policy->watch($attendee, $growthSession->fresh()));
    }

    /** Capacity is not a policy question - a full growth session is something to ask for. */
    public function test_somebody_with_no_role_may_still_ask_to_join_a_full_growth_session()
    {
        $growthSession = $this->fullGrowthSession();
        $outsider = User::factory()->vehiklMember()->create();

        $this->assertTrue($this->policy->join($outsider, $growthSession->fresh()));
    }

    /**
     * The policy is handed bare models by route model binding, so it loads what it reads itself.
     * With lazy loading prevented outside production, a missing eager load fails here rather than
     * in a browser.
     */
    public function test_the_policy_answers_without_the_caller_loading_anything()
    {
        $growthSession = $this->fullGrowthSession(['allow_watchers' => true]);
        $hopeful = $this->memberInLineAt($growthSession);
        $bare = GrowthSession::query()->findOrFail($growthSession->id);

        $this->assertFalse($this->policy->watch($hopeful, $bare));
        $this->assertTrue($this->policy->leave($hopeful, $bare));
    }

    private function fullGrowthSession(array $attributes = []): GrowthSession
    {
        $growthSession = GrowthSession::factory()->create($attributes + ['attendee_limit' => 1]);

        $growthSession->attendees()->attach(
            User::factory()->create(),
            ['user_type_id' => Role::Attendee->value]
        );

        return $growthSession;
    }

    private function memberInLineAt(GrowthSession $growthSession): User
    {
        $hopeful = User::factory()->vehiklMember()->create();
        Seating::for($growthSession)->take($hopeful);

        return $hopeful;
    }
}
