<?php

namespace App\Http\Resources;

use App\Models\Tag;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A single row of the Dashboard's hosted Growth Sessions list.
 *
 * Deliberately assembled attribute by attribute rather than from `parent::toArray()`: the
 * Growth Session model appends an `owner` attribute whose accessor queries the owner
 * relationship every time the model is serialized, so serializing it wholesale would cost one
 * extra query per row to compute an owner this page already knows is the current user.
 *
 * Dates are formatted here rather than in the browser, so the list renders plain strings, and
 * neither the page nor its spec has to reach for moment-timezone. The row shows the day only:
 * the start time still orders the list server-side, but it is noise once a session is listed.
 */
class HostedGrowthSession extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'date' => $this->resource->date->toDateString(),
            'date_label' => $this->resource->date->format('M j, Y'),
            'is_upcoming' => $this->resource->date->gte(today()),
            'attendee_count' => (int) $this->resource->attendee_count,
            'tags' => $this->resource->tags
                ->map(fn (Tag $tag) => ['id' => $tag->id, 'name' => $tag->name])
                ->values()
                ->all(),
        ];
    }
}
