<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteCommentRequest;
use App\Http\Resources\Comment as CommentResource;
use App\Http\Resources\GrowthSession as GrowthSessionResource;
use App\Models\Comment;
use App\Models\GrowthSession;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request, GrowthSession $growthSession)
    {
        return CommentResource::collection($growthSession->comments()->orderByDesc('created_at')->get());
    }

    public function store(Request $request, GrowthSession $growthSession)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $comment = new Comment($validated);
        $comment->user()->associate($request->user());
        $comment->growthSession()->associate($growthSession);
        $comment->save();
        $growthSession = $growthSession->fresh()->load(GrowthSession::RESOURCE_RELATIONS);

        return new GrowthSessionResource($growthSession);
    }

    public function destroy(DeleteCommentRequest $request, GrowthSession $growthSession, Comment $comment)
    {
        $comment->delete();

        return new GrowthSessionResource($growthSession->fresh()->load(GrowthSession::RESOURCE_RELATIONS));
    }
}
