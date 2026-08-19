<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class GrowthSessionCommentAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Comment $comment) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => NotificationType::GrowthSessionCommentAdded->value,
            'growth_session_id' => $this->comment->growth_session_id,
            'title' => $this->comment->growthSession->title,
            'commenter' => $this->comment->user->name,
            'commenter_id' => $this->comment->user->id,
            'commenter_avatar' => $this->comment->user->avatar,
            'url' => "/growth_sessions/{$this->comment->growth_session_id}",
        ];
    }

    /**
     * Without this, the broadcast payload's `type` gets overwritten with this class's fully
     * qualified name — `BroadcastNotificationCreated::broadcastWith()` merges `toArray()` with
     * `['type' => $this->broadcastType()]` last, and the default `broadcastType()` is `get_class()`.
     */
    public function broadcastType(): string
    {
        return NotificationType::GrowthSessionCommentAdded->value;
    }
}
