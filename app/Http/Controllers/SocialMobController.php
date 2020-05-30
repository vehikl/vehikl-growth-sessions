<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteSocialMobRequest;
use App\Http\Requests\JoinSocialMobRequest;
use App\Http\Requests\StoreSocialMobRequest;
use App\Http\Requests\UpdateSocialMobRequest;
use App\SocialMob;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class SocialMobController extends Controller
{
    public function index()
    {
        return SocialMob::all();
    }

    public function week(Request $request)
    {
        $referenceDate = CarbonImmutable::parse($request->input('date'));
        $weekMobs = SocialMob::query()->weekOf($referenceDate)->orderBy('date')->orderBy('start_time')->get();
        $startPoint = $referenceDate->isDayOfWeek(Carbon::MONDAY)
            ? $referenceDate
            : $referenceDate->modify('Last Monday');

        $response = [
            $startPoint->toDateString() => [],
            $startPoint->addDays(1)->toDateString() => [],
            $startPoint->addDays(2)->toDateString() => [],
            $startPoint->addDays(3)->toDateString() => [],
            $startPoint->addDays(4)->toDateString() => []
        ];
        foreach ($weekMobs as $mobModel) {
            $mob = $mobModel->toArray();
            array_push($response[$mob['date']], $mob);
        }

        return $response;
    }

    public function store(StoreSocialMobRequest $request)
    {
        return $request->user()->socialMobs()->save(new SocialMob($request->validated()));
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
