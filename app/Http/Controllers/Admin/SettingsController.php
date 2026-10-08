<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditAction;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Services\ApplicationApprovalCapacity;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    /**
     * Show the settings edit form
     */
    public function edit(ApplicationApprovalCapacity $capacity)
    {
        $settings = SystemSetting::current();
        $applicationPeriodAudits = AuditLog::query()
            ->with('user:id,name')
            ->where('module', 'Application Period')
            ->latest('created_at')
            ->limit(30)
            ->get();
        $applicationPeriodStatistics = $this->applicationPeriodStatistics();
        $approvedApplicantCount = $capacity->approvedCount($settings);
        $approvalLimitReached = $capacity->isFull($settings);

        return view('admin.settings', compact(
            'settings',
            'applicationPeriodAudits',
            'applicationPeriodStatistics',
            'approvedApplicantCount',
            'approvalLimitReached',
        ));
    }

    /**
     * Update the system settings
     */
    public function update(
        Request $request,
        AuditLogger $auditLogger,
        ApplicationApprovalCapacity $capacity,
    )
    {
        $validated = $request->validate([
            'application_start_date' => 'nullable|date_format:Y-m-d\TH:i',
            'application_end_date' => 'nullable|date_format:Y-m-d\TH:i',
            'approved_applicant_limit' => 'nullable|integer|min:1|max:100000',
        ]);
        $validated = array_merge([
            'application_start_date' => null,
            'application_end_date' => null,
            'approved_applicant_limit' => null,
        ], $validated);

        // Additional validation: end date must be after start date if both are provided
        if ($validated['application_start_date'] && $validated['application_end_date']) {
            if ($validated['application_end_date'] <= $validated['application_start_date']) {
                return back()
                    ->withErrors(['application_end_date' => 'End date must be after the start date.'])
                    ->withInput();
            }
        }

        $settings = SystemSetting::current();
        $pendingApplications = collect();
        $oldValues = [
            'application_start_date' => $settings->application_start_date?->format('Y-m-d H:i:s'),
            'application_end_date' => $settings->application_end_date?->format('Y-m-d H:i:s'),
            'approved_applicant_limit' => $settings->approved_applicant_limit,
        ];
        $auditValues = [
            'application_start_date' => $validated['application_start_date'] === null
                ? null
                : Carbon::createFromFormat('Y-m-d\TH:i', $validated['application_start_date']),
            'application_end_date' => $validated['application_end_date'] === null
                ? null
                : Carbon::createFromFormat('Y-m-d\TH:i', $validated['application_end_date']),
            'approved_applicant_limit' => $validated['approved_applicant_limit'],
        ];

        DB::transaction(function () use ($settings, $validated, $oldValues, $auditValues, $auditLogger, $request, $capacity, &$pendingApplications): void {
            $settings->update($validated);
            $auditLogger->recordChanges(
                $oldValues,
                $auditValues,
                'Application Period',
                $request->user(),
                null,
                null,
                AuditAction::APPLICATION_PERIOD_UPDATED,
                ['description' => 'Application submission schedule updated'],
            );
            $pendingApplications = $capacity->denyRemainingPending($settings->fresh(), $request->user(), $auditLogger);
        });

        $settings = $settings->fresh();
        if ($pendingApplications->isNotEmpty()) {
            $capacity->notifyClosedApplicants($pendingApplications, (int) $settings->approved_applicant_limit);
        }

        if ($capacity->isFull($settings)) {
            return back()
                ->with('success', 'Application period and approval limit updated successfully.')
                ->with('approval_limit_notice', 'The approved-applicant limit has been reached. New submissions and approvals are closed, and remaining pending applicants were notified.');
        }

        return back()->with('success', 'Application period and approval limit updated successfully.');
    }

    private function applicationPeriodStatistics(): array
    {
        $statistics = [
            'application_start_date' => ['earlier_count' => 0, 'later_count' => 0, 'earlier_seconds' => 0, 'later_seconds' => 0],
            'application_end_date' => ['earlier_count' => 0, 'later_count' => 0, 'earlier_seconds' => 0, 'later_seconds' => 0],
        ];

        AuditLog::query()
            ->where('module', 'Application Period')
            ->whereIn('field_name', array_keys($statistics))
            ->whereNotNull('old_value')
            ->whereNotNull('new_value')
            ->select(['id', 'field_name', 'old_value', 'new_value'])
            ->chunkById(500, function ($logs) use (&$statistics): void {
                foreach ($logs as $log) {
                    $difference = Carbon::parse($log->new_value)->getTimestamp()
                        - Carbon::parse($log->old_value)->getTimestamp();
                    if ($difference === 0) {
                        continue;
                    }

                    $direction = $difference < 0 ? 'earlier' : 'later';
                    $statistics[$log->field_name][$direction.'_count']++;
                    $statistics[$log->field_name][$direction.'_seconds'] += abs($difference);
                }
            });

        return $statistics;
    }
}
