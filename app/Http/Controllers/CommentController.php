<?php

namespace App\Http\Controllers;

use App\Events\GrowthSessionModified;
use App\Models\Comment;
use App\Http\Requests\DeleteCommentRequest;
use App\Models\GrowthSession;
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
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $comment = new Comment($validated);
        $comment->user()->associate($request->user());
        $comment->growthSession()->associate($growthSession);
        $comment->save();
        $growthSession = $growthSession->fresh()->load(['attendees', 'watchers', 'comments', 'anydesk', 'tags']);

        broadcast(new GrowthSessionModified($growthSession->id, GrowthSessionModified::ACTION_UPDATED, GrowthSessionModified::TYPE_COMMENT));

        return new GrowthSessionResource($growthSession);
    }

    public function destroy(DeleteCommentRequest $request, GrowthSession $growthSession,Comment $comment)
    {
        $comment->delete();

        broadcast(new GrowthSessionModified($growthSession->id, GrowthSessionModified::ACTION_UPDATED, GrowthSessionModified::TYPE_COMMENT));

        return new GrowthSessionResource($growthSession->fresh()->load(['attendees', 'watchers', 'comments', 'anydesk', 'tags']));
    }
}
