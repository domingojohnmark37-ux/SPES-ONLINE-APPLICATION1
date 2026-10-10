<?php

namespace App\Services;

use App\Models\Application;
use App\Models\AuditAction;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ApplicantAuditQuery
{
    private bool $settingsLoaded = false;

    private ?SystemSetting $settings = null;

    public function filterOptions(): array
    {
        $periods = ['all' => 'All Periods'];
        $settings = $this->settings();
        if ($settings?->application_start_date && $settings?->application_end_date) {
            $periods['configured'] = 'Configured application window ('
                .$settings->application_start_date->format('M j, Y').' – '
                .$settings->application_end_date->format('M j, Y').')';
        }

        return [
            'periods' => $periods,
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action', 'action')->all(),
            'statuses' => Application::query()->whereNotNull('status')->distinct()->orderBy('status')->pluck('status', 'status')->all(),
        ];
    }

    public function filteredLogs(array $filters): Builder
    {
        $query = AuditLog::query()->with(['applicant', 'application', 'user']);

        if (filled($filters['search'] ?? null)) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search): void {
                $builder->whereHas('applicant', fn (Builder $applicant) => $applicant
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('id', 'like', '%'.$search.'%'))
                    ->orWhereHas('application', fn (Builder $application) => $application
                        ->where('id', 'like', '%'.$search.'%')
                        ->orWhere('ref_id', 'like', '%'.$search.'%'))
                    ->orWhere('applicant_id', 'like', '%'.$search.'%');
            });
        }

        if (filled($filters['user_id'] ?? null)) {
            $query->where('user_id', $filters['user_id']);
        }

        if (($filters['period'] ?? 'all') === 'configured') {
            $settings = $this->settings();
            if ($settings?->application_start_date && $settings?->application_end_date) {
                $query->whereHas('application', fn (Builder $application) => $application
                    ->whereDate('created_at', '>=', $settings->application_start_date->toDateString())
                    ->whereDate('created_at', '<=', $settings->application_end_date->toDateString()));
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        [$start, $end] = $this->dateBounds($filters);
        $query->betweenDates($start, $end);

        if (filled($filters['action'] ?? null)) {
            $query->where('action', $filters['action']);
        }
        if (filled($filters['status'] ?? null)) {
            $query->whereHas('application', fn (Builder $application) => $application->where('status', $filters['status']));
        }

        return $query;
    }

    public function summary(array $filters): array
    {
        $base = $this->filteredLogs($filters);

        // All cards use the same filtered rows: total is distinct applicant_id, today is today's rows,
        // information counts changed applicant/application fields except status, status counts
        // field_name=status, and security counts login success/failure, logout, and password changes.
        $totalApplicants = (clone $base)->whereNotNull('applicant_id')->distinct('applicant_id')->count('applicant_id');
        $today = (clone $base)->whereDate('created_at', today())->count();
        $information = (clone $base)->whereIn('action', [
            AuditAction::INFORMATION_UPDATED,
            AuditAction::APPLICATION_UPDATED,
        ])->whereNotNull('field_name')->where('field_name', '<>', 'status')->count();
        $status = (clone $base)->where('field_name', 'status')->count();
        $security = (clone $base)->whereIn('action', [
            AuditAction::LOGIN_SUCCESS,
            AuditAction::LOGIN_FAILED,
            AuditAction::LOGOUT,
            AuditAction::PASSWORD_CHANGED,
        ])->count();

        return compact('totalApplicants', 'today', 'information', 'status', 'security');
    }

    public function periodName(?string $applicationCreatedAt): string
    {
        if (! $applicationCreatedAt) {
            return 'No application period';
        }

        $settings = $this->settings();
        if (! $settings?->application_start_date || ! $settings?->application_end_date) {
            return 'Period not configured';
        }

        $createdAt = Carbon::parse($applicationCreatedAt);

        return $createdAt->betweenIncluded(
            $settings->application_start_date->copy()->startOfDay(),
            $settings->application_end_date->copy()->endOfDay(),
        )
            ? 'Configured application window'
            : 'Outside configured window';
    }

    public function dateBounds(array $filters): array
    {
        $now = now();

        return match ($filters['date_range'] ?? 'all') {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'last7' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'last30' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'custom' => [
                filled($filters['date_from'] ?? null) ? Carbon::parse($filters['date_from'])->startOfDay() : null,
                filled($filters['date_to'] ?? null) ? Carbon::parse($filters['date_to'])->endOfDay() : null,
            ],
            default => [null, null],
        };
    }

    public function actorOptions(?string $role = null)
    {
        return User::query()
            ->whereIn('role', $role === null ? ['admin', 'user'] : [$role])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);
    }

    public function userAuditQuery(array $filters, string $role = 'user'): Builder
    {
        return $this->filteredLogs($filters)
            ->where('actor_role', $role)
            ->where('actor_type', $role === 'admin' ? 'admin' : 'applicant')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function csvSafe(?string $value): string
    {
        $value = (string) $value;

        return preg_match('/^[\s]*[=+\-@]/u', $value) ? "'".$value : $value;
    }

    private function settings(): ?SystemSetting
    {
        if (! $this->settingsLoaded) {
            $this->settings = SystemSetting::query()->first();
            $this->settingsLoaded = true;
        }

        return $this->settings;
    }
}
