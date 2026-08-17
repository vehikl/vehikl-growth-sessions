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
            'type' => $this->faker->randomElement(NotificationType::cases()),
        ];
    }

    public function read(bool $read = true): static
    {
        return $this->state(['read' => $read]);
    }

    /** A notification whose growth session has since been deleted, leaving only the snapshot. */
    public function forDeletedGrowthSession(array $metadata = []): static
    {
        return $this->state([
            'type' => NotificationType::GS_DELETED,
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
