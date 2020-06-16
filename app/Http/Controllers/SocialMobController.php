<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteSocialMobRequest;
use App\Http\Requests\JoinSocialMobRequest;
use App\Http\Requests\StoreSocialMobRequest;
use App\Http\Requests\UpdateSocialMobRequest;
use App\SocialMob;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SocialMobController extends Controller
{
    public function show(SocialMob $socialMob)
    {
        return view('social-mob', compact('socialMob'));
    }

    public function week(Request $request)
    {
        $weekMobs = SocialMob::allInTheWeekOf($request->input('date'));
        if ($request->user() == null) {
            $weekMobs = array_map(function($week) {
                return array_map('App\SocialMob::protectInformation', $week);
            }, $weekMobs);
        }
        return $weekMobs;
    }

    public function day()
    {
        return SocialMob::today()->get();
    }

    public function store(StoreSocialMobRequest $request)
    {
        $newMob = $request->user()->socialMobs()->save(new SocialMob($request->validated()));
        $newMob->load(['owner', 'attendees', 'comments']);
        $this->notifyCreationIfNeeded($newMob);
        return $newMob;
    }

    public function join(SocialMob $socialMob, JoinSocialMobRequest $request)
    {
        $socialMob->attendees()->attach($request->user());
        $this->notifyAttendeeChangeIfNeeded($socialMob->refresh());
        return $socialMob;
    }

    public function leave(SocialMob $socialMob, Request $request)
    {
        $socialMob->attendees()->detach($request->user());
        $this->notifyAttendeeChangeIfNeeded($socialMob->refresh());
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
        $this->notifyUpdateIfNeeded($originalValues, $socialMob->toArray());
        return $socialMob;
    }

    public function destroy(DeleteSocialMobRequest $request, SocialMob $socialMob)
    {
        $socialMob->delete();
        $this->notifyDeleteIfNeeded($socialMob);
    }

    private function notifyCreationIfNeeded(SocialMob $socialMob)
    {
        if ($this->isWithinWebHookNotificationWindow()
            && config('webhooks.created_today')
            && today()->isSameDay($socialMob->date)) {
            Http::post(config('webhooks.created_today'), $socialMob->toArray());
        }
    }

    private function notifyDeleteIfNeeded(SocialMob $socialMob)
    {
        if ($this->isWithinWebHookNotificationWindow()
            && config('webhooks.deleted_today')
            && today()->isSameDay($socialMob->date)) {
            Http::post(config('webhooks.deleted_today'), $socialMob->toArray());
        }
    }

    private function notifyUpdateIfNeeded(array $originalValues, array $newValues)
    {
        $wasMobOriginallyToday = today()->isSameDay($originalValues['date']);
        $wasMobMovedToToday = today()->isSameDay($newValues['date']);
        if ($this->isWithinWebHookNotificationWindow()
            && config('webhooks.updated_today')
            && ($wasMobOriginallyToday || $wasMobMovedToToday)) {
            Http::post(config('webhooks.updated_today'), $newValues);
        }
    }

    private function notifyAttendeeChangeIfNeeded(SocialMob $socialMob)
    {
        if ($this->isWithinWebHookNotificationWindow() && config('webhooks.attendees_today')) {
            Http::post(config('webhooks.attendees_today'), $socialMob->toArray());
        }
    }

    private function isWithinWebHookNotificationWindow(): bool
    {
        return now()
            ->isBetween(Carbon::parse(config('webhooks.start_time')), Carbon::parse(config('webhooks.end_time')));
    }
}
