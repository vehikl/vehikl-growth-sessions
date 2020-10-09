<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SocialMob extends JsonResource
{
    public function toArray($request)
    {
        $attributes = parent::toArray($request);
        if (! $request->user()) {
            $attributes = $this->hideLocation($attributes);
        }

        if ($this->isSingleResource() && $attributes['attendee_limit'] === \App\SocialMob::NO_LIMIT) {
            $attributes['attendee_limit'] = null;
        }

        return $attributes;
    }

    public function hideLocation(array $payload)
    {
        array_walk_recursive ($payload, function(&$entry, $key) {
            if($key === 'location') {
                $entry = '< Login to see the location >';
            }
        });

        return $payload;
    }

    private function isSingleResource()
    {
        return $this->resource instanceof \App\SocialMob;
    }
}
