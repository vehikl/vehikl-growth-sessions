<?php

namespace App\Models;

use App\Observers\GrowthSessionUserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(GrowthSessionUserObserver::class)]
class GrowthSessionUser extends Model
{
    protected $fillable = [
        'growth_session_id',
        'user_id',
        'user_type_id',
    ];
    public $timestamps = false;

    protected $table = 'growth_session_user';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function growthSession(): BelongsTo
    {
        return $this->belongsTo(GrowthSession::class);
    }

    public function getUserType(): string
    {
        return UserType::getTypeById($this->user_type_id);
    }
}
