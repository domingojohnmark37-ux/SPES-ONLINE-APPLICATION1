<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'audit_code',
        'applicant_id',
        'application_id',
        'user_id',
        'actor_name',
        'actor_role',
        'actor_type',
        'action',
        'module',
        'field_name',
        'old_value',
        'new_value',
        'description',
        'result',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit records are immutable.'));
        static::deleting(fn () => throw new LogicException('Audit records cannot be deleted.'));
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForActor(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForApplicant(Builder $query, int $applicantId): Builder
    {
        return $query->where('applicant_id', $applicantId);
    }

    public function scopeForApplication(Builder $query, int $applicationId): Builder
    {
        return $query->where('application_id', $applicationId);
    }

    public function scopeByAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    public function scopeBetweenDates(Builder $query, $start, $end): Builder
    {
        return $query->when($start, fn (Builder $builder) => $builder->where('created_at', '>=', $start))
            ->when($end, fn (Builder $builder) => $builder->where('created_at', '<=', $end));
    }
}
