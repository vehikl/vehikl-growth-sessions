<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    protected $table = 'users';

    use Notifiable,
        HasFactory;

    protected $fillable = [
        'github_nickname',
        'name',
        'avatar',
        'password',
        'is_vehikl_member'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_vehikl_member' => 'boolean',
        ];
    }

    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }

    public function growthSessions()
    {
        // TODO: Replace implementation of this to be "all Growth sessions involving this user"
        return $this->belongsToMany(GrowthSession::class)->wherePivot('user_type_id', UserType::OWNER_ID);
    }

    public function allSessions(): BelongsToMany
    {
        return $this->belongsToMany(GrowthSession::class);
    }

    public function sessionsHosted(): BelongsToMany
    {
        return $this->belongsToMany(GrowthSession::class)->wherePivot('user_type_id', UserType::OWNER_ID);
    }

    public function sessionsAttended(): BelongsToMany
    {
        return $this->belongsToMany(GrowthSession::class)->wherePivot('user_type_id', UserType::ATTENDEE_ID);
    }

    public function sessionsWatched(): BelongsToMany
    {
        return $this->belongsToMany(GrowthSession::class)->wherePivot('user_type_id', UserType::WATCHER_ID);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function scopeVehikaliens(Builder $query, bool $shouldBeVehikalien = true): Builder
    {
        return $query->where('is_vehikl_member', $shouldBeVehikalien);
    }

    public function scopeVisibleInStatistics(Builder $query, bool $shouldBeVisible = true): Builder
    {
        return $query->where('is_visible_in_statistics', $shouldBeVisible);
    }

    public function hasMobbedWith(): Attribute
    {
        return Attribute::get(fn() => $this
            ->allSessions
            ->flatMap
            ->members
            ->unique('id')
            ->reject(fn(User $peer) => $peer->id === $this->id || !$peer->is_vehikl_member));
    }
}
