<?php

namespace App\Services\Zoom;

use App\GrowthSession;
use Illuminate\Support\Facades\Http;

class ZoomService
{
    const API_BASE_URL = 'https://api.zoom.us/v2';

    private $api_key;
    private $api_secret;

    public function __construct()
    {
        $this->api_key = config('services.zoom.api_key');
        $this->api_secret = config('services.zoom.api_secret');
    }

    private function generateJwt(): string {
        /* TODO: Generate JWT using firebase/php-jwt.
         * See https://marketplace.zoom.us/docs/guides/auth/jwt
        */
        return '';
    }

    private function isConfigSet(): bool {
        return !empty($this->api_key) && !empty($this->api_secret);
    }

    public function createMeeting(GrowthSession $growthSession): ?int
    {
        if (! $this->isConfigSet()) {
            return null;
        }

        $jwt = $this->generateJwt();

        $response = Http::withToken($jwt)
            ->accept('application/json')
            ->post(self::API_BASE_URL . '/users/me/meetings', [
                'topic' => $growthSession['title'],
            ])
            ->json();

        return $response['id'];
    }

    public function deleteMeeting($meeting_id): void {
        if (! $this->isConfigSet()) {
            return;
        }

        $jwt = $this->generateJwt();

        Http::withToken($jwt)->delete(self::API_BASE_URL . '/meetings/' . $meeting_id);
    }
}
