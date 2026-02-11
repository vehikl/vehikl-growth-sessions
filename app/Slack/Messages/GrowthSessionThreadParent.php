<?php

namespace App\Slack\Messages;

use App\Models\GrowthSession;
use App\Services\LocationUrls;
use Carbon\Carbon;
use SlackPhp\BlockKit\Blocks\Block;
use SlackPhp\BlockKit\Blocks\Header;
use SlackPhp\BlockKit\Blocks\Section;
use SlackPhp\BlockKit\Elements\Button;
use SlackPhp\BlockKit\Elements\Image;
use SlackPhp\BlockKit\Surfaces\Message;

class GrowthSessionThreadParent implements MessageInterface
{
    /**
     * @return array{Block}
     */
    public function build(GrowthSession $growthSession): array
    {
        $links = app(LocationUrls::class)->get($growthSession);

        $startHour = Carbon::parse($growthSession->start_time)->hour % 12;
        $clockEmoji = ":clock{$startHour}:";

        $topicMarkdown = collect(explode(PHP_EOL, $growthSession->topic))
            ->map(fn(string $line) => "> $line")
            ->join(PHP_EOL);

        $blocks = [
            new Header(text: $growthSession->title),
            new Section(text: sprintf(<<<DETAILS
:bust_in_silhouette: %s
{$clockEmoji} %s - %s
:busts_in_silhouette: %d/%d Attendees

{$topicMarkdown}
DETAILS,
                $growthSession->owner?->name ?? 'Anonymous',
                Carbon::parse($growthSession->start_time)->toTimeString('minute'),
                Carbon::parse($growthSession->end_time)->toTimeString('minute'),
                $growthSession->attendees()->count(),
                $growthSession->attendee_limit
            ),
                accessory: $growthSession->owner?->avatar ? new Image($growthSession->owner->avatar, altText: $growthSession->owner->name) : null,
            ),
        ];

        if (count($links) > 0) {
            $blocks []= new Section(text: implode(PHP_EOL, $links));
        }

        return (new Message(
            blocks: [
                ...$blocks,
                new Section(
                    text: 'Interested?',
                    accessory: new Button(text: 'View session', value: route('growth_sessions.show', $growthSession->id))
                ),

            ]
        ))->toArray()['blocks'];
    }

}
