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

        if ($attributes['attendee_limit'] === \App\SocialMob::NO_LIMIT) {
            $attributes['attendee_limit'] = null;
        }

        return $attributes;
    }

}
