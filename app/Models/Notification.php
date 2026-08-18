<?php

namespace App\Models;

use App\Enums\NotificationType;
use App\Observers\NotificationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
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
        'event_types',
        'metadata',
    ];

    /**
     * A method rather than the $casts property because AsEnumCollection::of() is a call, which a
     * property default cannot hold.
     */
    protected function casts(): array
    {
        return [
            'event_types' => AsEnumCollection::of(NotificationType::class),
            'read' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * Whether this notification is reporting the given event among the ones it carries.
     *
     * Null-safe because the cast leaves the attribute null until the model is saved or hydrated,
     * and asking an unsaved notification what it reports should answer "nothing", not fatal.
     */
    public function hasEvent(NotificationType $eventType): bool
    {
        return (bool) $this->event_types?->contains($eventType);
    }

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
