<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingEmailChange extends Model
{
    protected $fillable = ['user_id', 'email', 'pin_hash', 'pin_expires_at', 'failed_attempts'];

    protected function casts(): array
    {
        return [
            'pin_expires_at' => 'datetime',
            'failed_attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
