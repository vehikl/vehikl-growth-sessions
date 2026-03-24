<?php

namespace App\Services\Slack;

use App\Models\GrowthSession;
use App\Slack\Messages\GrowthSessionThreadParent;
use Carbon\Carbon;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Http\Client\ConnectionException;

class SessionPoster extends Messenger
{
    protected function canSendMessage(GrowthSession $growthSession): bool
    {
        $isValidDay = config('services.slack.chat.post-mode') === 'today'
            ? Carbon::now()->isSameDay($growthSession->date)
            : Carbon::now()->endOfDay()->gte($growthSession->date);

        return $this->isConfigured() && $isValidDay;
    }

    /**
     * @throws CircularDependencyException
     * @throws ConnectionException
     * @throws BindingResolutionException
     */
    public function post(GrowthSession $growthSession): void
    {
        $growthSession->refresh();

        if (!$this->canSendMessage($growthSession)) {
            return;
        }

        $blocks = app(GrowthSessionThreadParent::class)->build($growthSession);

        if (isset($growthSession->slack_thread_ts)) {
            $message = $this->updateMessage(
                $growthSession->slack_thread_ts,
                $blocks,
            );
            if (!$message->ok) {
                info('SessionPoster.post.update: unable to update message', [
                    'response' => $message,
                ]);
            }
            return;
        }

        $message = $this->sendMessage($blocks);
        if (!$message->ok) {
            info('SessionPoster.post.create: unable to create message', [
                'response' => $message,
            ]);
            return;
        }
        $growthSession->updateQuietly([
            'slack_thread_ts' => $message->id(),
        ]);
    }
}
