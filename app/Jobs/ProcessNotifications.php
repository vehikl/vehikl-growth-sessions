<?php

namespace App\Jobs;

use App\Enums\NotificationType;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;

class ProcessNotifications implements ShouldQueue
{
    use Queueable;

    /**
     * @param Collection<int, int> $recipients
     * @param array<string, string|null> $metadata Snapshot of the growth session, taken at dispatch time.
     */
    public function __construct(
        protected int $growthSessionId,
        protected int $initiatorId,
        protected NotificationType $notificationType,
        protected Collection $recipients,
        protected array $metadata = [],
    ) {
    }

    public function handle(): void
    {
        if ($this->recipients->isEmpty()) {
            return;
        }

        foreach ($this->recipients as $recipient) {
            //observer will dispatch notification
            Notification::query()->create([
                'initiator' => $this->initiatorId,
                'user_id' => $recipient,
                'growth_session_id' => $this->growthSessionId,
                'type' => $this->notificationType,
                'metadata' => $this->metadata,
            ]);
        }
    }
}
