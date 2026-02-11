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

interface MessageInterface
{
    /**
     * @return array{Block}
     */
    public function build(GrowthSession $growthSession): array;
}
