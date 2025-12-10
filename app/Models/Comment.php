<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $table = 'comments';

    use HasFactory;

    protected $fillable = ['content'];
    protected $with = ['user'];
    protected $appends = ['time_stamp'];

    protected function casts(): array
    {
        return [
            'growth_session_id' => 'int',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function growthSession()
    {
        return $this->belongsTo(GrowthSession::class);
    }

    protected function timeStamp(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at->diffForHumans(),
        );
    }
}
