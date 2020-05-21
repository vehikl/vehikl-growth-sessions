<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteSocialMobRequest;
use App\Http\Requests\JoinSocialMobRequest;
use App\Http\Requests\UpdateSocialMobRequest;
use App\SocialMob;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SocialMobController extends Controller
{
    public function index(Request $request)
    {
        $filter = Str::lower($request->input('filter'));
        if ($filter === 'week') {

        }

        return SocialMob::all();
    }

    public function week(Request $request)
    {
        /** @var Collection $weekMobs */
        $weekMobs = SocialMob::query()->thisWeek()->orderBy('start_time')->get();
        $startPoint = $startPoint = now()->isDayOfWeek(Carbon::MONDAY) ? Carbon::today() : Carbon::parse('Last Monday');
        $startPoint = $startPoint->toImmutable();

        $response = [
            $startPoint->toDateString() => [],
            $startPoint->addDays(1)->toDateString() => [],
            $startPoint->addDays(2)->toDateString() => [],
            $startPoint->addDays(3)->toDateString() => [],
            $startPoint->addDays(4)->toDateString() => []
        ];
        foreach ($weekMobs as $mob) {
            $mobDate = Carbon::parse($mob->start_time)->toDateString();
            array_push($response[$mobDate], $mob->toArray());
        }

        return $response;
    }

    public function store(Request $request)
    {
        return $request->user()->socialMobs()->save(new SocialMob($request->all()));
    }

    public function join(SocialMob $socialMob, JoinSocialMobRequest $request)
    {
        $socialMob->attendees()->attach($request->user());
    }

    public function leave(SocialMob $socialMob, Request $request)
    {
        $socialMob->attendees()->detach($request->user());
    }

    public function update(UpdateSocialMobRequest $request, SocialMob $socialMob)
    {
        return $socialMob->update($request->validated());
    }

    public function destroy(DeleteSocialMobRequest $request, SocialMob $socialMob)
    {
        $socialMob->delete();
    }
}
