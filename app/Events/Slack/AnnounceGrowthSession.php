<?php

namespace App\Events\Slack;

use App\Models\GrowthSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnnounceGrowthSession
{
    use Dispatchable, SerializesModels;

    public function __construct(public GrowthSession $growthSession)
    {
    }
}
