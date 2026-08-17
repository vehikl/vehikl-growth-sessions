<?php

namespace App\Models;

use App\Enums\NotificationType;
use App\Observers\NotificationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(NotificationObserver::class)]
class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'initiator',
        'user_id',
        'growth_session_id',
        'read',
        'type',
        'metadata',
    ];

    protected $casts = [
        'type' => NotificationType::class,
        'read' => 'boolean',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator');
    }

    public function growthSession(): BelongsTo
    {
        return $this->belongsTo(GrowthSession::class);
    }
}
