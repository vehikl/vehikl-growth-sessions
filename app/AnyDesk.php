<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnyDesk extends Model
{
    use HasFactory;

    protected $table = 'anydesks';

    public $timestamps = false;

    public function growthSession()
    {
        return $this->hasMany(GrowthSession::class, 'anydesks_remote_desk_id', 'remote_desk_id');
    }
}
