<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingPasswordReset extends Model
{
    protected $fillable = [
        'token',
        'user_id',
        'password_hash',
        'pin_hash',
        'pin_expires_at',
        'last_sent_at',
        'failed_attempts',
        'resend_count',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'pin_expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'expires_at' => 'datetime',
            'failed_attempts' => 'integer',
            'resend_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
