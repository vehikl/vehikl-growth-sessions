<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserHasMobbedWithView extends Model
{
    public $timestamps = false;

    protected $table = 'user_has_mobbed_with';

    public function mainUser(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'main_user_id');
    }

    public function otherUser(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'other_user_id');
    }

    public function growthSession(): BelongsTo
    {
        return $this->belongsTo(GrowthSession::class);
    }
}
