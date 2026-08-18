<?php

namespace Database\Factories;

use App\Enums\NotificationType;
use App\Models\GrowthSession;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'initiator' => User::factory(),
            'user_id' => User::factory(),
            'growth_session_id' => GrowthSession::factory(),
            'read' => false,
            'event_types' => [$this->faker->randomElement(self::eventsWithALiveGrowthSession())],
            // The resource reads the session out of the snapshot, so a row without one describes
            // nothing. Taken from the session this notification is about, as dispatch would.
            'metadata' => fn (array $attributes) => GrowthSession::find($attributes['growth_session_id'])?->toNotificationMetadata() ?? [],
        ];
    }

    /**
     * Every event but GS_DELETED.
     *
     * A deletion is the one shape whose growth session no longer exists, so it is served from
     * the snapshot that forDeletedGrowthSession() sets. Leaving it in the default pool made a
     * plain factory row render no growth session at all, at random, one time in five.
     *
     * @return list<NotificationType>
     */
    private static function eventsWithALiveGrowthSession(): array
    {
        return array_values(array_filter(
            NotificationType::cases(),
            fn(NotificationType $eventType) => $eventType !== NotificationType::GS_DELETED,
        ));
    }

    /** A notification reporting several events from one save, the case composite types existed for. */
    public function reporting(NotificationType ...$eventTypes): static
    {
        return $this->state(['event_types' => $eventTypes]);
    }

    public function read(bool $read = true): static
    {
        return $this->state(['read' => $read]);
    }

    /** A notification whose growth session has since been deleted, leaving only the snapshot. */
    public function forDeletedGrowthSession(array $metadata = []): static
    {
        return $this->state([
            'event_types' => [NotificationType::GS_DELETED],
            // The session is gone, so there is no row to point at - the snapshot is all that is left.
            'growth_session_id' => null,
            'metadata' => $metadata + [
                'title' => 'A cancelled session',
                'location' => 'At AnyDesk XYZ - abcdefg',
                'date' => '2026-08-20',
                'start_time' => '03:30 pm',
                'end_time' => '05:00 pm',
            ],
        ]);
    }
}
