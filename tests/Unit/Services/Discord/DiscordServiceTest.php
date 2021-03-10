<?php

namespace Tests\Unit\Services\Discord;

use App\Services\Discord\DiscordService;
use App\Services\Discord\Models\Channel;
use GuzzleHttp\Client;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\MocksGuzzleHistory;

class DiscordServiceTest extends TestCase
{
    private string $fakeDiscordGuildId = 'geralt_of_rivia';

    public function setUp(): void
    {
        parent::setUp();
        config(['services.discord.guild_id' => $this->fakeDiscordGuildId]);
    }

    public function testItCanBePulledFromTheApplicationContainer(): void
    {
        /** @var DiscordService $discord */
        $discord = $this->app->get(DiscordService::class);

        $this->assertInstanceOf(DiscordService::class, $discord);
    }

    public function testItMakesAGetsRequestToDiscordForAllGuildVoiceChannels(): void
    {
        Http::fake();

        $discord = new DiscordService();

        $discord->getChannels();

        Http::assertSent(function(Request $request) {
            return $request->url() === "https://discord.com/api/guilds/$this->fakeDiscordGuildId/channels";
        });
    }

    public function testItReturnsACollectionOfAllDiscordGuildVoiceChannels(): void
    {
        $channelsFixture = $this->loadJsonFixture('Discord/Channels', true);
        Http::fake([
            '*' => Http::response($channelsFixture, Response::HTTP_OK)
        ]);

        $discord = new DiscordService();

        $channels = $discord->getChannels();

        $this->assertInstanceOf(Collection::class, $channels);
        $this->assertEquals(sizeof($channelsFixture), $channels->count());
        collect($channelsFixture)->each(function ($channelFixture) use ($channels) {
            $this->assertTrue($channels->pluck('id')->contains($channelFixture['id']));
        });
        $channels->each(function ($channel) {
            $this->assertInstanceOf(Channel::class, $channel);
        });
    }
}
