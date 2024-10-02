<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VehiklMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()->is_vehikl_member) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
