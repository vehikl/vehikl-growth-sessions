<?php

namespace App\Models;

use App\Enums\Role;
use App\Observers\GrowthSessionUserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(GrowthSessionUserObserver::class)]
class GrowthSessionUser extends Model
{
    protected $fillable = [
        'growth_session_id',
        'user_id',
        'user_type_id',
        'waitlisted_at',
    ];

    public $timestamps = false;

    protected $table = 'growth_session_user';

    /**
     * Microseconds survive the round trip, so two people who joined the same waitlist within the
     * same second still come off it in the order they asked.
     */
    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected function casts(): array
    {
        return [
            'user_type_id' => Role::class,
            'waitlisted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function growthSession(): BelongsTo
    {
        return $this->belongsTo(GrowthSession::class);
    }

    /**
     * The table has no id of its own, and its primary key includes the very column a promotion
     * rewrites - so Eloquent is pointed at the one pair that identifies a row for good: the unique
     * (growth session, member) index. Without this, saving a role change would go looking for a
     * key that isn't there.
     */
    protected function setKeysForSaveQuery($query): Builder
    {
        return $this->addRowKeysTo($query);
    }

    protected function setKeysForSelectQuery($query): Builder
    {
        return $this->addRowKeysTo($query);
    }

    private function addRowKeysTo(Builder $query): Builder
    {
        return $query
            ->where('growth_session_id', $this->getOriginal('growth_session_id', $this->growth_session_id))
            ->where('user_id', $this->getOriginal('user_id', $this->user_id));
    }
}
