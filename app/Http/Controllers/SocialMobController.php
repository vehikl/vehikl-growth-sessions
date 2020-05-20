<?php

namespace App\Http\Controllers;

use App\SocialMob;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SocialMobController extends Controller
{
    public function index(Request $request)
    {
        $filter = Str::lower($request->input('filter'));
        if ($filter === 'week') {
            return SocialMob::query()->thisWeek()->get();
        }

        return SocialMob::all();
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $request->user()->socialMobs()->save(new SocialMob($request->all()));
    }

    public function join(SocialMob $socialMob, Request $request)
    {
        $socialMob->attendees()->attach($request->user());
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
