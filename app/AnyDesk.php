<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnyDesk extends Model
{
    use HasFactory;

    protected $table = 'anydesks';

    public $timestamps = false;
}
