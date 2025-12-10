<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Response as StatusCode;

class VehiklMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()->is_vehikl_member) {
            abort(StatusCode::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
