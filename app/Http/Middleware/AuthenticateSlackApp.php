<?php

namespace App\Http\Middleware;

use App\User;
use Closure;
use Illuminate\Http\Request;

class AuthenticateSlackApp
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->header('Authorization') === 'Bearer '.config('auth.slack_token')) {
            auth()->login(User::query()->where('github_nickname', config('auth.slack_app_name'))->first());
        }
        return $next($request);
    }
}
