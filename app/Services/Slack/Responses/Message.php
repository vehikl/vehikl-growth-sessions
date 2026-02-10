<?php

namespace App\Services\Slack\Responses;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;

readonly class Message
{
    public bool $ok;
    public ?string $error;
    public string $channel;
    public ?string $ts;
    public string $text;
    public string $bot_id;
    public array $attachments;
    public string $type;
    public string $subtype;
    public ?string $message_ts;

    public static function fromResponse(Response $response): self
    {
        return new self($response->json());
    }

    public function __construct(public array $json)
    {
        $this->ok = Arr::get($json, 'ok', false);
        $this->error = Arr::get($json, 'error', null);
        $this->channel = Arr::get($json, 'channel', '');
        $this->ts = Arr::get($json, 'ts', null);
        $this->text = Arr::get($json, 'message.text', '');
        $this->bot_id = Arr::get($json, 'message.bot_id', '');
        $this->attachments = Arr::get($json, 'message.attachments', []);
        $this->type = Arr::get($json, 'message.type', '');
        $this->subtype = Arr::get($json, 'message.subtype', '');
        $this->message_ts = Arr::get($json, 'message.ts', null);
    }

    public function id(): ?string
    {
        return $this->message_ts ?? $this->ts ?? null;
    }
}
