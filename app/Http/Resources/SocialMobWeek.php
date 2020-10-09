<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SocialMobWeek extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return collect($this->resource)->mapWithKeys(function ($week, $key) use ($request) {
            $hideLocation = ! $request->user();
            return [$key => collect($week)->map(function ($mob) use ($hideLocation) {
                $mob['attendee_limit'] = $mob['attendee_limit'] == \App\SocialMob::NO_LIMIT ? null : $mob['attendee_limit'];

                if ($hideLocation) {
                    $mob['location'] = '< Login to see the location >';
                }

                return $mob;
            })->all()];
        })->all();
    }
}
