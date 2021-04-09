<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GrowthSession extends JsonResource
{
    public function toArray($request)
    {
        $attributes = parent::toArray($request);

        if (! $request->user()) {
            $attributes['location'] = '< Login to see the location >';
        }

        if ($attributes['attendee_limit'] === \App\GrowthSession::NO_LIMIT) {
            $attributes['attendee_limit'] = null;
        }

        for ($i = 0; $i < count($attributes['attendees'] ?? []); $i++) {
            if (! $attributes['attendees'][$i]['is_vehikl_member']) {
                $attributes['attendees'][$i]['name'] = 'Guest';
            }
        }

        return $attributes;
    }

}
