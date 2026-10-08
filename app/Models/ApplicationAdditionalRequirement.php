<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationAdditionalRequirement extends Model
{
    protected $fillable = [
        'application_id',
        'additional_requirement_id',
        'file_path',
        'original_name',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(AdditionalRequirement::class, 'additional_requirement_id');
    }
}
