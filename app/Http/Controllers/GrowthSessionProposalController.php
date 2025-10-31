<?php

namespace App\Http\Controllers;

use App\Events\GrowthSessionCreated;
use App\Http\Requests\ApproveGrowthSessionProposalRequest;
use App\Http\Requests\StoreGrowthSessionProposalRequest;
use App\Http\Requests\UpdateGrowthSessionProposalRequest;
use App\Http\Resources\GrowthSessionProposal as GrowthSessionProposalResource;
use App\Models\GrowthSession;
use App\Models\GrowthSessionProposal;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;

class GrowthSessionProposalController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $proposals = GrowthSessionProposal::query()
            ->with(['creator', 'timePreferences', 'tags'])
            ->where(function ($query) use ($user) {
                // Show user's own proposals
                $query->where('creator_id', $user->id);

                // Or all proposals if user is Vehikl member
                if ($user && $user->is_vehikl_member) {
                    $query->orWhereNotNull('id');
                }
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('ProposalsPage', [
            'proposals' => GrowthSessionProposalResource::collection($proposals),
            'userJson' => $user,
        ]);
    }

    public function store(StoreGrowthSessionProposalRequest $request)
    {
        $proposal = new GrowthSessionProposal($request->validated());
        $proposal->creator_id = $request->user()->id;
        $proposal->status = 'pending';
        $proposal->save();

        // Sync tags
        $proposal->tags()->sync($request->input('tags', []));

        // Create time preferences
        if ($request->has('time_preferences')) {
            foreach ($request->input('time_preferences') as $timePreference) {
                $proposal->timePreferences()->create($timePreference);
            }
        }

        $proposal->load(['creator', 'timePreferences', 'tags']);

        return new GrowthSessionProposalResource($proposal);
    }

    public function show(Request $request, GrowthSessionProposal $proposal)
    {
        $this->authorize('view', $proposal);

        $proposal->load(['creator', 'timePreferences', 'tags']);

        return new GrowthSessionProposalResource($proposal);
    }

    public function update(UpdateGrowthSessionProposalRequest $request, GrowthSessionProposal $proposal)
    {
        $proposal->update(Arr::except($request->validated(), ['tags', 'time_preferences']));

        // Sync tags
        $proposal->tags()->sync($request->input('tags', []));

        // Update time preferences - delete old ones and create new ones
        $proposal->timePreferences()->delete();
        if ($request->has('time_preferences')) {
            foreach ($request->input('time_preferences') as $timePreference) {
                $proposal->timePreferences()->create($timePreference);
            }
        }

        $proposal->load(['creator', 'timePreferences', 'tags']);

        return new GrowthSessionProposalResource($proposal);
    }

    public function destroy(Request $request, GrowthSessionProposal $proposal)
    {
        $this->authorize('delete', $proposal);

        $proposal->delete();

        return response()->noContent();
    }

    public function approve(ApproveGrowthSessionProposalRequest $request, GrowthSessionProposal $proposal)
    {
        // Create a new growth session from the proposal
        $growthSession = new GrowthSession($request->validated());
        $growthSession->save();

        // Attach the proposal creator as the owner
        $growthSession->owners()->attach($proposal->creator_id, ['user_type_id' => UserType::OWNER_ID]);

        // Sync tags from proposal to session
        $growthSession->tags()->sync($proposal->tags->pluck('id'));

        // Mark proposal as approved
        $proposal->status = 'approved';
        $proposal->save();

        event(new GrowthSessionCreated($growthSession->fresh()));

        return new GrowthSessionProposalResource($proposal->load(['creator', 'timePreferences', 'tags']));
    }
}
