<?php

namespace App\Models;

use App\Models\Concerns\HasTextSegments;
use App\Observers\CommentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(CommentObserver::class)]
class Comment extends Model
{
    protected $table = 'comments';

    use HasFactory;
    use HasTextSegments;

    protected $fillable = ['content'];
    protected $with = ['user'];

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
            get: fn() => $this->created_at->diffForHumans(),
        );
    }

    protected function segments(): Attribute
    {
        return $this->textSegmentsAttribute($this->content, (bool) $this->user?->is_vehikl_member);
    }
}
