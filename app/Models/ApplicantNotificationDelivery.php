<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicantNotificationDelivery extends Model
{
    protected $fillable = [
        'user_id',
        'event_hash',
        'channel',
        'status',
        'attempts',
        'last_error',
        'claimed_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }
}
