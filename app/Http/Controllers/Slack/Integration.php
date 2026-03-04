<?php

namespace App\Http\Controllers\Slack;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class Integration
{
    public function interactivity()
    {
        // no-op for now
        return new JsonResponse(null, Response::HTTP_OK);
    }

}
