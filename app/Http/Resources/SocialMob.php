<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SocialMob extends JsonResource
{
    public function toArray($request)
    {
        $attributes = parent::toArray($request);
        if (! $request->user()) {
            return $this->hideLocation($attributes);
        }
        return $attributes;
    }

    public function hideLocation(array $payload)
    {
        array_walk_recursive ($payload, function(&$entry, $key) {
            if($key === 'location') {
                $entry = '???';
            }
        });

        return $payload;
    }
}
