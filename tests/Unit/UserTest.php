<?php

namespace Tests\Unit;

use App\GrowthSession;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_the_total_number_of_growth_sessions_attended_by_the_user(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        GrowthSession::factory()->times(5)->create();
        GrowthSession::factory()
            ->times(4)
            ->create()
            ->map(function (GrowthSession $session) use ($user) {
                $session->attendees()->attach($user);
            });

        $this->assertEquals(4, $user->attendedGrowthSessionsCount);
    }
}
