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

        $attributes['attendees'] = $attributes['attendees'] ?? [];
        $isPersonNotAVehiklMember = auth()->guest() || !auth()->user()->is_vehikl_member;

        if ($isPersonNotAVehiklMember) {
            $attributes['anydesk'] = null;
        }

        if ($isPersonNotAVehiklMember) {
            $attributes = $this->hideGuestInformationFromPayload('attendees', $attributes);
            $attributes = $this->hideGuestInformationFromPayload('watchers', $attributes);
        }

        return $attributes;
    }

    protected function hideGuestInformationFromPayload($key, $payload): array
    {
        for ($i = 0; $i < count($payload[$key]); $i++) {
            $isThisAttendeeAGuest = !$payload[$key][$i]['is_vehikl_member'];

            if ($isThisAttendeeAGuest) {
                $payload[$key][$i]['name'] = 'Guest';
                $payload[$key][$i]['avatar'] = asset('images/guest-avatar.webp');
                $payload[$key][$i]['github_nickname'] = '';
            }
        }
        return $payload;
    }

}
