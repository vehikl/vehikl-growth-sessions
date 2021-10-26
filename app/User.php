<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function growthSessions()
    {
        return $this->hasMany(GrowthSession::class, 'owner_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function getAttendedGrowthSessionsCountAttribute(): int
    {
        return DB::table('social_mob_user')->where('user_id','=',$this->id)->count();
    }
    public function getAttendedGrowthSessionsAverageAttribute(): int
    {
        /** @var Collection $attended */
        $attended = GrowthSession::whereIn('id', DB::table('social_mob_user')->where('user_id', $this->id)
            ->pluck('social_mob_id'))
            ->orderBy('date');

       //dd($attended->pluck('date')->map->toDateString());

        /** @var Carbon $minDate */
        $minDate = $attended->first()->date;
        $maxDate = now();

        $diffInDays = $minDate->diffInDays($maxDate);

        //dump($minDate->diffInDays($maxDate));
        return ($attended->count() / $diffInDays) * 7;
    }
}
