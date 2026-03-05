<?php

namespace App\Observers;

use App\Events\GrowthSessionModified;
use App\Models\Comment;

class CommentObserver
{
    public function created(Comment $comment): void
    {
        broadcast(new GrowthSessionModified($comment->growth_session_id, GrowthSessionModified::ACTION_CREATED, GrowthSessionModified::TYPE_COMMENT));
    }

    public function updated(Comment $comment): void
    {
        broadcast(new GrowthSessionModified($comment->growth_session_id, GrowthSessionModified::ACTION_UPDATED, GrowthSessionModified::TYPE_COMMENT));
    }

    public function deleted(Comment $comment): void
    {
        broadcast(new GrowthSessionModified($comment->growth_session_id, GrowthSessionModified::ACTION_DELETED, GrowthSessionModified::TYPE_COMMENT));
    }
}
