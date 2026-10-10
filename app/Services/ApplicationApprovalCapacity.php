<?php

namespace App\Services;

use App\Models\Application;
use App\Models\AuditAction;
use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\ApplicantPortalUpdate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ApplicationApprovalCapacity
{
    public function approvedCount(?SystemSetting $settings = null): int
    {
        $settings ??= SystemSetting::current();

        return $this->currentSeasonApplications($settings)
            ->where('status', 'approved')
            ->count();
    }

    public function isFull(?SystemSetting $settings = null): bool
    {
        $settings ??= SystemSetting::current();
        $limit = $settings->approved_applicant_limit;

        return $limit !== null && $this->approvedCount($settings) >= $limit;
    }

    public function remaining(?SystemSetting $settings = null): ?int
    {
        $settings ??= SystemSetting::current();
        $limit = $settings->approved_applicant_limit;

        return $limit === null ? null : max(0, $limit - $this->approvedCount($settings));
    }

    public function currentSeasonApplications(?SystemSetting $settings = null): Builder
    {
        $settings ??= SystemSetting::current();
        $query = Application::query();
        $start = $settings->application_start_date;
        $end = $settings->application_end_date;

        if ($start && $end) {
            return $query->whereBetween('created_at', [$start, $end]);
        }
        if ($start) {
            return $query->where('created_at', '>=', $start);
        }
        if ($end) {
            return $query->where('created_at', '<=', $end);
        }

        return $query->whereYear('created_at', now()->year);
    }

    public function denyRemainingPending(
        SystemSetting $settings,
        User $actor,
        AuditLogger $auditLogger,
    ): Collection {
        if (! $this->isFull($settings)) {
            return collect();
        }

        $pending = $this->currentSeasonApplications($settings)
            ->where('status', 'pending')
            ->with('user')
            ->lockForUpdate()
            ->get();

        foreach ($pending as $application) {
            $application->update(['status' => 'denied']);
            $auditLogger->record(
                AuditAction::APPLICATION_REJECTED,
                'Application',
                $actor,
                $application->user,
                $application,
                [
                    'field_name' => 'status',
                    'old_value' => 'pending',
                    'new_value' => 'denied',
                    'description' => 'Automatically denied because the approved-applicant limit was reached.',
                ],
            );
        }

        return $pending;
    }

    public function notifyClosedApplicants(
        Collection $applications,
        int $limit,
        ApplicantNotificationService $notifications,
    ): void
    {
        foreach ($applications as $application) {
            $notifications->notifyApplicant($application->user, new ApplicantPortalUpdate(
                'notify_documents',
                'SPES application period closed',
                "The SPES program has reached its limit of {$limit} approved applicants. Your application was not included in the approved list. Please try again next SPES season.",
                [
                    'application_id' => $application->id,
                    'status' => 'denied',
                    'event' => 'approval_limit_reached',
                    'approved_applicant_limit' => $limit,
                ],
            ), "application:{$application->id}:approval-limit-reached");
        }
    }
}
