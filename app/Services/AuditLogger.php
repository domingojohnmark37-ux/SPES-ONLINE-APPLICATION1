<?php

namespace App\Services;

use App\Models\Application;
use App\Models\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditLogger
{
    private const SENSITIVE_FIELDS = [
        'password',
        'password_hash',
        'pin_hash',
        'token',
        'remember_token',
        'session_id',
        'email_verified_at',
    ];

    public function record(
        string $action,
        string $module,
        ?User $actor = null,
        ?User $applicant = null,
        ?Application $application = null,
        array $details = [],
    ): AuditLog {
        if (! in_array($action, AuditAction::ALL, true)) {
            throw new \InvalidArgumentException("Unsupported audit action [{$action}].");
        }

        for ($attempt = 0; ; $attempt++) {
            try {
                return DB::transaction(function () use ($action, $module, $actor, $applicant, $application, $details): AuditLog {
                    $timestamp = now();
                    $sequence = ((int) AuditLog::query()->lockForUpdate()->max('id')) + 1;
                    $actorType = $details['actor_type'] ?? match ($actor?->role) {
                        'admin' => 'admin',
                        'user' => 'applicant',
                        default => 'system',
                    };

                    return AuditLog::create([
                        'audit_code' => sprintf('AUD-%s-%06d', $timestamp->format('Y'), $sequence),
                        'applicant_id' => $applicant?->id,
                        'application_id' => $application?->id,
                        'user_id' => $details['user_id'] ?? $actor?->id,
                        'actor_name' => $details['actor_name'] ?? $actor?->name ?? 'System',
                        'actor_role' => $details['actor_role'] ?? $actor?->role ?? 'system',
                        'actor_type' => $actorType,
                        'action' => $action,
                        'module' => $module,
                        'field_name' => $details['field_name'] ?? null,
                        'old_value' => $this->safeValue($details['old_value'] ?? null),
                        'new_value' => $this->safeValue($details['new_value'] ?? null),
                        'description' => $details['description'] ?? null,
                        'result' => $details['result'] ?? 'successful',
                        'ip_address' => $details['ip_address'] ?? request()->ip(),
                        'created_at' => $timestamp,
                    ]);
                });
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt >= 2) {
                    throw $exception;
                }
                usleep(10_000);
            }
        }
    }

    public function recordChanges(
        array $oldValues,
        array $newValues,
        string $module,
        ?User $actor,
        ?User $applicant,
        ?Application $application = null,
        ?string $action = null,
        array $options = [],
    ): array {
        $logs = [];

        foreach ($newValues as $field => $newValue) {
            if ($this->isSensitive((string) $field)) {
                continue;
            }

            $oldValue = $oldValues[$field] ?? null;
            if ($this->valuesAreEquivalent($oldValue, $newValue)) {
                continue;
            }

            $fieldAction = $field === 'status'
                ? ($options['status_action'] ?? AuditAction::APPLICATION_STATUS_CHANGED)
                : ($action ?? AuditAction::INFORMATION_UPDATED);

            $logs[] = $this->record(
                $fieldAction,
                $module,
                $actor,
                $applicant,
                $application,
                [
                    'field_name' => (string) $field,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'description' => $options['description'] ?? null,
                ],
            );
        }

        return $logs;
    }

    public function recordPasswordChanged(User $user, ?User $actor = null): AuditLog
    {
        return $this->record(
            AuditAction::PASSWORD_CHANGED,
            'Authentication',
            $actor ?? $user,
            $user->role === 'user' ? $user : null,
            null,
            [
                'description' => 'Password changed',
                'field_name' => null,
                'old_value' => null,
                'new_value' => null,
            ],
        );
    }

    public function recordFailedLoginAttempt(?User $candidate = null): AuditLog
    {
        return $this->record(
            AuditAction::LOGIN_FAILED,
            'Authentication',
            null,
            $candidate?->role === 'user' ? $candidate : null,
            $candidate?->role === 'user' ? $candidate->applications()->latest('created_at')->first() : null,
            [
                'actor_type' => in_array($candidate?->role, ['admin', 'user'], true)
                    ? ($candidate->role === 'admin' ? 'admin' : 'applicant')
                    : 'system',
                'actor_name' => 'Unverified login attempt',
                'actor_role' => $candidate?->role ?? 'unknown',
                'user_id' => $candidate?->id,
                'result' => 'failed',
                'description' => 'Login credentials were not accepted',
            ],
        );
    }

    public function recordDocumentSubmitted(
        User $applicant,
        Application $application,
        string $documentLabel,
        ?User $actor = null,
    ): AuditLog {
        return $this->record(
            AuditAction::DOCUMENT_SUBMITTED,
            'Application Documents',
            $actor ?? $applicant,
            $applicant,
            $application,
            ['description' => $documentLabel.' submitted'],
        );
    }

    private function normalize(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return null;
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}:\d{2})?$/D', $value) === 1) {
                return Carbon::parse($value)->format('Y-m-d H:i:s');
            }

            return $value;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $value;
    }

    private function valuesAreEquivalent(mixed $oldValue, mixed $newValue): bool
    {
        $oldNormalized = $this->normalize($oldValue);
        $newNormalized = $this->normalize($newValue);

        if ($oldNormalized === $newNormalized) {
            return true;
        }

        if (
            (is_bool($oldNormalized) || is_int($oldNormalized) || is_float($oldNormalized))
            && is_string($newNormalized)
            && is_numeric($newNormalized)
        ) {
            return (string) $oldNormalized === (string) (0 + $newNormalized);
        }
        if (
            (is_bool($newNormalized) || is_int($newNormalized) || is_float($newNormalized))
            && is_string($oldNormalized)
            && is_numeric($oldNormalized)
        ) {
            return (string) (0 + $oldNormalized) === (string) $newNormalized;
        }

        return false;
    }

    private function safeValue(mixed $value): ?string
    {
        if ($value === null || $this->isSensitiveValue($value)) {
            return null;
        }

        $normalized = $this->normalize($value);
        if ($normalized === null) {
            return null;
        }

        return Str::limit((string) $normalized, 4000, '');
    }

    private function isSensitive(string $field): bool
    {
        $field = strtolower($field);

        return in_array($field, self::SENSITIVE_FIELDS, true)
            || str_contains($field, 'password')
            || str_contains($field, 'token')
            || str_contains($field, 'secret')
            || str_ends_with($field, '_hash');
    }

    private function isSensitiveValue(mixed $value): bool
    {
        return is_string($value) && (
            str_starts_with($value, '$2y$')
            || str_starts_with($value, '$argon2')
        );
    }
}
