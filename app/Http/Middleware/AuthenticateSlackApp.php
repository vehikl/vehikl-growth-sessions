<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSlackApp
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('Authorization') === 'Bearer '.config('auth.slack_token')) {
            auth()->login(User::query()->where('github_nickname', config('auth.slack_app_name'))->first());
        }
        return $next($request);
    }
}
