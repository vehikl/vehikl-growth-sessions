<?php

namespace App\Slack\Messages;

use App\Models\GrowthSession;
use App\Models\User;
use App\Services\LocationUrls;
use Carbon\Carbon;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SlackPhp\BlockKit\Blocks\Actions;
use SlackPhp\BlockKit\Blocks\Block;
use SlackPhp\BlockKit\Blocks\Context;
use SlackPhp\BlockKit\Blocks\Divider;
use SlackPhp\BlockKit\Blocks\Header;
use SlackPhp\BlockKit\Blocks\Section;
use SlackPhp\BlockKit\Elements\Button;
use SlackPhp\BlockKit\Elements\Image;
use SlackPhp\BlockKit\Parts\MrkdwnText;
use SlackPhp\BlockKit\Parts\PlainText;
use SlackPhp\BlockKit\Surfaces\Message;

class GrowthSessionThreadParent implements MessageInterface
{
    /**
     * @param GrowthSession $growthSession
     * @return array{Block}
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function build(GrowthSession $growthSession): array
    {
        $links = app(LocationUrls::class)->get($growthSession);

        $startTime = Carbon::parse($growthSession->start_time);
        $endTime = Carbon::parse($growthSession->start_time);

        $locationLinks = collect($links)
            ->map(function (string $link) {
                $host = parse_url($link, PHP_URL_HOST);
                return "<{$link}|{$host}>";
            })
            ->join(" | ");
        $locationLinks = $locationLinks ?: $growthSession->location;

        $contextElements = $growthSession->attendees
            ->map(function (User $attendee) {
                return new Image(imageUrl: $attendee->avatar, altText: $attendee->name);
            })
            ->slice(0, 9);

        $trailingStrings = [];
        $andOthersCount = $growthSession->attendees()->count() - $contextElements->count();
        if ($andOthersCount > 0) {
            $trailingStrings [] = "and {$andOthersCount} others";
        }
        $trailingStrings [] = $growthSession->hasUnlimitedSlots()
            ? "{$growthSession->attendees()->count()} / :infinity: Attendees"
            : "{$growthSession->attendees()->count()} / {$growthSession->attendee_limit} Attendees";

        $contextElements->push(new PlainText(text: implode('. ', $trailingStrings), emoji: true));

        return (new Message(
            blocks: [
                new Header(text: $growthSession->title),
                new Section(text: $growthSession->topic),

                new Context(elements: $contextElements->toArray()),

                new Section(fields: [
                    new MrkdwnText("*:alarm_clock: Time*\n{$startTime->format('g:i a')} - {$endTime->format('g:i a')}"),
                    new MrkdwnText("*:round_pushpin: Location*\n{$locationLinks}"),
                ]),
                new Divider(),
                new Actions([
                    new Button(actionId: 'details', text: 'View Details', url: route('growth_sessions.show', $growthSession->id)),
                    new Button(actionId: 'join', text: 'Join', style: 'primary'),
                ])
            ],
        ))->toArray()['blocks'];
    }

}
