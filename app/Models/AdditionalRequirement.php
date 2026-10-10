<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdditionalRequirement extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_required',
        'is_active',
        'audience',
        'template_path',
        'template_original_name',
        'due_at',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'due_at' => 'datetime',
    ];

    public function submissions(): HasMany
    {
        return $this->hasMany(ApplicationAdditionalRequirement::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(AdditionalRequirementTemplate::class);
    }

    public function expectedSubmissionCount(?int $lastSubmittedFileNumber = null): int
    {
        $templateCount = $this->templates->count() + (filled($this->template_path) ? 1 : 0);

        return max(1, $templateCount, $lastSubmittedFileNumber ?? 0);
    }

    public function isAvailableTo(?Application $application): bool
    {
        return $this->audience === 'all_applicants'
            || ($this->audience === 'approved_applicants' && $application?->status === 'approved');
    }
}
