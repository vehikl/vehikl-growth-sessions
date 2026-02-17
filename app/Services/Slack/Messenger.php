<?php

namespace App\Services\Slack;

use App\Services\Slack\Responses\Message;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Messenger
{
    public function isConfigured(): bool
    {
        return config('services.slack.chat.token') ?? false;
    }

    /**
     * @throws ConnectionException
     */
    public function sendMessage(array $blocks, ?string $thread_ts = null): ?Message
    {
        if (!$this->isConfigured()) {
            return new Message(['ok' => false, 'error' => 'Not configured']);
        }

        $data = [
            'channel' => config('services.slack.chat.channel'),
            'blocks' => $blocks,
        ];

        if ($thread_ts) {
            $data['thread_ts'] = $thread_ts;
        }

        return Message::fromResponse(
            $this->postJson('https://slack.com/api/chat.postMessage', $data)
        );
    }

    /**
     * @throws ConnectionException
     */
    public function updateMessage(string $message_ts, array $blocks): ?Message
    {
        if (!$this->isConfigured()) {
            return new Message(['ok' => false, 'error' => 'Not configured']);
        }

        return Message::fromResponse(
            $this->postJson('https://slack.com/api/chat.update', [
                'channel' => config('services.slack.chat.channel'),
                'ts' => $message_ts,
                'blocks' => $blocks,
            ])
        );
    }

    public function deleteMessage(string $message_ts): bool
    {
        $response = $this->postJson('https://slack.com/api/chat.delete', [
            'channel' => config('services.slack.chat.channel'),
            'ts' => $message_ts,
        ]);
        return $response->ok() && $response->json('ok');
    }

    /**
     * @throws ConnectionException
     */
    protected function postJson(string $uri, array $body): Response
    {
        Log::debug('Messenger.postJson.request ' . $uri, $body);

        $response = Http::acceptJson()
            ->contentType('application/json; charset=utf-8')
            ->withToken(config('services.slack.chat.token'))
            ->post($uri, $body);

        Log::debug('Messenger.postJson.response ' . $uri, $response->json());

        return $response;
    }
}
