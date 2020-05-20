<?php

namespace App\Http\Controllers;

use App\Http\Requests\JoinSocialMobRequest;
use App\SocialMob;
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
        $response = [
            'monday' => [],
            'tuesday' => [],
            'wednesday' => [],
            'thursday' => [],
            'friday' => []
        ];
        foreach ($weekMobs as $mob) {
            array_push($response[$mob->dayOfTheWeek()], $mob->toArray());
        }

        return $response;
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $request->user()->socialMobs()->save(new SocialMob($request->all()));
    }

    public function join(SocialMob $socialMob, JoinSocialMobRequest $request)
    {
        $socialMob->attendees()->attach($request->user());
    }

    public function leave(SocialMob $socialMob, Request $request)
    {
        $socialMob->attendees()->detach($request->user());
    }

    public function show(SocialMob $socialMob)
    {
        //
    }

    public function edit(SocialMob $socialMob)
    {
        //
    }

    public function update(Request $request, SocialMob $socialMob)
    {
        //
    }

    public function destroy(SocialMob $socialMob)
    {
        //
    }
}
