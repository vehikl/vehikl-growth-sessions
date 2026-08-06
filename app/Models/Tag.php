<?php

namespace App\Models;

use Closure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    public function growthSessions(): BelongsToMany
    {
        return $this->belongsToMany(GrowthSession::class);
    }

    /**
     * How often each tag is used by the Growth Sessions `$within` selects, busiest first.
     *
     * Shared by the Dashboard and the Statistics page, which differ only in which sessions
     * they count and how many tags they show, so the payload shape has one home. Tags nobody
     * used are dropped, and ties break alphabetically so the ranking does not reshuffle
     * between requests.
     *
     * @return array<int, array{id: int, name: string, sessions_count: int}>
     */
    public static function usageRanking(Closure $within, ?int $limit = null): array
    {
        return static::query()
            ->withCount(['growthSessions as sessions_count' => $within])
            ->having('sessions_count', '>', 0)
            ->orderByDesc('sessions_count')
            ->orderBy('name')
            ->when($limit, fn ($query) => $query->limit($limit))
            ->get(['id', 'name'])
            ->map(fn (Tag $tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'sessions_count' => $tag->sessions_count,
            ])
            ->all();
    }
}
