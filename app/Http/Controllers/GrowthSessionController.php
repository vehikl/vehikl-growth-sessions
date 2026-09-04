<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Events\GrowthSessionCreated;
use App\Events\GrowthSessionModified;
use App\Http\Requests\DeleteGrowthSessionRequest;
use App\Http\Requests\StoreGrowthSessionRequest;
use App\Http\Requests\UpdateGrowthSessionRequest;
use App\Http\Resources\GrowthSession as GrowthSessionResource;
use App\Http\Resources\GrowthSessionWeek;
use App\Models\AnyDesk;
use App\Models\GrowthSession;
use App\Policies\GrowthSessionPolicy;
use App\Support\InviteLink;
use App\Support\SeriesAssignment;
use App\Support\Seating;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class GrowthSessionController extends Controller
{
    public function show(Request $request, GrowthSession $growthSession)
    {
        abort_unless((new GrowthSessionPolicy)->view($request->user(), $growthSession), Response::HTTP_NOT_FOUND);

        if (! $request->expectsJson()) {
            return redirect()->route('home', [
                'date' => $growthSession->date->toDateString(),
                'session' => $growthSession->id,
            ]);
        }

        $growthSession->load(GrowthSession::RESOURCE_RELATIONS);

        return response()->json(new GrowthSessionResource($growthSession));
    }

    public function week(Request $request)
    {
        $user = $request->user();
        $sessions = GrowthSession::allInTheWeekOf($request->input('date'))->filter(function (GrowthSession $session) use (
            $user
        ) {
            return (new GrowthSessionPolicy)->view($user, $session);
        })->loadMissing(GrowthSession::RESOURCE_RELATIONS);

        return new GrowthSessionWeek($sessions);
    }

    public function day()
    {
        return GrowthSessionResource::collection(
            GrowthSession::today()
                ->with(GrowthSession::RESOURCE_RELATIONS)
                ->get()
        );
    }

    public function store(StoreGrowthSessionRequest $request)
    {
        $newGrowthSession = new GrowthSession($request->validated());
        InviteLink::for($newGrowthSession)->set($request->boolean('has_invite_link'));
        SeriesAssignment::for($newGrowthSession, $request->user())->file($request->input('series_name'));

        DB::transaction(function () use ($newGrowthSession, $request) {
            $newGrowthSession->save();
            $request->user()->growthSessions()->attach($newGrowthSession, ['user_type_id' => Role::Owner->value]);
            $newGrowthSession->tags()->sync($request->input('tags'));
        });

        // save() doesn't populate columns the request omitted (is_public, allow_watchers are
        // 'sometimes' rules) with their DB defaults, so refresh from the row fresh() just inserted
        // rather than merely loading relations onto the in-memory, still-attribute-incomplete model.
        $newGrowthSession = $newGrowthSession->fresh(GrowthSession::RESOURCE_RELATIONS);

        broadcast(new GrowthSessionModified($newGrowthSession->id, GrowthSessionModified::ACTION_CREATED));
        event(new GrowthSessionCreated($newGrowthSession));

        return new GrowthSessionResource($newGrowthSession);
    }

    /**
     * Joining a growth session with a seat free takes it; joining one with none takes a place in
     * line instead, so a full growth session is something to ask for rather than a dead end.
     */
    public function join(GrowthSession $growthSession, Request $request)
    {
        Seating::for($growthSession)->take($request->user());

        return $this->rosterOf($growthSession);
    }

    public function watch(GrowthSession $growthSession, Request $request)
    {
        Seating::for($growthSession)->spectate($request->user());

        return $this->rosterOf($growthSession);
    }

    /**
     * Leaving covers giving up a seat and giving up a place in line alike. Either way the queue is
     * asked to fill whatever is now free - withdrawing from it frees nothing, so the order behind
     * the person who left is untouched.
     */
    public function leave(GrowthSession $growthSession, Request $request)
    {
        Seating::for($growthSession)->release($request->user());

        return $this->rosterOf($growthSession);
    }

    public function update(UpdateGrowthSessionRequest $request, GrowthSession $growthSession)
    {
        $growthSession->fill($request->validated());

        // An update that says nothing about the invite link asks for whatever the owner set previously — which the
        // module still reconciles against the new visibility, so turning a growth session public revokes its link.
        $inviteLink = InviteLink::for($growthSession);
        $inviteLink->set($request->boolean('has_invite_link', $inviteLink->exists()));

        // An omitted series_name leaves the session filed where it is.
        if ($request->has('series_name')) {
            SeriesAssignment::for($growthSession, $request->user())->file($request->input('series_name'));
        }

        $growthSession->tags()->sync($request->input('tags'));

        if ($request->input('anydesk_id')) {
            $anyDesk = AnyDesk::query()->find($request->input('anydesk_id'));
            $growthSession->anydesk()->associate($anyDesk);
        } else {
            $growthSession->anydesk()->dissociate();
        }

        $growthSession->save();

        // Raising the limit is the other way a seat comes free, so the queue is served here too.
        Seating::for($growthSession)->reseat();

        return $this->rosterOf($growthSession);
    }

    public function destroy(DeleteGrowthSessionRequest $request, GrowthSession $growthSession)
    {
        $growthSession->delete();
    }

    /** Never a bare model: every growth session response is built from the same loaded relations. */
    private function rosterOf(GrowthSession $growthSession): GrowthSessionResource
    {
        return new GrowthSessionResource($growthSession->fresh()->load(GrowthSession::RESOURCE_RELATIONS));
    }
}
