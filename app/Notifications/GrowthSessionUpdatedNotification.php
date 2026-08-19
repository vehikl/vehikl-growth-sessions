<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\GrowthSession;
use Carbon\Carbon;

class GrowthSessionUpdatedNotification extends BaseGrowthSessionNotification
{
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

    public function notificationType(): NotificationType
    {
        return $this->type;
    }

    public function toArray(): array
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
