<?php

namespace App\Http\Controllers\Api;

use App\Events\GrowthSessionAttendeeChanged;
use App\Events\GrowthSessionCreated;
use App\Events\GrowthSessionDeleted;
use App\Events\GrowthSessionUpdated;
use App\Exceptions\AttendeeLimitReached;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteGrowthSessionRequest;
use App\Http\Requests\StoreGrowthSessionRequest;
use App\Http\Requests\UpdateGrowthSessionRequest;
use App\Http\Resources\DiscordChannelCollection;
use App\Http\Resources\GrowthSession as GrowthSessionResource;
use App\Http\Resources\GrowthSessionWeek;
use App\GrowthSession;
use App\Services\Discord\DiscordService;
use App\Services\Discord\Models\Channel;
use Illuminate\Http\Request;

class DiscordChannelsController extends Controller
{
    public function index(DiscordService $discord): DiscordChannelCollection
    {
        return new DiscordChannelCollection($discord->getChannels());
    }
}
