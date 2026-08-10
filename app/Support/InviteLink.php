<?php

namespace App\Support;

use App\Models\GrowthSession;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * The shareable invitation to a growth session, and everything that follows from holding one.
 *
 * Owning the token in one place keeps its two rules together: a public growth session never has an invite link — it
 * is already visible to everyone — and a link that has been handed out is never silently replaced.
 *
 * Callers say what the link should be, not how to get there; issuing and revoking only write to the model, so
 * persisting stays with whoever called.
 */
class InviteLink
{
    private const TOKEN_LENGTH = 40;

    private function __construct(private readonly GrowthSession $growthSession)
    {
    }

    public static function for(GrowthSession $growthSession): self
    {
        return new self($growthSession);
    }

    public function exists(): bool
    {
        return filled($this->growthSession->share_token);
    }

    public function url(): ?string
    {
        return $this->exists()
            ? route('growth_sessions.invitation', ['token' => $this->growthSession->share_token])
            : null;
    }

    /**
     * The one way in: whether the growth session should have a link, reconciled against how visible it now is. A
     * public growth session ends up without one either way — including when it has just been made public with a
     * link already handed out.
     */
    public function set(bool $shouldExist): void
    {
        $shouldExist && ! $this->growthSession->is_public
            ? $this->issue()
            : $this->revoke();
    }

    /**
     * The URL goes to the owner alone. Handing out an unlisted growth session is theirs to control, so attending it —
     * or merely being a colleague — grants no sight of the link, and nobody can widen the audience on their behalf.
     */
    public function isVisibleTo(?User $user): bool
    {
        if (! $this->exists() || ! $user) {
            return false;
        }

        return $user->is($this->growthSession->owner);
    }

    public function hasBeenUnlocked(): bool
    {
        return $this->exists() && GrowthSessionUnlocks::has($this->growthSession->share_token);
    }

    /**
     * Idempotent: a link already handed out keeps working, so the same URL stays valid for everyone holding it.
     */
    private function issue(): void
    {
        if (! $this->exists()) {
            $this->growthSession->share_token = Str::random(self::TOKEN_LENGTH);
        }
    }

    private function revoke(): void
    {
        $this->growthSession->share_token = null;
    }
}
