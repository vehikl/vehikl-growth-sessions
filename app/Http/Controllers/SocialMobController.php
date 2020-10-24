<?php

namespace App\Http\Controllers;

use App\Events\SocialMobAttendeeChanged;
use App\Events\SocialMobCreated;
use App\Events\SocialMobDeleted;
use App\Events\SocialMobUpdated;
use App\Exceptions\AttendeeLimitReached;
use App\Http\Requests\DeleteSocialMobRequest;
use App\Http\Requests\StoreSocialMobRequest;
use App\Http\Requests\UpdateSocialMobRequest;
use App\Http\Resources\SocialMob as SocialMobResource;
use App\Http\Resources\SocialMobWeek;
use App\SocialMob;
use Illuminate\Http\Request;

class SocialMobController extends Controller
{
    public function show(SocialMob $socialMob)
    {
        return view('social-mob', ['socialMob' => new SocialMobResource($socialMob)]);
    }

    public function week(Request $request)
    {
        return new SocialMobWeek(SocialMob::allInTheWeekOf($request->input('date')));
    }

    public function day()
    {
        return SocialMobResource::collection(SocialMob::today()->get());
    }

    public function store(StoreSocialMobRequest $request)
    {
        $newMob = $request->user()->socialMobs()->save(new SocialMob($request->validated()));
        $newMob->load(['owner', 'attendees', 'comments']);
        event(new SocialMobCreated($newMob));

        return $newMob;
    }

    public function join(SocialMob $socialMob, Request $request)
    {
        if ($socialMob->attendees()->count() === $socialMob->attendee_limit) {
            throw new AttendeeLimitReached;
        }

        $socialMob->attendees()->attach($request->user());
        event(new SocialMobAttendeeChanged($socialMob->refresh()));

        return $socialMob;
    }

    public function leave(SocialMob $socialMob, Request $request)
    {
        $socialMob->attendees()->detach($request->user());
        event(new SocialMobAttendeeChanged($socialMob->refresh()));

        return $socialMob;
    }

    public function edit(SocialMob $socialMob)
    {
        return view('social-mob-edit', compact('socialMob'));
    }

    public function update(UpdateSocialMobRequest $request, SocialMob $socialMob)
    {
        $originalValues = $socialMob->toArray();
        $socialMob->update($request->validated());
        event(new SocialMobUpdated($originalValues, $socialMob->toArray()));

        return $socialMob;
    }

    public function destroy(DeleteSocialMobRequest $request, SocialMob $socialMob)
    {
        $socialMob->delete();
        event(new SocialMobDeleted($socialMob));
    }
}
