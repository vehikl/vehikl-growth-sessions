<?php

namespace App\Http\Controllers;

use App\AnyDesk;
use App\Events\GrowthSessionAttendeeChanged;
use App\Events\GrowthSessionCreated;
use App\Events\GrowthSessionDeleted;
use App\Events\GrowthSessionUpdated;
use App\Exceptions\AttendeeLimitReached;
use App\GrowthSession;
use App\Http\Requests\DeleteGrowthSessionRequest;
use App\Http\Requests\StoreGrowthSessionRequest;
use App\Http\Requests\UpdateGrowthSessionRequest;
use App\Http\Resources\GrowthSession as GrowthSessionResource;
use App\Http\Resources\GrowthSessionWeek;
use App\Policies\GrowthSessionPolicy;
use App\UserType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

class GrowthSessionController extends Controller
{
    public function show(Request $request, GrowthSession $growthSession)
    {
        // if the user is not allowed to view the growth session then return 404
        $growthSession->load(['attendees', 'watchers', 'comments', 'anydesk', 'tags']);
        abort_unless((new GrowthSessionPolicy())->view(request()->user(), $growthSession), Response::HTTP_NOT_FOUND);
        if ($request->expectsJson()) {
            return response()->json(new GrowthSessionResource($growthSession));
        }
        return view('growth-session', ['growthSession' => new GrowthSessionResource($growthSession)]);
    }

    public function week(Request $request)
    {
        $user = $request->user();
        $sessions = GrowthSession::allInTheWeekOf($request->input('date'))->filter(function (GrowthSession $session) use
        (
            $user
        ) {
            return (new GrowthSessionPolicy())->view($user, $session);
        });
        return new GrowthSessionWeek($sessions);
    }

    public function day()
    {
        return GrowthSessionResource::collection(
            GrowthSession::today()
                ->with(['attendees', 'watchers', 'comments', 'anydesk', 'tags'])
                ->get()
        );
    }

    public function store(StoreGrowthSessionRequest $request)
    {
        $newGrowthSession = new GrowthSession($request->validated());
        $newGrowthSession->save();
        $request->user()->growthSessions()->attach($newGrowthSession, ['user_type_id' => UserType::OWNER_ID]);
        $newGrowthSession->tags()->sync($request->input('tags'));

        $newGrowthSession->fresh();
        event(new GrowthSessionCreated($newGrowthSession));

        return new GrowthSessionResource($newGrowthSession);
    }

    public function join(GrowthSession $growthSession, Request $request)
    {
        if ($growthSession->attendees()->count() === $growthSession->attendee_limit) {
            throw new AttendeeLimitReached;
        }

        $growthSession->attendees()->attach($request->user(), ['user_type_id' => UserType::ATTENDEE_ID]);
        event(new GrowthSessionAttendeeChanged($growthSession->refresh()));

        return new GrowthSessionResource($growthSession);
    }

    public function watch(GrowthSession $growthSession, Request $request)
    {
        $growthSession->watchers()->attach($request->user(), ['user_type_id' => UserType::WATCHER_ID]);

        return new GrowthSessionResource($growthSession->refresh());
    }

    public function leave(GrowthSession $growthSession, Request $request)
    {
        $growthSession->watchers()->detach($request->user());
        $growthSession->attendees()->detach($request->user());

        event(new GrowthSessionAttendeeChanged($growthSession->refresh()));

        return new GrowthSessionResource($growthSession);
    }

    public function update(UpdateGrowthSessionRequest $request, GrowthSession $growthSession)
    {
        $originalValues = $growthSession->toArray();
        $growthSession->update(Arr::except($request->validated(), 'tags'));
        $growthSession->tags()->sync($request->input('tags'));

        if ($request->input('anydesk_id')) {
            $anyDesk = AnyDesk::query()->find($request->input('anydesk_id'));
            $growthSession->anydesk()->associate($anyDesk);
        } else {
            $growthSession->anydesk()->dissociate();

            $growthSession->save();
        }

        $growthSession->refresh();

        event(new GrowthSessionUpdated($originalValues, $growthSession->toArray()));

        return new GrowthSessionResource($growthSession);
    }

    public function destroy(DeleteGrowthSessionRequest $request, GrowthSession $growthSession)
    {
        $growthSession->delete();
        event(new GrowthSessionDeleted($growthSession));
    }
}
