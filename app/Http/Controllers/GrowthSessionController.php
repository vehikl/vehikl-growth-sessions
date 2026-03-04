<?php

namespace App\Http\Controllers;

use App\Events\GrowthSessionCreated;
use App\Events\GrowthSessionModified;
use App\Exceptions\AttendeeLimitReached;
use App\Http\Requests\DeleteGrowthSessionRequest;
use App\Http\Requests\StoreGrowthSessionRequest;
use App\Http\Requests\UpdateGrowthSessionRequest;
use App\Http\Resources\GrowthSession as GrowthSessionResource;
use App\Http\Resources\GrowthSessionWeek;
use App\Models\AnyDesk;
use App\Models\GrowthSession;
use App\Models\GrowthSessionUser;
use App\Models\UserType;
use App\Policies\GrowthSessionPolicy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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

        return Inertia::render('GrowthSessionView', [
            'growthSessionJson' => new GrowthSessionResource($growthSession),
            'userJson' => auth()->user(),
            'discordGuildId' => config('services.discord.guild_id')
        ]);
    }

    public function week(Request $request)
    {
        $user = $request->user();
        $sessions = GrowthSession::allInTheWeekOf($request->input('date'))->filter(function (GrowthSession $session) use (
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
        DB::transaction(function () use ($newGrowthSession, $request) {
            $newGrowthSession->save();
            $request->user()->growthSessions()->attach($newGrowthSession, ['user_type_id' => UserType::OWNER_ID]);
            $newGrowthSession->tags()->sync($request->input('tags'));
        });

        $newGrowthSession->fresh();

        broadcast(new GrowthSessionModified($newGrowthSession->id, GrowthSessionModified::ACTION_CREATED));
        event(new GrowthSessionCreated($newGrowthSession));

        return new GrowthSessionResource($newGrowthSession);
    }

    public function join(GrowthSession $growthSession, Request $request)
    {
        if ($growthSession->attendees()->count() === $growthSession->attendee_limit) {
            throw new AttendeeLimitReached;
        }

        // Switched from attach flow so the observer would run properly
        GrowthSessionUser::query()->updateOrCreate([
            'growth_session_id' => $growthSession->id,
            'user_id' => $request->user()->id,
        ], [
            'user_type_id' => UserType::ATTENDEE_ID
        ]);

        return new GrowthSessionResource($growthSession->fresh()->load(['attendees', 'watchers', 'comments', 'anydesk', 'tags']));
    }

    public function watch(GrowthSession $growthSession, Request $request)
    {
        // Switched from attach flow so the observer would run properly
        GrowthSessionUser::query()->updateOrCreate([
            'growth_session_id' => $growthSession->id,
            'user_id' => $request->user()->id,
        ], [
            'user_type_id' => UserType::WATCHER_ID
        ]);

        return new GrowthSessionResource($growthSession->fresh()->load(['attendees', 'watchers', 'comments', 'anydesk', 'tags']));
    }

    public function leave(GrowthSession $growthSession, Request $request)
    {
        GrowthSessionUser::query()
            ->where('growth_session_id', $growthSession->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return new GrowthSessionResource($growthSession->fresh()->load(['attendees', 'watchers', 'comments', 'anydesk', 'tags']));
    }

    public function update(UpdateGrowthSessionRequest $request, GrowthSession $growthSession)
    {
        $growthSession->update(Arr::except($request->validated(), 'tags'));
        $growthSession->tags()->sync($request->input('tags'));

        if ($request->input('anydesk_id')) {
            $anyDesk = AnyDesk::query()->find($request->input('anydesk_id'));
            $growthSession->anydesk()->associate($anyDesk);
        } else {
            $growthSession->anydesk()->dissociate();

            $growthSession->save();
        }

        return new GrowthSessionResource($growthSession->refresh()->load(['attendees', 'watchers', 'comments', 'anydesk', 'tags']));
    }

    public function destroy(DeleteGrowthSessionRequest $request, GrowthSession $growthSession)
    {
        $growthSession->delete();
    }
}
