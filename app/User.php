<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function socialMobs()
    {
        return $this->hasMany(SocialMob::class, 'owner_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
