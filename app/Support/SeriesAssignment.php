<?php

namespace App\Support;

use App\Models\GrowthSession;
use App\Models\Series;
use App\Models\User;

/**
 * Resolves a series name to a series owned by the filing member, creating one if needed.
 *
 * Only writes to the model — the caller saves, since `store()` files mid-transaction on a session
 * that has no row yet. Pruning the vacated series is likewise the observer's job, once the move
 * is on the row. {@see \App\Observers\GrowthSessionObserver}
 */
class SeriesAssignment
{
    private function __construct(
        private readonly GrowthSession $growthSession,
        private readonly User $filedBy,
    ) {
    }

    public static function for(GrowthSession $growthSession, User $filedBy): self
    {
        return new self($growthSession, $filedBy);
    }

    /** A blank name, or none, takes the session out of its series. */
    public function file(?string $name): void
    {
        $name = trim((string) $name);
        $series = $name === '' ? null : $this->threadCalled($name);

        // `associate(null)` only unsets the key, so the relation would lazy-load the series the
        // session just left — which the lazy-loading guard refuses. Set the relation too.
        $this->growthSession->series()->associate($series)->setRelation('series', $series);
    }

    /**
     * This member's series by that name, created if they have none.
     *
     * The lookup is case-insensitive by collation, so "vue deep dive" joins their existing
     * "Vue Deep Dive" rather than sitting beside it as a second series.
     */
    private function threadCalled(string $name): Series
    {
        return Series::query()->firstOrCreate(['owner_id' => $this->filedBy->id, 'name' => $name]);
    }

    /** Delete a series once nothing is filed under it, freeing the name to be used again. */
    public static function prune(?int $seriesId): void
    {
        $series = $seriesId === null ? null : Series::query()->find($seriesId);

        if ($series && ! $series->growthSessions()->exists()) {
            $series->delete();
        }
    }
}
