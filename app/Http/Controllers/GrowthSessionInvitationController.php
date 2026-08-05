<?php

namespace App\Http\Controllers;

use App\Models\GrowthSession;
use App\Support\GrowthSessionUnlocks;
use Illuminate\Http\RedirectResponse;

class GrowthSessionInvitationController extends Controller
{
    /**
     * Records the unlocking and redirects to the growth session's canonical page, so that the share token
     * appears once in the visitor's history and is never carried into the referrer of anything they click next.
     */
    public function __invoke(string $token): RedirectResponse
    {
        // An unknown token is a 404 rather than a 403, so that a growth session's
        // existence cannot be probed through this route.
        $growthSession = GrowthSession::query()->where('share_token', $token)->firstOrFail();

        GrowthSessionUnlocks::unlock($growthSession->share_token);

        return redirect()->route('growth_sessions.show', $growthSession);
    }
}
