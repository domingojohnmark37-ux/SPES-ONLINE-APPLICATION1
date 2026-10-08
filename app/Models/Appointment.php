<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Appointment extends Model
{
    protected $fillable = [
        'title',
        'description',
        'location',
        'starts_at',
        'is_published',
        'target_audience',
        'target_user_id',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'is_published' => 'boolean',
    ];

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function targetApplicants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'appointment_user');
    }

    public function scopeVisibleToApplicant(Builder $query, User $applicant): Builder
    {
        $latestStatus = Application::query()
            ->where('user_id', $applicant->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->value('status');
        $statusAudience = match ($latestStatus) {
            'approved' => 'approved_applicants',
            'pending' => 'pending_applicants',
            'denied' => 'denied_applicants',
            default => null,
        };

        return $query->where(function (Builder $targeted) use ($applicant, $statusAudience): void {
            $targeted->where('target_audience', 'all_applicants')
                ->orWhere(function (Builder $specific) use ($applicant): void {
                    $specific->where('target_audience', 'specific_applicant')
                        ->where('target_user_id', $applicant->id);
                })
                ->orWhere(function (Builder $multiple) use ($applicant): void {
                    $multiple->where('target_audience', 'multiple_applicants')
                        ->whereHas('targetApplicants', fn (Builder $recipients) => $recipients->whereKey($applicant->id));
                });

            if ($statusAudience !== null) {
                $targeted->orWhere('target_audience', $statusAudience);
            }
        });
    }

    public function recipientUsers(): Builder
    {
        $applicants = User::query()->where('role', 'user');

        if ($this->target_audience === 'all_applicants') {
            return $applicants;
        }

        if ($this->target_audience === 'specific_applicant') {
            return $applicants->whereKey($this->target_user_id);
        }

        if ($this->target_audience === 'multiple_applicants') {
            return $applicants->whereIn(
                'id',
                $this->targetApplicants()->select('users.id'),
            );
        }

        $status = match ($this->target_audience) {
            'approved_applicants' => 'approved',
            'pending_applicants' => 'pending',
            'denied_applicants' => 'denied',
            default => null,
        };

        if ($status === null) {
            return $applicants->whereRaw('1 = 0');
        }

        $matchingApplicants = Application::query()
            ->select('user_id')
            ->where('status', $status)
            ->whereRaw('applications.id = (
                SELECT latest_application.id
                FROM applications AS latest_application
                WHERE latest_application.user_id = applications.user_id
                ORDER BY latest_application.created_at DESC, latest_application.id DESC
                LIMIT 1
            )');

        return $applicants->whereIn('id', $matchingApplicants);
    }
}
