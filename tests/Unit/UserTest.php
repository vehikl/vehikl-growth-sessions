<?php

namespace Tests\Unit;

use App\GrowthSession;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_returns_the_total_number_of_growth_sessions_attended_by_the_user(): void
    {
        GrowthSession::factory()->times(5)->create();
        GrowthSession::factory()
            ->times(4)
            ->create()
            ->map(function (GrowthSession $session) {
                $session->attendees()->attach($this->user);
            });

        $this->assertEquals(4, $this->user->attendedGrowthSessionsCount);
    }
    /** @test */
    public function it_returns_the_avg_number_of_growth_sessions_attended_by_the_user(): void
    {
        Carbon::setTestNow();
        GrowthSession::factory()
            ->times(1)
            ->create()
            ->map(function (GrowthSession $session) {
                $session->attendees()->attach($this->user);
            });

        GrowthSession::factory()
            ->times(1)
            ->create([
                'date' => now()->subWeeks(1)
            ])
            ->map(function (GrowthSession $session) {
                $session->attendees()->attach($this->user);
            });
        GrowthSession::factory()
            ->count(1)
            ->create([
                'date' => now()->subWeeks(2)
            ])
            ->map(function (GrowthSession $session) {
                $session->attendees()->attach($this->user);
            });

        $this->assertEquals(1, $this->user->attendedGrowthSessionsAverage);
    }
}


