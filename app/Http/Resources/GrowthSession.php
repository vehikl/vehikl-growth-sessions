<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class GrowthSession extends JsonResource
{
    public function toArray($request)
    {
        $attributes = parent::toArray($request);

        $user = $request->user();

        $isParticipatingInGrowthSession = $user &&
            ($this->resource->hasAttendee($user) || $this->resource->hasWatcher($user));

        $isSlackbot = $user && $user->email === config('auth.slack_app_email');

        if (!$isSlackbot && !$isParticipatingInGrowthSession) {
            $attributes['location'] = '< Join Growth Session to see the location >';
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
        if (!Arr::has($payload, $key)) {
            return $payload;
        }

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
