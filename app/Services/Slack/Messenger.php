<?php

namespace App\Services\Slack;

use App\Services\Slack\Responses\Message;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

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
            Http::acceptJson()
                ->contentType('application/json; charset=utf-8')
                ->withToken(config('services.slack.chat.token'))
                ->post('https://slack.com/api/chat.postMessage', $data)
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

        $body = [
            'channel' => config('services.slack.chat.channel'),
            'ts' => $message_ts,
            'blocks' => $blocks,
        ];

        info('Messenger.updateMessage', $body);

        return Message::fromResponse(
            Http::acceptJson()
                ->contentType('application/json; charset=utf-8')
                ->withToken(config('services.slack.chat.token'))
                ->post('https://slack.com/api/chat.update', $body)
        );
    }
}
