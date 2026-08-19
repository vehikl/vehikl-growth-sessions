<?php

namespace App\Http\Resources;

use App\Models\GrowthSession as GrowthSessionModel;
use App\Models\User as UserModel;
use App\Support\InviteLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class GrowthSession extends JsonResource
{
    private const HIDDEN_LOCATION = '< Join to see location >';

    public function toArray($request): array
    {
        /** @var GrowthSessionModel $growthSession */
        $growthSession = $this->resource;
        $viewer = $request->user();

        $isParticipating = $viewer && $growthSession->hasParticipant($viewer);
        $isOwner = $viewer && $viewer->is($growthSession->owner);
        $canSeeSensitiveInfo = $isOwner || ($viewer && $viewer->is_vehikl_member);
        $canSeeLocation = $isOwner || $isParticipating;

        $inviteLink = InviteLink::for($growthSession);

        return [
            'id' => $growthSession->id,
            'title' => $growthSession->title,
            'topic' => $growthSession->topic,
            'topic_segments' => $growthSession->topic_segments,
            'location' => $canSeeLocation ? $growthSession->location : self::HIDDEN_LOCATION,
            'location_segments' => $canSeeLocation
                ? $growthSession->location_segments
                : [['type' => 'text', 'value' => self::HIDDEN_LOCATION]],
            // ->date/->start_time/->end_time are Carbon instances (the model's `datetime:FORMAT` casts only
            // apply their format string during Eloquent's own toArray()/toJson(), not on direct property
            // access), so they're formatted explicitly here to match those casts.
            'date' => $growthSession->date->format('Y-m-d'),
            'start_time' => $growthSession->start_time->format('h:i a'),
            'end_time' => $growthSession->end_time->format('h:i a'),
            'is_public' => $growthSession->is_public,
            'allow_watchers' => $growthSession->allow_watchers,
            'attendee_limit' => $growthSession->attendee_limit === GrowthSessionModel::NO_LIMIT
                ? null
                : $growthSession->attendee_limit,
            'discord_channel_id' => $growthSession->discord_channel_id,
            'slack_thread_ts' => $growthSession->slack_thread_ts,
            'owner' => $growthSession->owner ? new User($growthSession->owner) : null,
            'attendees' => $this->usersArray($growthSession->attendees, $viewer, $canSeeSensitiveInfo, $request),
            'watchers' => $this->usersArray($growthSession->watchers, $viewer, $canSeeSensitiveInfo, $request),
            'comments' => Comment::collection($growthSession->comments),
            'anydesk' => $canSeeSensitiveInfo ? $growthSession->anydesk : null,
            'tags' => Tag::collection($growthSession->tags),
            // Whether the growth session is unlisted, not the token that unlocks it: the board needs to tell an
            // invite-only session apart from a private one to keep showing it to an invited guest.
            'is_unlisted' => $inviteLink->exists(),
            'share_url' => $this->when($inviteLink->isVisibleTo($viewer), fn () => $inviteLink->url()),
        ];
    }

    /**
     * Redacts a guest's identity from every viewer except the guest themselves - unlike location/anydesk,
     * this is a per-user decision (a viewer can always see their own info) rather than an all-or-nothing gate.
     */
    private function usersArray(Collection $users, ?UserModel $viewer, bool $canSeeSensitiveInfo, Request $request): array
    {
        return $users
            ->map(function (UserModel $user) use ($viewer, $canSeeSensitiveInfo, $request) {
                $isGuest = ! $user->is_vehikl_member;
                $isViewingSelf = $viewer && $viewer->is($user);

                $resource = $canSeeSensitiveInfo || ! $isGuest || $isViewingSelf
                    ? new User($user)
                    : new GuestSafeUser($user);

                return $resource->toArray($request);
            })
            ->all();
    }
}
