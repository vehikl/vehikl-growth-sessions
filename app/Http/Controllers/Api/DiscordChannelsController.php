<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DiscordChannelCollection;
use App\Services\Discord\DiscordService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DiscordChannelsController extends Controller
{
    public function index(DiscordService $discord): DiscordChannelCollection
    {
        return new DiscordChannelCollection($discord->getChannels());
    }

    public function occupied(string $date, DiscordService $discord): DiscordChannelCollection
    {
        return new DiscordChannelCollection($discord->getOccupiedChannels($date));
    }
}
