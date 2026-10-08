<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminPreference extends Model
{
    protected $fillable = [
        'user_id',
        'theme',
        'language',
        'sidebar_behavior',
        'font_size',
        'date_format',
        'timezone',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
