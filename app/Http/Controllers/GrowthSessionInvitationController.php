<?php

namespace App\Http\Controllers;

use App\Models\GrowthSession;
use App\Support\GrowthSessionUnlocks;
use Illuminate\Http\RedirectResponse;

class GrowthSessionInvitationController extends Controller
{
    /**
     * Records the unlocking and redirects to the board, deep-linked to the growth session so its detail drawer
     * opens in context. Redirecting also keeps the share token to a single entry in the visitor's history, and
     * out of the referrer of anything they click next.
     */
    public function __invoke(string $token): RedirectResponse
    {
        // An unknown token is a 404 rather than a 403, so that a growth session's
        // existence cannot be probed through this route.
        $growthSession = GrowthSession::query()->where('share_token', $token)->firstOrFail();

        GrowthSessionUnlocks::unlock($growthSession->share_token);

        return redirect()->route('home', [
            'date' => $growthSession->date->toDateString(),
            'session' => $growthSession->id,
        ]);
    }
}
