<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\SocialMob as GrowthSession;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = ['content'];
    protected $with = ['user'];
    protected $appends = ['time_stamp'];
    protected $casts = ['social_mob_id' => 'int'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function socialMob()
    {
        return $this->belongsTo(GrowthSession::class);
    }

    public function getTimeStampAttribute()
    {
        return $this->created_at->diffForHumans();
    }
}
