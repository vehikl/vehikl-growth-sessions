<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteSocialMobRequest;
use App\Http\Requests\JoinSocialMobRequest;
use App\Http\Requests\StoreSocialMobRequest;
use App\Http\Requests\UpdateSocialMobRequest;
use App\SocialMob;
use Illuminate\Http\Request;

class SocialMobController extends Controller
{
    public function index()
    {
        return SocialMob::all();
    }

    public function show(SocialMob $socialMob) {
        return view('social-mob', compact('socialMob'));
    }

    public function week(Request $request)
    {
        return SocialMob::allInTheWeekOf($request->input('date'));
    }

    public function day()
    {
        return SocialMob::today()->get();
    }

    public function store(StoreSocialMobRequest $request)
    {
        return $request->user()->socialMobs()->save(new SocialMob($request->validated()));
    }

    public function join(SocialMob $socialMob, JoinSocialMobRequest $request)
    {
        $socialMob->attendees()->attach($request->user());
        return $socialMob->fresh();
    }

    public function leave(SocialMob $socialMob, Request $request)
    {
        $socialMob->attendees()->detach($request->user());
        return $socialMob->fresh();
    }

    public function edit(SocialMob $socialMob)
    {
        return view('social-mob-edit', compact('socialMob'));
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
