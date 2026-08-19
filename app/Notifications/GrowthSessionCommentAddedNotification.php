<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

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
            'excerpt' => Str::limit($this->comment->content, 120),
            'url' => "/growth_sessions/{$this->comment->growth_session_id}",
        ];
    }
}
