<?php

namespace Tests\Feature\Api;

use App\Services\Discord\DiscordService;
use App\Services\Discord\Models\Channel;
use App\User;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class DiscordChannelsTest extends TestCase
{
    private MockObject $mockedDiscord;

    public function setUp(): void
    {
        parent::setUp();
        $this->mockedDiscord = $this->createMock(DiscordService::class);
        $this->app->bind(DiscordService::class, fn() => $this->mockedDiscord);
    }

    public function testItReturnsAllDiscordChannels(): void
    {
        $fakeChannels = collect([
            new Channel('1234567890', 'Channel One'),
            new Channel('1234567891', 'Channel Two'),
            new Channel('1234567892', 'Channel Three'),
        ]);

        $this->mockedDiscord->method('getChannels')
            ->willReturn($fakeChannels);

        $response = $this->actingAs(User::factory()->create())->get('/api/discord-channels');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                ],
            ],
        ]);
        $responseContents = json_decode($response->getContent());
        $this->assertCount(3, $responseContents->data);
        $fakeChannels->each(function (Channel $channel) use ($responseContents) {
            $this->assertContains($channel->id, collect($responseContents->data)->pluck('id')->toArray());
        });
    }

    public function testItRedirectsIfUserIsNotLoggedIn(): void
    {
        $this->get('/api/discord-channels')->assertRedirect('/');
    }
}
