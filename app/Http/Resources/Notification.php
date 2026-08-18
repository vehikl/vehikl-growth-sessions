<?php

namespace App\Http\Resources;

use App\Enums\NotificationType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Notification extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // Always a list, even for the one-event notifications, so a consumer never has to
            // handle two shapes.
            'event_types' => $this->event_types->map(fn (NotificationType $eventType) => $eventType->value)->values()->all(),
            'read' => $this->read,
            'growth_session' => $this->growthSessionPayload(),
            'initiator' => $this->whenLoaded('initiatedBy', fn() => [
                'id' => $this->initiatedBy->id,
                'name' => $this->initiatedBy->name,
                'avatar' => $this->initiatedBy->avatar,
            ]),
            'created_at' => $this->created_at,
        ];
    }

    /**
     * The growth session as it was when this happened, never as it is now.
     *
     * A notification is a record of a moment: reading the live row would have it quietly restate
     * itself every time somebody edited the session again, so a notification saying who moved the
     * time would end up quoting a time that person never set. The snapshot is written at dispatch,
     * before the queue runs, and is the only source for what the session said.
     *
     * The id is the exception, because it is not something the session said - it is how the reader
     * gets to it. It comes from the relation and is null once there is nothing left to open.
     */
    private function growthSessionPayload(): ?array
    {
        $snapshot = $this->metadata ?: [];

        if ($snapshot === []) {
            return null;
        }

        return [
            'id' => $this->growthSession?->id,
            'title' => $snapshot['title'] ?? null,
            'location' => $snapshot['location'] ?? null,
            'date' => $snapshot['date'] ?? null,
            'start_time' => $snapshot['start_time'] ?? null,
            'end_time' => $snapshot['end_time'] ?? null,
        ];
    }
}
