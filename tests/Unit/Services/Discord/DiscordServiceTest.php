<?php

namespace Tests\Unit\Services\Discord;

use App\Services\Discord\DiscordService;
use GuzzleHttp\Client;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\Traits\MocksGuzzleHistory;

class DiscordServiceTest extends TestCase
{
    use MocksGuzzleHistory;

    private string $fakeDiscordGuildId = 'geralt_of_rivia';

    public function setUp(): void
    {
        parent::setUp();
        $this->mockGuzzleHistory($this->guzzleHistory);
        config(['services.discord.guild_id' => $this->fakeDiscordGuildId]);
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
        $this->appendJsonResponse(Response::HTTP_OK, '{}');
        $discord = new DiscordService($this->getGuzzleClient());

        $discord->getChannels();

        $this->assertGuzzleHistoryContains("https://discord.com/api/guilds/$this->fakeDiscordGuildId/channels");
    }

    public function testItReturnsACollectionOfAllDiscordGuildVoiceChannels(): void
    {
        $channelsFixture = $this->loadJsonFixture('Discord/Channels');
        $this->appendJsonResponse(Response::HTTP_OK, json_encode($channelsFixture));
        $discord = new DiscordService($this->getGuzzleClient());

        $channels = $discord->getChannels();

        $this->assertInstanceOf(Collection::class, $channels);
        $this->assertEquals(sizeof($channelsFixture), $channels->count());
        collect($channelsFixture)->each(function ($channelFixture) use ($channels) {
            $this->assertTrue($channels->pluck('id')->contains($channelFixture->id));
        });
    }
}
