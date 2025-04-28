<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnyDesk extends Model
{
    use HasFactory;

    protected $table = 'anydesks';

    public $timestamps = false;

    public function growthSession()
    {
        return $this->hasMany(GrowthSession::class, 'anydesk_id', 'id');
    }
}
