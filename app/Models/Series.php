<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * A named thread of growth sessions, run by the member who started it.
 *
 * A series belongs to whoever first filed a session under the name, and only they may file
 * anything else under it: a thread is somebody's to run, so what appears in it is theirs to say.
 * Two members may each run a series by the same name - the name is theirs within their own, and
 * nobody's claim on one locks the words away from everybody else.
 *
 * A series lives exactly as long as something is filed under it. The last session to leave takes
 * the thread with it {@see \App\Support\SeriesAssignment::prune()}, so nothing here is ever an
 * empty name waiting to be tidied up.
 */
class Series extends Model
{
    use HasFactory;

    protected $table = 'series';

    protected $fillable = ['owner_id', 'name'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function growthSessions(): HasMany
    {
        return $this->hasMany(GrowthSession::class);
    }

    /**
     * The series this member is running, alphabetically - the threads the form's picker offers.
     *
     * Only their own, because those are the only ones they may file a session under. Offering
     * somebody else's would be offering a choice that cannot be taken.
     *
     * @return Collection<int, string>
     */
    public static function namesOwnedBy(User $owner): Collection
    {
        return static::query()
            ->where('owner_id', $owner->id)
            ->orderBy('name')
            ->pluck('name');
    }
}
