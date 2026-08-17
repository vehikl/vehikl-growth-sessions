<?php

namespace App\Http\Resources;

use App\Enums\NotificationType;
use App\Models\GrowthSession;
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
            'type' => $this->type,
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

    private function growthSessionPayload(): ?array
    {
        if ($this->type === NotificationType::GS_DELETED) {
            return $this->fromSnapshot();
        }

        return $this->growthSession ? $this->fromRelation($this->growthSession) : null;
    }

    private function fromSnapshot(): ?array
    {
        $metadata = $this->metadata ?: [];

        if ($metadata === []) {
            return null;
        }

        return [
            // The row is gone, so there is nothing to link to. A null id is the payload saying so.
            'id' => null,
            'title' => $metadata['title'] ?? null,
            'location' => $metadata['location'] ?? null,
            'date' => $metadata['date'] ?? null,
            'start_time' => $metadata['start_time'] ?? null,
            'end_time' => $metadata['end_time'] ?? null,
        ];
    }

    private function fromRelation(GrowthSession $growthSession): array
    {
        return [
            'id' => $growthSession->id,
            'title' => $growthSession->title,
            'location' => $growthSession->location,
            'date' => $growthSession->date?->format('Y-m-d'),
            'start_time' => $growthSession->start_time?->format('h:i a'),
            'end_time' => $growthSession->end_time?->format('h:i a'),
        ];
    }
}
