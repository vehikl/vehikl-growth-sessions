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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    private static ?string $aLaterDate = null;

    /**
     * Further out than any session these tests create, so an edit moving a session here always
     * changes the date and always satisfies the request's after_or_equal:today rule. Carbon rather
     * than now(), because data providers run before the application is booted.
     *
     * Worked out once and kept: the provider builds the request payload long before the test body
     * asserts on it, and recomputing would disagree with itself across a UTC midnight.
     */
    private static function aLaterDate(): string
    {
        return self::$aLaterDate ??= Carbon::now()->addDays(10)->format('Y-m-d');
    }

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
            'event_types' => [NotificationType::GS_COMMENT_ADDED],
        ]);

        $this->actingAs($user)
            ->getJson(route('notifications.index'))
            ->assertSuccessful()
            ->assertJsonPath('0.event_types', [NotificationType::GS_COMMENT_ADDED->value])
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

    /**
     * The shape the composite types were replaced by: several events on one notification, kept in
     * the order they were reported so a reader can render them as one sentence.
     */
    public function testANotificationCarriesEveryEventItReports()
    {
        $user = User::factory()->create();

        Notification::factory()
            ->reporting(NotificationType::GS_DATE_CHANGED, NotificationType::GS_TIME_CHANGED, NotificationType::GS_LOCATION_CHANGED)
            ->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson(route('notifications.index'))
            ->assertSuccessful()
            ->assertJsonPath('0.event_types', [
                NotificationType::GS_DATE_CHANGED->value,
                NotificationType::GS_TIME_CHANGED->value,
                NotificationType::GS_LOCATION_CHANGED->value,
            ]);
    }

    /** A single event is still a list, so a consumer never has to tell the two shapes apart. */
    public function testASingleEventIsStillReportedAsAList()
    {
        $user = User::factory()->create();

        Notification::factory()
            ->reporting(NotificationType::GS_COMMENT_ADDED)
            ->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson(route('notifications.index'))
            ->assertSuccessful()
            ->assertJsonPath('0.event_types', [NotificationType::GS_COMMENT_ADDED->value]);
    }

    public function testASessionIsReadFromItsSnapshotRatherThanTheLiveRow()
    {
        $user = User::factory()->create();
        $growthSession = GrowthSession::factory()->create(['title' => 'The title it has now']);

        Notification::factory()->create([
            'user_id' => $user->id,
            'growth_session_id' => $growthSession->id,
            'event_types' => [NotificationType::GS_TIME_CHANGED],
            'metadata' => ['title' => 'The title it had then'],
        ]);

        $this->actingAs($user)
            ->getJson(route('notifications.index'))
            ->assertSuccessful()
            ->assertJsonPath('0.growth_session.title', 'The title it had then')
            // The id is not something the session said, so it still points at the row to open.
            ->assertJsonPath('0.growth_session.id', $growthSession->id);
    }

    /**
     * The regression the snapshot rule exists for. A notification used to quote the live row, so a
     * later edit by somebody else silently rewrote what an earlier one said the first person did -
     * "Ada moved it to 9am" would start reading "Ada moved it to 7pm" the moment Bob moved it again.
     */
    public function testALaterEditDoesNotChangeWhatAnEarlierNotificationSays()
    {
        $owner = User::factory()->create();
        $attendee = User::factory()->create();
        $growthSession = GrowthSession::factory()->create(['date' => now()->addDays(3)->format('Y-m-d')]);
        $growthSession->attendees()->attach($owner, ['user_type_id' => UserType::OWNER_ID]);
        $growthSession->attendees()->attach($attendee, ['user_type_id' => UserType::ATTENDEE_ID]);

        foreach ([['09:00 am', '10:00 am'], ['07:00 pm', '09:00 pm']] as [$start, $end]) {
            $this->actingAs($owner)
                ->putJson(route('growth_sessions.update', $growthSession), ['start_time' => $start, 'end_time' => $end])
                ->assertSuccessful();
        }

        $payload = $this->actingAs($attendee)
            ->getJson(route('notifications.index'))
            ->assertSuccessful()
            ->json();

        // Keyed by id rather than read positionally: both were raised in the same second, and
        // latest() has no tiebreaker, so their order in the response is not guaranteed.
        $rows = collect($payload)->keyBy('id');
        [$earlier, $later] = Notification::query()->where('user_id', $attendee->id)->orderBy('id')->pluck('id')->all();

        $this->assertSame('09:00 am', $rows[$earlier]['growth_session']['start_time'], 'the first notification must still say what the first edit did');
        $this->assertSame('10:00 am', $rows[$earlier]['growth_session']['end_time']);
        $this->assertSame('07:00 pm', $rows[$later]['growth_session']['start_time']);
        $this->assertSame('09:00 pm', $rows[$later]['growth_session']['end_time']);
    }

    public function testANotificationStillDescribesASessionThatHasSinceGone()
    {
        $user = User::factory()->create();

        Notification::factory()->create([
            'user_id' => $user->id,
            'growth_session_id' => null,
            'event_types' => [NotificationType::GS_TIME_CHANGED],
            'metadata' => ['title' => 'A session that has since been deleted'],
        ]);

        $this->actingAs($user)
            ->getJson(route('notifications.index'))
            ->assertSuccessful()
            ->assertJsonPath('0.growth_session.title', 'A session that has since been deleted')
            // Nothing left to open, so nothing to link to.
            ->assertJsonPath('0.growth_session.id', null);
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
            ->assertJsonPath('0.event_types', [NotificationType::GS_DELETED->value])
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
            'event_types' => [NotificationType::GS_DELETED],
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
                'a default factory row rendered no growth session (events: ' . implode(', ', $notification['event_types']) . ')',
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
            ->assertJsonPath('0.event_types', [NotificationType::GS_DELETED->value])
            ->assertJsonPath('0.growth_session.id', null)
            ->assertJsonPath('0.growth_session.title', 'A cancelled session')
            ->assertJsonPath('0.growth_session.location', 'At AnyDesk XYZ - abcdefg');
    }

    /**
     * Pins the pushed payload to the fetched one. They fill the same place in a client, so a field
     * added to the resource must not silently reach only half of it.
     */
    public function testTheBroadcastPayloadIsWhatTheEndpointWouldHaveReturned()
    {
        $user = User::factory()->create();
        $initiator = User::factory()->create();
        $growthSession = GrowthSession::factory()->create();

        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'initiator' => $initiator->id,
            'growth_session_id' => $growthSession->id,
            'event_types' => [NotificationType::GS_COMMENT_ADDED],
        ]);

        // Encoded and decoded so enums and dates are compared as they go over the wire.
        $broadcast = json_decode(json_encode((new NotificationCreated($notification))->broadcastWith()), true);

        $fetched = $this->actingAs($user)
            ->getJson(route('notifications.index'))
            ->assertSuccessful()
            ->json()[0];

        $this->assertSame($fetched, $broadcast);
        $this->assertSame($initiator->id, $broadcast['initiator']['id']);
        $this->assertSame($growthSession->id, $broadcast['growth_session']['id']);
    }

    /**
     * The name and channel the client subscribes to and routes/channels.php authorises. Renaming
     * either without the other leaves notifications broadcasting into a channel nobody hears.
     */
    public function testItBroadcastsToTheRecipientAloneUnderAStableName()
    {
        $recipient = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $recipient->id]);

        $event = new NotificationCreated($notification);

        $this->assertSame('notification.created', $event->broadcastAs());
        $this->assertSame(
            ['private-notifications.' . $recipient->id],
            array_map(fn($channel) => (string) $channel, $event->broadcastOn()),
        );
    }

    public function testABroadcastDeletionCarriesItsSnapshot()
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->forDeletedGrowthSession()->create(['user_id' => $user->id]);

        $payload = (new NotificationCreated($notification))->broadcastWith();

        $this->assertNull($payload['growth_session']['id']);
        $this->assertSame('A cancelled session', $payload['growth_session']['title']);
    }

    public function testCommentingNotifiesTheOtherParticipantsButNotTheCommenter()
    {
        $owner = User::factory()->create();
        $attendee = User::factory()->create();
        $watcher = User::factory()->create();
        $growthSession = GrowthSession::factory()->create(['title' => 'Pairing on Vue']);
        $growthSession->attendees()->attach($owner, ['user_type_id' => UserType::OWNER_ID]);
        $growthSession->attendees()->attach($attendee, ['user_type_id' => UserType::ATTENDEE_ID]);
        $growthSession->watchers()->attach($watcher, ['user_type_id' => UserType::WATCHER_ID]);

        $this->actingAs($attendee)
            ->postJson(route('growth_sessions.comments.store', $growthSession), ['content' => 'Looking forward to this'])
            ->assertSuccessful();

        $notifications = Notification::query()->get();

        $this->assertEqualsCanonicalizing(
            [$owner->id, $watcher->id],
            $notifications->pluck('user_id')->all(),
            'everyone involved except the commenter should hear about it',
        );
        $notifications->each(
            fn (Notification $notification) => $this->assertSame(
                [NotificationType::GS_COMMENT_ADDED],
                $notification->event_types->all(),
                'a comment reports one event and only that event',
            ),
        );
        $this->assertSame([$attendee->id], $notifications->pluck('initiator')->unique()->all());
    }

    public function testACommentNotificationCarriesTheGrowthSessionItWasLeftOn()
    {
        $owner = User::factory()->create();
        $commenter = User::factory()->create();
        $growthSession = GrowthSession::factory()->create(['title' => 'Pairing on Vue']);
        $growthSession->attendees()->attach($owner, ['user_type_id' => UserType::OWNER_ID]);
        $growthSession->attendees()->attach($commenter, ['user_type_id' => UserType::ATTENDEE_ID]);

        $this->actingAs($commenter)
            ->postJson(route('growth_sessions.comments.store', $growthSession), ['content' => 'Looking forward to this'])
            ->assertSuccessful();

        $this->actingAs($owner)
            ->getJson(route('notifications.index'))
            ->assertSuccessful()
            ->assertJsonPath('0.event_types', [NotificationType::GS_COMMENT_ADDED->value])
            ->assertJsonPath('0.growth_session.id', $growthSession->id)
            ->assertJsonPath('0.growth_session.title', 'Pairing on Vue')
            ->assertJsonPath('0.initiator.id', $commenter->id);
    }

    #[DataProvider('editProvider')]
    public function testAnEditRaisesExactlyOneNotificationDescribingEverythingThatMoved(array $changes, array $expected)
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
        $this->assertSame($expected, $notifications->first()->event_types->all());
    }

    /**
     * The expectation is a list because the notification is: everything one save moved, in the
     * order a reader would say it. The multi-event rows are the ones that used to need a composite
     * type of their own.
     */
    public static function editProvider(): array
    {
        return [
            'only the time moved' => [
                ['start_time' => '09:00 am', 'end_time' => '10:00 am'],
                [NotificationType::GS_TIME_CHANGED],
            ],
            'only the start time moved' => [
                ['start_time' => '09:00 am'],
                [NotificationType::GS_TIME_CHANGED],
            ],
            'only the location moved' => [
                ['location' => 'Somewhere else entirely'],
                [NotificationType::GS_LOCATION_CHANGED],
            ],
            'the time and the location moved' => [
                ['start_time' => '09:00 am', 'end_time' => '10:00 am', 'location' => 'Somewhere else entirely'],
                [NotificationType::GS_TIME_CHANGED, NotificationType::GS_LOCATION_CHANGED],
            ],
            'only the date moved' => [
                ['date' => self::aLaterDate()],
                [NotificationType::GS_DATE_CHANGED],
            ],
            'the date and the time moved' => [
                ['date' => self::aLaterDate(), 'start_time' => '09:00 am', 'end_time' => '10:00 am'],
                [NotificationType::GS_DATE_CHANGED, NotificationType::GS_TIME_CHANGED],
            ],
            'the date and the location moved' => [
                ['date' => self::aLaterDate(), 'location' => 'Somewhere else entirely'],
                [NotificationType::GS_DATE_CHANGED, NotificationType::GS_LOCATION_CHANGED],
            ],
            'the day, the clock and the room all moved' => [
                [
                    'date' => self::aLaterDate(),
                    'start_time' => '09:00 am',
                    'end_time' => '10:00 am',
                    'location' => 'Somewhere else entirely',
                ],
                [
                    NotificationType::GS_DATE_CHANGED,
                    NotificationType::GS_TIME_CHANGED,
                    NotificationType::GS_LOCATION_CHANGED,
                ],
            ],
        ];
    }

    /**
     * The snapshot is taken after the save, so it holds where the session moved to rather than where
     * it was. That is what a reader needs from a notification saying the date changed.
     */
    public function testADateChangeRecordsTheNewDateOnTheNotification()
    {
        $owner = User::factory()->create();
        $attendee = User::factory()->create();
        $growthSession = GrowthSession::factory()->create(['date' => now()->addDays(3)->format('Y-m-d')]);
        $growthSession->attendees()->attach($owner, ['user_type_id' => UserType::OWNER_ID]);
        $growthSession->attendees()->attach($attendee, ['user_type_id' => UserType::ATTENDEE_ID]);

        $this->actingAs($owner)
            ->putJson(route('growth_sessions.update', $growthSession), ['date' => self::aLaterDate()])
            ->assertSuccessful();

        $notification = Notification::query()->where('user_id', $attendee->id)->sole();

        $this->assertSame(self::aLaterDate(), $notification->metadata['date']);

        $this->actingAs($attendee)
            ->getJson(route('notifications.index'))
            ->assertSuccessful()
            ->assertJsonPath('0.event_types', [NotificationType::GS_DATE_CHANGED->value])
            ->assertJsonPath('0.growth_session.date', self::aLaterDate());
    }

    public function testAnEditThatMovesNoneOfTheDateTimeOrLocationNotifiesNobody()
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
