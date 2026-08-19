<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Comment;

class GrowthSessionCommentAddedNotification extends BaseGrowthSessionNotification
{
    public function __construct(public Comment $comment) {}

    public function notificationType(): NotificationType
    {
        return NotificationType::GrowthSessionCommentAdded;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->notificationType()->value,
            'growth_session_id' => $this->comment->growth_session_id,
            'title' => $this->comment->growthSession->title,
            'commenter' => $this->comment->user->name,
            'commenter_id' => $this->comment->user->id,
            'commenter_avatar' => $this->comment->user->avatar,
            'url' => "/growth_sessions/{$this->comment->growth_session_id}",
        ];
    }
}
