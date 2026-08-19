<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\GrowthSession;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class GrowthSessionUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private const FIELD_LABELS = [
        'date' => 'Date',
        'start_time' => 'Start time',
        'location' => 'Location',
    ];

    public function __construct(
        public GrowthSession $growthSession,
        public NotificationType $type,
        public string $field,
        public mixed $old,
        public mixed $new,
    ) {}

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
            'change' => [
                'field' => $this->field,
                'label' => self::FIELD_LABELS[$this->field] ?? $this->field,
                'value' => $this->formatValue(),
            ],
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

    private function formatValue(): string
    {
        if ($this->new === null || $this->new === '') {
            return 'None';
        }

        if ($this->field === 'date') {
            return Carbon::parse($this->new)->format('M j, Y');
        }

        if ($this->field === 'start_time') {
            return Carbon::parse($this->new)->format('g:i A');
        }

        return (string) $this->new;
    }
}
