<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdditionalRequirementTemplate extends Model
{
    protected $fillable = [
        'additional_requirement_id',
        'file_path',
        'original_name',
    ];

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(AdditionalRequirement::class, 'additional_requirement_id');
    }
}
