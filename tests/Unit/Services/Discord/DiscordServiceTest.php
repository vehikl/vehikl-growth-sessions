<?php

namespace Tests\Unit\Services\Discord;

use App\Services\Discord\DiscordService;
use GuzzleHttp\Client;
use Tests\TestCase;

class DiscordServiceTest extends TestCase
{
    public function testItCanBeInstantiatedWithAGuzzleClient(): void
    {
        $guzzleClient = new Client();
        $discord = new DiscordService($guzzleClient);

        $this->assertInstanceOf(DiscordService::class, $discord);
        $this->assertEquals($guzzleClient, $discord->getHttp());
    }

    public function testItCanBePulledFromTheApplicationContainer(): void
    {
        /** @var DiscordService $discord */
        $discord = $this->app->get(DiscordService::class);

        $this->assertInstanceOf(DiscordService::class, $discord);
        $this->assertInstanceOf(Client::class, $discord->getHttp());
    }
}
