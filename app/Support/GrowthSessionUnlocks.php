<?php

namespace App\Support;

use Illuminate\Support\Facades\Session;

/**
 * Remembers, for the lifetime of a visitor's browser session, which growth session invite links they have opened.
 *
 * Unlocks are stored as the share token *value* rather than the growth session's identifier, so that revoking or
 * regenerating a token takes effect immediately for everyone still browsing.
 */
class GrowthSessionUnlocks
{
    public const SESSION_KEY = 'unlocked_growth_session_tokens';

    public static function unlock(string $shareToken): void
    {
        $unlocked = Session::get(self::SESSION_KEY, []);

        if (! in_array($shareToken, $unlocked, true)) {
            $unlocked[] = $shareToken;
            Session::put(self::SESSION_KEY, $unlocked);
        }
    }

    public static function has(?string $shareToken): bool
    {
        if (blank($shareToken)) {
            return false;
        }

        return in_array($shareToken, Session::get(self::SESSION_KEY, []), true);
    }
}
