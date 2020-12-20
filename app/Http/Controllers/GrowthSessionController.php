<?php

namespace App\Http\Controllers;

use App\Events\SocialMobAttendeeChanged;
use App\Events\GrowthSessionCreated;
use App\Events\GrowthSessionDeleted;
use App\Events\SocialMobUpdated;
use App\Exceptions\AttendeeLimitReached;
use App\Http\Requests\DeleteGrowthSessionRequest;
use App\Http\Requests\StoreSocialMobRequest;
use App\Http\Requests\UpdateSocialMobRequest;
use App\Http\Resources\SocialMob as SocialMobResource;
use App\Http\Resources\SocialMobWeek;
use App\SocialMob as GrowthSession;
use Illuminate\Http\Request;

class GrowthSessionController extends Controller
{
    public function show(GrowthSession $socialMob)
    {
        return view('social-mob', ['socialMob' => new SocialMobResource($socialMob)]);
    }

    public function week(Request $request)
    {
        return new SocialMobWeek(GrowthSession ::allInTheWeekOf($request->input('date')));
    }

    public function day()
    {
        return SocialMobResource::collection(GrowthSession ::today()->get());
    }

    public function store(StoreSocialMobRequest $request)
    {
        $newMob = $request->user()->socialMobs()->save(new GrowthSession ($request->validated()));
        $newMob->load(['owner', 'attendees', 'comments']);
        event(new GrowthSessionCreated($newMob));

        return $newMob;
    }

    public function join(GrowthSession $socialMob, Request $request)
    {
        if ($socialMob->attendees()->count() === $socialMob->attendee_limit) {
            throw new AttendeeLimitReached;
        }

        $socialMob->attendees()->attach($request->user());
        event(new SocialMobAttendeeChanged($socialMob->refresh()));

        return $socialMob;
    }

    public function leave(GrowthSession $socialMob, Request $request)
    {
        $socialMob->attendees()->detach($request->user());
        event(new SocialMobAttendeeChanged($socialMob->refresh()));

        return $socialMob;
    }

    public function edit(GrowthSession $socialMob)
    {
        return view('social-mob-edit', compact('socialMob'));
    }

    public function update(UpdateSocialMobRequest $request, GrowthSession $socialMob)
    {
        $originalValues = $socialMob->toArray();
        $socialMob->update($request->validated());
        event(new SocialMobUpdated($originalValues, $socialMob->toArray()));

        return $socialMob;
    }

    public function destroy(DeleteGrowthSessionRequest $request, GrowthSession $socialMob)
    {
        $socialMob->delete();
        event(new GrowthSessionDeleted($socialMob));
    }
}
