<?php

namespace Tests\Unit\Services\Discord;

use App\Services\Discord\DiscordService;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Tests\TestCase;
use Tests\Traits\MocksGuzzleHistory;

class DiscordServiceTest extends TestCase
{
    use MocksGuzzleHistory;

    public function setUp(): void
    {
        parent::setUp();
        $this->mockGuzzleHistory($this->guzzleHistory);
    }

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

    public function testItMakesAGetsRequestToDiscordForAllGuildVoiceChannels(): void
    {
        config(['services.discord.guild_id' => 'geralt_of_rivia']);
        $this->appendJsonResponse(Response::HTTP_OK, '{}');
        $discord = new DiscordService($this->getGuzzleClient());

        $discord->getChannels();

        $this->assertGuzzleHistoryContains('https://discord.com/api/guilds/geralt_of_rivia/channels');
    }
}
