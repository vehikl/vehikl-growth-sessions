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

    public function getChannels()
    {
        return collect(
            json_decode($this->http->get(
                'https://discord.com/api/guilds/' . config('services.discord.guild_id') . '/channels',
                ['headers' => ['Authorization' => 'Bot ' . config('services.discord.bot_token')]]
            )->getBody()->getContents())
        );
    }
}
