<?php

namespace App\Http\Controllers;

use App\Comment;
use App\Http\Requests\DeleteCommentRequest;
use App\GrowthSession;
use App\Http\Resources\GrowthSession as GrowthSessionResource;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request, GrowthSession $growthSession)
    {
        return $growthSession->comments()->orderByDesc('created_at')->get();
    }

    public function store(Request $request, GrowthSession $growthSession)
    {
        $comment = new Comment($request->all());
        $comment->user()->associate($request->user());
        $comment->growthSession()->associate($growthSession);
        $comment->save();
        $growthSession = $growthSession->fresh()->load(['attendees', 'watchers', 'comments', 'anydesk', 'tags']);
        return new GrowthSessionResource($growthSession);
    }

    public function destroy(DeleteCommentRequest $request, GrowthSession $growthSession,Comment $comment)
    {
        $comment->delete();

        return new GrowthSessionResource($growthSession->fresh());
    }
}
