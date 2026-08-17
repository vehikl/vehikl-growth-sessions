<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Events\NotificationCreated;
use App\Http\Requests\IndexNotificationRequest;
use App\Jobs\ProcessNotifications;
use App\Models\GrowthSession;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([NotificationCreated::class]);
    }

    public function testAGuestCannotSeeNotifications()
    {
        $this->getJson(route('notifications.index'))->assertUnauthorized();
    }

    public function testItReturnsOnlyTheAuthenticatedUsersNotifications()
    {
        $user = User::factory()->create();
        $someoneElse = User::factory()->create();

        $mine = Notification::factory()->create(['user_id' => $user->id]);
        Notification::factory()->create(['user_id' => $someoneElse->id]);

        $this->actingAs($user)
            ->getJson(route('notifications.index'))
            ->assertSuccessful()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $mine->id);
    }

    public function testItReturnsTheTenMostRecentNotificationsByDefault()
    {
        $user = User::factory()->create();
        Notification::factory()->count(15)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson(route('notifications.index'))
            ->assertSuccessful()
            ->assertJsonCount(IndexNotificationRequest::DEFAULT_LIMIT);
    }

    public function testItReturnsTheNewestNotificationsFirst()
    {
        $user = User::factory()->create();
        $oldest = Notification::factory()->create(['user_id' => $user->id, 'created_at' => now()->subDays(2)]);
        $newest = Notification::factory()->create(['user_id' => $user->id, 'created_at' => now()]);

        $this->actingAs($user)
            ->getJson(route('notifications.index'))
            ->assertSuccessful()
            ->assertJsonPath('0.id', $newest->id)
            ->assertJsonPath('1.id', $oldest->id);
    }

    public function testItHonoursAnExplicitLimit()
    {
        $user = User::factory()->create();
        Notification::factory()->count(15)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson(route('notifications.index', ['limit' => 3]))
            ->assertSuccessful()
            ->assertJsonCount(3);
    }

    #[DataProvider('invalidLimitProvider')]
    public function testItRejectsAnUnusableLimit($limit)
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('notifications.index', ['limit' => $limit]))
            ->assertJsonValidationErrorFor('limit');
    }

    public static function invalidLimitProvider(): array
    {
        return [
            'zero' => [0],
            'negative' => [-1],
            'not a number' => ['ten'],
            'beyond the maximum' => [IndexNotificationRequest::MAX_LIMIT + 1],
        ];
    }

    public function testItIncludesTheInitiatorAndGrowthSession()
    {
        $user = User::factory()->create();
        $initiator = User::factory()->create();
        $growthSession = GrowthSession::factory()->create();

        Notification::factory()->create([
            'user_id' => $user->id,
            'initiator' => $initiator->id,
            'growth_session_id' => $growthSession->id,
            'type' => NotificationType::GS_COMMENT_ADDED,
        ]);

        $this->actingAs($user)
            ->getJson(route('notifications.index'))
            ->assertSuccessful()
            ->assertJsonPath('0.type', NotificationType::GS_COMMENT_ADDED->value)
            ->assertJsonPath('0.read', false)
            ->assertJsonPath('0.initiator.id', $initiator->id)
            ->assertJsonPath('0.initiator.name', $initiator->name)
            ->assertJsonPath('0.growth_session.id', $growthSession->id)
            ->assertJsonPath('0.growth_session.title', $growthSession->title)
            ->assertJsonPath('0.growth_session.location', $growthSession->location)
            ->assertJsonPath('0.growth_session.date', $growthSession->date->format('Y-m-d'))
            ->assertJsonPath('0.growth_session.start_time', $growthSession->start_time->format('h:i a'))
            ->assertJsonPath('0.growth_session.end_time', $growthSession->end_time->format('h:i a'))
            ->assertJsonMissingPath('0.growth_session_id')
            ->assertJsonMissingPath('0.metadata');
    }

    public function testALiveSessionIsReadFromTheRelationRatherThanItsSnapshot()
    {
        $user = User::factory()->create();
        $growthSession = GrowthSession::factory()->create(['title' => 'The current title']);

        Notification::factory()->create([
            'user_id' => $user->id,
            'growth_session_id' => $growthSession->id,
            'type' => NotificationType::GS_TIME_CHANGED,
            'metadata' => ['title' => 'A stale title nobody should see'],
        ]);

        $this->actingAs($user)
            ->getJson(route('notifications.index'))
            ->assertSuccessful()
            ->assertJsonPath('0.growth_session.title', 'The current title');
    }

    public function testAGrowthSessionDeletedAfterAnUnrelatedNotificationReportsNothing()
    {
        $user = User::factory()->create();

        Notification::factory()->create([
            'user_id' => $user->id,
            'growth_session_id' => null,
            'type' => NotificationType::GS_TIME_CHANGED,
            'metadata' => ['title' => 'A stale title nobody should see'],
        ]);

        $this->actingAs($user)
            ->getJson(route('notifications.index'))
            ->assertSuccessful()
            ->assertJsonPath('0.growth_session', null);
    }

    /**
     * The regression this snapshot exists for: on a real queue the worker runs after the growth
     * session row - and the pivot rows naming its members - are already gone.
     */
    public function testADeletedGrowthSessionStillNamesItselfInTheNotification()
    {
        Queue::fake();

        $owner = User::factory()->create();
        $attendee = User::factory()->create();
        $growthSession = GrowthSession::factory()->create([
            'title' => 'Pairing on Vue',
            'location' => 'At AnyDesk 12 - hunter2',
            'date' => now()->addDays(3)->format('Y-m-d'),
        ]);
        $growthSession->attendees()->attach($owner, ['user_type_id' => UserType::OWNER_ID]);
        $growthSession->attendees()->attach($attendee, ['user_type_id' => UserType::ATTENDEE_ID]);
        $growthSessionId = $growthSession->id;
        $expectedStart = $growthSession->start_time->format('h:i a');
        $expectedEnd = $growthSession->end_time->format('h:i a');

        $this->actingAs($owner)
            ->deleteJson(route('growth_sessions.destroy', $growthSession))
            ->assertSuccessful();

        $this->assertNull(GrowthSession::find($growthSessionId), 'the session is gone before the worker runs');

        // Run the queued job the way a worker would: after the deletion has already happened.
        Queue::pushed(ProcessNotifications::class)->each->handle();

        $payload = $this->actingAs($attendee)
            ->getJson(route('notifications.index'))
            ->assertSuccessful()
            ->assertJsonPath('0.type', NotificationType::GS_DELETED->value)
            // The session it names is gone, so there is nothing to link to - but it still describes itself.
            ->assertJsonPath('0.growth_session.id', null)
            ->assertJsonPath('0.growth_session.title', 'Pairing on Vue')
            ->assertJsonPath('0.growth_session.location', 'At AnyDesk 12 - hunter2')
            ->assertJsonPath('0.growth_session.start_time', $expectedStart)
            ->assertJsonPath('0.growth_session.end_time', $expectedEnd)
            ->json();

        $this->assertNotNull($payload[0]['growth_session']['date']);
    }

    public function testADeletionPredatingTheSnapshotColumnReportsNoGrowthSession()
    {
        $user = User::factory()->create();

        Notification::factory()->create([
            'user_id' => $user->id,
            'growth_session_id' => null,
            'type' => NotificationType::GS_DELETED,
            'metadata' => null,
        ]);

        $this->actingAs($user)
            ->getJson(route('notifications.index'))
            ->assertSuccessful()
            ->assertJsonPath('0.growth_session', null);
    }

    /**
     * Guards the factory rather than the endpoint: a default row must always be able to describe
     * its growth session, or every future test asserting on growth_session becomes a coin flip.
     */
    public function testADefaultFactoryNotificationAlwaysDescribesItsGrowthSession()
    {
        $user = User::factory()->create();
        Notification::factory()->count(30)->create(['user_id' => $user->id]);

        $payload = $this->actingAs($user)
            ->getJson(route('notifications.index', ['limit' => 30]))
            ->assertSuccessful()
            ->json();

        $this->assertCount(30, $payload);

        foreach ($payload as $notification) {
            $this->assertNotNull(
                $notification['growth_session'],
                "a default factory row rendered no growth session (type: {$notification['type']})",
            );
        }
    }

    public function testTheDeletedStateDescribesItselfWithoutALiveSession()
    {
        $user = User::factory()->create();
        Notification::factory()->forDeletedGrowthSession()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson(route('notifications.index'))
            ->assertSuccessful()
            ->assertJsonPath('0.type', NotificationType::GS_DELETED->value)
            ->assertJsonPath('0.growth_session.id', null)
            ->assertJsonPath('0.growth_session.title', 'A cancelled session')
            ->assertJsonPath('0.growth_session.location', 'At AnyDesk XYZ - abcdefg');
    }

    #[DataProvider('editProvider')]
    public function testAnEditRaisesExactlyOneNotificationDescribingEverythingThatMoved(array $changes, NotificationType $expected)
    {
        $owner = User::factory()->create();
        $attendee = User::factory()->create();
        $growthSession = GrowthSession::factory()->create([
            'location' => 'At AnyDesk 12 - hunter2',
            'date' => now()->addDays(3)->format('Y-m-d'),
        ]);
        $growthSession->attendees()->attach($owner, ['user_type_id' => UserType::OWNER_ID]);
        $growthSession->attendees()->attach($attendee, ['user_type_id' => UserType::ATTENDEE_ID]);

        $this->actingAs($owner)
            ->putJson(route('growth_sessions.update', $growthSession), [
                'location' => $growthSession->location,
                'start_time' => $growthSession->start_time->format('h:i a'),
                'end_time' => $growthSession->end_time->format('h:i a'),
                ...$changes,
            ])
            ->assertSuccessful();

        $notifications = Notification::query()->where('user_id', $attendee->id)->get();

        $this->assertCount(1, $notifications, 'one save must not fan out into several notifications');
        $this->assertSame($expected, $notifications->first()->type);
    }

    public static function editProvider(): array
    {
        return [
            'only the time moved' => [
                ['start_time' => '09:00 am', 'end_time' => '10:00 am'],
                NotificationType::GS_TIME_CHANGED,
            ],
            'only the start time moved' => [
                ['start_time' => '09:00 am'],
                NotificationType::GS_TIME_CHANGED,
            ],
            'only the location moved' => [
                ['location' => 'Somewhere else entirely'],
                NotificationType::GS_LOCATION_CHANGED,
            ],
            'both moved in one save' => [
                ['start_time' => '09:00 am', 'end_time' => '10:00 am', 'location' => 'Somewhere else entirely'],
                NotificationType::GS_TIME_AND_LOCATION_CHANGED,
            ],
        ];
    }

    public function testAnEditThatTouchesNeitherTimeNorLocationNotifiesNobody()
    {
        $owner = User::factory()->create();
        $attendee = User::factory()->create();
        $growthSession = GrowthSession::factory()->create(['date' => now()->addDays(3)->format('Y-m-d')]);
        $growthSession->attendees()->attach($owner, ['user_type_id' => UserType::OWNER_ID]);
        $growthSession->attendees()->attach($attendee, ['user_type_id' => UserType::ATTENDEE_ID]);

        $this->actingAs($owner)
            ->putJson(route('growth_sessions.update', $growthSession), ['topic' => 'A brand new topic'])
            ->assertSuccessful();

        $this->assertSame(0, Notification::query()->count());
    }
}
