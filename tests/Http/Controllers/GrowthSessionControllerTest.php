<?php

namespace Tests\Http\Controllers;

use App\Events\GrowthSessionAttendeeChanged;
use App\Events\GrowthSessionCreated;
use App\Events\Slack\AnnounceGrowthSession;
use App\Models\GrowthSession;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GrowthSessionControllerTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake();

        $this->user = User::factory()->vehiklMember()->create();
    }

    #[Test]
    public function itReturnsGrowthSessionJson(): void
    {
        $growthSession = GrowthSession::factory()->create();

        $this->getJson(route('growth_sessions.show', $growthSession->id))
            ->assertSuccessful()
            ->assertJson([
                'id' => $growthSession->id,
            ]);
    }

    #[Test]
    public function itCreatesANewGrowthSession(): void
    {
        $payload = [
            'title' => fake()->name(),
            'topic' => fake()->paragraph(),
            'location' => fake()->address(),
            'start_time' => '03:30 pm',
            'end_time' => '05:00 pm',
            'date' => now()->toDateString(),
            'attendee_limit' => 2,
        ];

        $this->actingAs($this->user)
            ->postJson(route('growth_sessions.store'), $payload)
            ->assertSuccessful()
            ->assertJson($payload)
            ->assertJsonStructure(['id']);

        Event::assertDispatched(AnnounceGrowthSession::class);
    }

}
