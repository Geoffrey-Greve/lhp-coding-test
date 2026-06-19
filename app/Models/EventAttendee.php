<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAttendee extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'email',
        'confirmation_sent_at',
        'reminder_3d_sent_at',
        'reminder_24h_sent_at',
    ];

    protected $casts = [
        'confirmation_sent_at' => 'datetime',
        'reminder_3d_sent_at' => 'datetime',
        'reminder_24h_sent_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
