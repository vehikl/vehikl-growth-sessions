<?php

namespace App\Http\Controllers\DevMode;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Authentication
{
    public function __invoke(Request $request, string $token)
    {
        $userId = Cache::pull("dev-mode:auth-token:{$token}");
        if (is_null($userId)) {
            abort(403);
        }

        $user = User::findOrFail($userId);
        Auth::login($user);

        return response()->redirectTo(route('dashboard'));
    }

}
