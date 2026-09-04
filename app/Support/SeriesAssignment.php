<?php

namespace App\Support;

use App\Models\GrowthSession;
use App\Models\Series;
use App\Models\User;

/**
 * Which series a growth session is filed under.
 *
 * The whole move lives here: a name is trimmed, matched against the threads the member already
 * runs so a differently-cased spelling joins the one that exists rather than opening a second
 * beside it, and one is started for them if no such thread is running yet. Saying nothing files
 * the session under no series at all.
 *
 * The member doing the filing is the one whose series are searched, and the one who owns whatever
 * gets started - a thread is somebody's to run, so a name that happens to match somebody else's
 * series is just a name, and starts a thread of this member's own. Every caller hands in the
 * session's owner, which is who the policy has already established is doing the filing.
 *
 * Callers say where the session should end up, not how to get there. Filing only writes to the
 * model, so persisting stays with whoever called - `store()` is mid-transaction when it files, and
 * the session it is filing has no row yet. Clearing away the thread a session has just left is
 * likewise not filing's business: it can only be known once the move is on the row, so
 * {@see \App\Observers\GrowthSessionObserver} asks for it afterwards.
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

    /** The one way in. A blank name, or none, takes the session out of whatever it was in. */
    public function file(?string $name): void
    {
        $name = trim((string) $name);
        $series = $name === '' ? null : $this->threadCalled($name);

        // Setting the relation as well as the key, because `associate(null)` only unsets it: a
        // session left holding no series would go looking for the one it just left, and the
        // lazy-loading guard refuses. Saying plainly that there is none settles it either way.
        $this->growthSession->series()->associate($series)->setRelation('series', $series);
    }

    /**
     * The thread of this member's own that goes by the name, started if it is not running yet.
     *
     * The lookup is case-insensitive by collation, so "vue deep dive" finds their "Vue Deep Dive"
     * and adopts its spelling: somebody typing a name they already use means the thread they can
     * see in the picker, and two casings of one name would sit there as two threads with no way to
     * tell them apart. Somebody else's series by the same name is not theirs to join, so it is not
     * looked at - they get one of their own.
     */
    private function threadCalled(string $name): Series
    {
        return Series::query()->firstOrCreate(['owner_id' => $this->filedBy->id, 'name' => $name]);
    }

    /**
     * Clear away a thread that nothing is filed under any more.
     *
     * A series lives for exactly as long as it holds a session, so the last one to leave - moved
     * to another thread, taken out of every thread, or deleted outright - takes the thread with
     * it, and the name is free for anyone to start again.
     */
    public static function prune(?int $seriesId): void
    {
        $series = $seriesId === null ? null : Series::query()->find($seriesId);

        if ($series && ! $series->growthSessions()->exists()) {
            $series->delete();
        }
    }
}
