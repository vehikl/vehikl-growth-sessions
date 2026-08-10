<?php

namespace App\Http\Resources;

/**
 * The User resource is shared publicly (attendees, watchers, comment authors) and deliberately
 * omits email. The authenticated viewer is shown their own email in the UI, so this extends it
 * with that one field rather than reusing the raw model, which would also expose
 * email_verified_at/timestamps.
 */
class AuthenticatedUser extends User
{
    public function toArray($request): array
    {
        return [
            ...parent::toArray($request),
            'email' => $this->email,
        ];
    }
}
