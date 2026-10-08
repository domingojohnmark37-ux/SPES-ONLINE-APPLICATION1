<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingRegistration extends Model
{
    protected $fillable = [
        'token',
        'email',
        'password_hash',
        'pin_hash',
        'pin_expires_at',
        'expires_at',
        'failed_attempts',
        'resend_count',
        'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'pin_expires_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'failed_attempts' => 'integer',
            'resend_count' => 'integer',
        ];
    }
}
