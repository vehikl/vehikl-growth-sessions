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
        }

        for ($i = 0; $i < count($attributes['watchers']); $i++) {
            $isThisAttendeeAGuest = !$attributes['watchers'][$i]['is_vehikl_member'];

            $shouldHideGuestsInformation = $isThisAttendeeAGuest && $isPersonNotAVehiklMember;

            if ($shouldHideGuestsInformation) {
                $attributes['watchers'][$i]['name'] = 'Guest';
                $attributes['watchers'][$i]['avatar'] = asset('images/guest-avatar.webp');
                $attributes['watchers'][$i]['github_nickname'] = '';
            }
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
