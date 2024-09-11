<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'email',
        'avatar',
        'password',
        'is_vehikl_member'
    ];

    protected $hidden = [
        'email',
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_vehikl_member' => 'boolean',
    ];

    public function growthSessions()
    {
        // TODO: Replace implementation of this to be "all Growth sessions involving this user"
        return $this->belongsToMany(GrowthSession::class)->wherePivot('user_type_id', UserType::OWNER_ID);
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

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function scopeVehikaliens(Builder $query): Builder
    {
        return $query->where('is_vehikl_member', true);
    }
}
