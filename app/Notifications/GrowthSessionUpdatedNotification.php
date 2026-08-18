<?php

namespace App\Notifications;

use App\Models\AnyDesk;
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
        'end_time' => 'End time',
        'location' => 'Location',
        'anydesk_id' => 'Meeting link',
    ];

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes  Raw old/new values keyed by field name.
     */
    public function __construct(public GrowthSession $growthSession, public array $changes) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'growth_session_updated',
            'growth_session_id' => $this->growthSession->id,
            'title' => $this->growthSession->title,
            'changes' => $this->describeChanges(),
            'url' => "/growth_sessions/{$this->growthSession->id}",
        ];
    }

    /**
     * @return string[]
     */
    private function describeChanges(): array
    {
        $lines = [];

        foreach ($this->changes as $field => $value) {
            $label = self::FIELD_LABELS[$field] ?? $field;
            $lines[] = sprintf(
                '%s changed %s → %s',
                $label,
                $this->formatValue($field, $value['old']),
                $this->formatValue($field, $value['new']),
            );
        }

        return $lines;
    }

    private function formatValue(string $field, mixed $value): string
    {
        if ($field === 'anydesk_id') {
            return $value ? (optional(AnyDesk::find($value))->name ?? "AnyDesk #{$value}") : 'None';
        }

        if ($field === 'date') {
            return $value ? Carbon::parse($value)->format('M j, Y') : 'None';
        }

        if (in_array($field, ['start_time', 'end_time'], true)) {
            return $value ? Carbon::parse($value)->format('g:i A') : 'None';
        }

        return $value !== null && $value !== '' ? (string) $value : 'None';
    }
}
