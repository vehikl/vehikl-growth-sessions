<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * A named group of growth sessions, owned by the member who started it.
 *
 * Names are unique per owner, not globally — two members may each run a series called the same
 * thing. A series is deleted once nothing is filed under it. {@see \App\Support\SeriesAssignment}
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
     * This owner's series names, alphabetically.
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
