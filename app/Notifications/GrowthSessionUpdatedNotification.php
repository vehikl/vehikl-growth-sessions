<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\GrowthSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class GrowthSessionUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private const FIELD_LABELS = [
        'date' => 'Date',
        'start_time' => 'Start time',
        'end_time' => 'End time',
        'location' => 'Location',
    ];

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes  Raw old/new values keyed by field name — every
     *                                                                 field here belongs to the same $type, so a save
     *                                                                 that moves both start and end time is still one
     *                                                                 notification, while a save that moves the date
     *                                                                 and the location is two.
     */
    public function __construct(public GrowthSession $growthSession, public NotificationType $type, public array $changes) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type->value,
            'growth_session_id' => $this->growthSession->id,
            'title' => $this->growthSession->title,
            'changes' => $this->describeChanges(),
            'url' => "/growth_sessions/{$this->growthSession->id}",
        ];
    }

    /**
     * Without this, the broadcast payload's `type` gets overwritten with this class's fully
     * qualified name — `BroadcastNotificationCreated::broadcastWith()` merges `toArray()` with
     * `['type' => $this->broadcastType()]` last, and the default `broadcastType()` is `get_class()`.
     */
    public function broadcastType(): string
    {
        return $this->type->value;
    }

    /**
     * @return array<int, array{field: string, label: string}>
     */
    private function describeChanges(): array
    {
        $lines = [];

        foreach (array_keys($this->changes) as $field) {
            $lines[] = [
                'field' => $field,
                'label' => self::FIELD_LABELS[$field] ?? $field,
            ];
        }

        return $lines;
    }
}
