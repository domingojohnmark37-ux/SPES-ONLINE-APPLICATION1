<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingPersonalSecurityRecovery extends Model
{
    protected $hidden = ['code_hash'];

    protected $fillable = [
        'user_id',
        'code_hash',
        'expires_at',
        'failed_attempts',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'failed_attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
