<?php

namespace App\Services\Discord;

use GuzzleHttp\Client;

class DiscordService
{
    private Client $http;

    public function __construct(Client $http)
    {
        $this->http = $http;
    }

    public function getHttp(): Client
    {
        return $this->http;
    }
}
