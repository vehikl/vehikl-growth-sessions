<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A User resource with a guest's identity redacted - used wherever a guest is shown to a viewer
 * who isn't allowed to see who they are (see GrowthSession::usersArray()).
 */
class GuestSafeUser extends JsonResource
{
    public function toArray($request): array
    {
        return [
            ...(new User($this->resource))->toArray($request),
            'name' => 'Guest',
            'avatar' => asset('images/guest-avatar.webp'),
            'github_nickname' => '',
        ];
    }
}
