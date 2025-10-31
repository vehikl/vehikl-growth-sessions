<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrowthSessionProposalTimePreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'growth_session_proposal_id',
        'weekday',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(GrowthSessionProposal::class, 'growth_session_proposal_id');
    }
}
