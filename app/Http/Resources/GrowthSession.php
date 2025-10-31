<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class GrowthSession extends JsonResource
{
    public function toArray($request): array
    {
        $attributes = parent::toArray($request);

        $user = $request->user();

        $isParticipatingInGrowthSession = $user &&
            ($this->resource->hasAttendee($user) || $this->resource->hasWatcher($user));

        $isSlackbot = $user?->github_nickname === config('auth.slack_app_name');
        $isOwner = $user && $user->is($this->resource->owner);

        if (!$isSlackbot && !$isParticipatingInGrowthSession && !$isOwner) {
            $attributes['location'] = '< Join to see location >';
        }

        if ($attributes['attendee_limit'] === \App\Models\GrowthSession::NO_LIMIT) {
            $attributes['attendee_limit'] = null;
        }

        $attributes['attendees'] = $attributes['attendees'] ?? [];
        $isPersonNotAVehiklMember = auth()->guest() || !auth()->user()->is_vehikl_member;

        if ($isPersonNotAVehiklMember && !$isOwner) {
            $attributes['anydesk'] = null;
        }

        if ($isPersonNotAVehiklMember && !$isOwner) {
            $attributes = $this->hideGuestInformationFromPayload('attendees', $attributes, $user);
            $attributes = $this->hideGuestInformationFromPayload('watchers', $attributes, $user);
        }

        return $attributes;
    }

    protected function hideGuestInformationFromPayload($key, $payload, $user): array
    {
        if (!Arr::has($payload, $key)) {
            return $payload;
        }

        for ($i = 0; $i < count($payload[$key]); $i++) {
            $currentAttendee = $payload[$key][$i];
            $isThisAttendeeAGuest = !$currentAttendee['is_vehikl_member'];
            $isThisAttendeeTheRequesterThemselves = (optional($user)->id === $currentAttendee['id']);

            if ($isThisAttendeeAGuest && !$isThisAttendeeTheRequesterThemselves) {
                $payload[$key][$i]['name'] = 'Guest';
                $payload[$key][$i]['avatar'] = asset('images/guest-avatar.webp');
                $payload[$key][$i]['github_nickname'] = '';
            }
        }
        return $payload;
    }

}
