<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HideSensitiveInformationFromGuests
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! $request->expectsJson()) {
            return $response;
        }

        $content = $response->getOriginalContent();
        if (! $request->user()) {
            array_walk_recursive ($content, function(&$entry, $key) {
               if($key === 'location') {
                   $entry = '???';
               }
            });
        }
        $response->setContent(json_encode($content));

        return $response;
    }
}
