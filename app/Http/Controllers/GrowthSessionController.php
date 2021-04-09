<?php

namespace App\Http\Controllers;

use App\Events\GrowthSessionAttendeeChanged;
use App\Events\GrowthSessionCreated;
use App\Events\GrowthSessionDeleted;
use App\Events\GrowthSessionUpdated;
use App\Exceptions\AttendeeLimitReached;
use App\Http\Requests\DeleteGrowthSessionRequest;
use App\Http\Requests\StoreGrowthSessionRequest;
use App\Http\Requests\UpdateGrowthSessionRequest;
use App\Http\Resources\GrowthSession as GrowthSessionResource;
use App\Http\Resources\GrowthSessionWeek;
use App\GrowthSession;
use App\Policies\GrowthSessionPolicy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GrowthSessionController extends Controller
{
    public function show(GrowthSession $growthSession)
    {
        // if the user is not allowed to view the growth session then return 404
        abort_unless((new GrowthSessionPolicy())->view(request()->user(), $growthSession), Response::HTTP_NOT_FOUND);

        return view('growth-session', ['growthSession' => new GrowthSessionResource($growthSession)]);
    }

    public function week(Request $request)
    {
        $user = $request->user();
        $sessions = GrowthSession::allInTheWeekOf($request->input('date'))->filter(function (GrowthSession $session) use ($user) {
            return (new GrowthSessionPolicy())->view($user, $session);
        });
        return new GrowthSessionWeek($sessions);
    }

    public function day()
    {
        return GrowthSessionResource::collection(GrowthSession::today()->get());
    }

    public function store(StoreGrowthSessionRequest $request)
    {
        $growthSession = $request->user()->growthSessions()->save(new GrowthSession ($request->validated()));
        $growthSession->load(['owner', 'attendees', 'comments']);
        event(new GrowthSessionCreated($growthSession));

        return $growthSession;
    }

    public function join(GrowthSession $growthSession, Request $request)
    {
        if ($growthSession->attendees()->count() === $growthSession->attendee_limit) {
            throw new AttendeeLimitReached;
        }

        $growthSession->attendees()->attach($request->user());
        event(new GrowthSessionAttendeeChanged($growthSession->refresh()));

        return $growthSession;
    }

    public function leave(GrowthSession $growthSession, Request $request)
    {
        $growthSession->attendees()->detach($request->user());
        event(new GrowthSessionAttendeeChanged($growthSession->refresh()));

        return $growthSession;
    }

    public function edit(GrowthSession $growthSession)
    {
        return view('growth-session-edit', compact('growthSession'));
    }

    public function update(UpdateGrowthSessionRequest $request, GrowthSession $growthSession)
    {
        $originalValues = $growthSession->toArray();
        $growthSession->update($request->validated());
        event(new GrowthSessionUpdated($originalValues, $growthSession->toArray()));

        return $growthSession;
    }

    public function destroy(DeleteGrowthSessionRequest $request, GrowthSession $growthSession)
    {
        $growthSession->delete();
        event(new GrowthSessionDeleted($growthSession));
    }
}
