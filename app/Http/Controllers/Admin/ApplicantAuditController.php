<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditAction;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\ApplicantAuditQuery;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ApplicantAuditController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:viewApplicantAudit');
    }

    public function index(Request $request, ApplicantAuditQuery $auditQuery): View
    {
        $options = ['periods' => ['all' => 'All Periods'], 'actions' => [], 'statuses' => []];
        $filters = $this->defaultFilters();
        $users = collect();

        try {
            $options = $auditQuery->filterOptions();
            $users = $auditQuery->actorOptions('user');
            $filters = $this->validatedFilters($request, $options);
            $activityQuery = $auditQuery->userAuditQuery($filters);
            $selectedActivity = $request->filled('event_id')
                ? (clone $activityQuery)->whereKey($request->integer('event_id'))->firstOrFail()
                : null;
            $selectedApplicant = $selectedActivity?->applicant;
            $selectedApplication = $selectedActivity?->application;
            $history = $selectedApplicant
                ? $auditQuery->filteredLogs($filters)
                    ->where('applicant_id', $selectedApplicant->id)
                    ->when($selectedApplication, fn (Builder $query) => $query->where('application_id', $selectedApplication->id))
                    ->latest('created_at')
                    ->latest('id')
                    ->paginate(25, ['*'], 'history_page')
                    ->withQueryString()
                : null;
            $statusHistory = $selectedApplicant
                ? $auditQuery->filteredLogs($filters)
                    ->where('applicant_id', $selectedApplicant->id)
                    ->when($selectedApplication, fn (Builder $query) => $query->where('application_id', $selectedApplication->id))
                    ->where('field_name', 'status')
                    ->latest('created_at')
                    ->latest('id')
                    ->get()
                : null;

            return view('admin.applicant-audit.index', [
                'filters' => $filters,
                'options' => $options,
                'users' => $users,
                'activities' => $activityQuery->get(),
                'summary' => $auditQuery->summary($filters),
                'selectedApplicant' => $selectedApplicant,
                'selectedApplication' => $selectedApplication,
                'selectedActivity' => $selectedActivity,
                'history' => $history,
                'statusHistory' => $statusHistory,
                'queryError' => null,
                'periodSettings' => SystemSetting::query()->first(),
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Applicant audit report query failed.', ['exception' => $exception]);

            return view('admin.applicant-audit.index', [
                'filters' => $filters,
                'options' => $options,
                'users' => $users,
                'activities' => null,
                'summary' => null,
                'selectedApplicant' => null,
                'selectedApplication' => null,
                'selectedActivity' => null,
                'history' => null,
                'statusHistory' => null,
                'queryError' => 'Applicant audit data could not be loaded. Please try again later.',
                'periodSettings' => null,
            ]);
        }
    }

    public function show(
        Request $request,
        User $applicant,
        ApplicantAuditQuery $auditQuery,
    ): View {
        abort_unless($applicant->role === 'user', 404);
        $options = ['periods' => ['all' => 'All Periods'], 'actions' => [], 'statuses' => []];
        $filters = $this->defaultFilters();
        $users = collect();
        $applicationId = $request->integer('application_id') ?: null;

        try {
            $options = $auditQuery->filterOptions();
            $filters = $this->validatedFilters($request, $options);
            $users = $auditQuery->actorOptions('user');
            $latestApplication = $applicationId
                ? $applicant->applications()->whereKey($applicationId)->firstOrFail()
                : $applicant->applications()->latest('created_at')->first();
            $logs = $auditQuery->filteredLogs($filters)->where('applicant_id', $applicant->id);

            if ($applicationId) {
                $logs->where('application_id', $applicationId);
            }

            $history = (clone $logs)->latest('created_at')->latest('id')->paginate(25, ['*'], 'history_page')->withQueryString();
            $statusHistory = (clone $logs)->where('field_name', 'status')->latest('created_at')->latest('id')->get();
            $selectedActivity = $request->filled('event_id')
                ? (clone $logs)->whereKey($request->integer('event_id'))->firstOrFail()
                : null;

            return view('admin.applicant-audit.index', [
                'filters' => $filters,
                'options' => $options,
                'users' => $users,
                'activities' => $auditQuery->userAuditQuery($filters)->get(),
                'summary' => $auditQuery->summary($filters),
                'selectedApplicant' => $applicant,
                'selectedApplication' => $latestApplication,
                'selectedActivity' => $selectedActivity,
                'history' => $history,
                'statusHistory' => $statusHistory,
                'queryError' => null,
                'periodSettings' => SystemSetting::query()->first(),
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Applicant audit history query failed.', [
                'applicant_id' => $applicant->id,
                'exception' => $exception,
            ]);

            return view('admin.applicant-audit.index', [
                'filters' => $filters,
                'options' => $options,
                'users' => $users,
                'activities' => null,
                'summary' => null,
                'selectedApplicant' => $applicant,
                'selectedApplication' => null,
                'selectedActivity' => null,
                'history' => null,
                'statusHistory' => null,
                'queryError' => 'Applicant audit history could not be loaded. Please try again later.',
                'periodSettings' => null,
            ]);
        }
    }

    public function event(AuditLog $auditLog): View
    {
        try {
            $auditLog->load(['applicant', 'application', 'user']);
            $statusHistory = $auditLog->applicant_id
                ? AuditLog::query()
                    ->where('applicant_id', $auditLog->applicant_id)
                    ->when($auditLog->application_id, fn (Builder $query) => $query->where('application_id', $auditLog->application_id))
                    ->where('field_name', 'status')
                    ->latest('created_at')
                    ->latest('id')
                    ->get()
                : collect();

            return view('admin.applicant-audit.event', compact('auditLog', 'statusHistory'));
        } catch (Throwable $exception) {
            Log::error('Audit event detail query failed.', [
                'audit_id' => $auditLog->id,
                'exception' => $exception,
            ]);

            return view('admin.applicant-audit.event', [
                'auditLog' => $auditLog,
                'statusHistory' => collect(),
                'queryError' => 'Additional audit detail could not be loaded. Please try again later.',
            ]);
        }
    }

    public function statusHistory(User $applicant, Request $request): View
    {
        abort_unless($applicant->role === 'user', 404);
        $applicationId = $request->integer('application_id') ?: null;
        try {
            $history = AuditLog::query()
                ->with(['user', 'application'])
                ->where('applicant_id', $applicant->id)
                ->where('field_name', 'status')
                ->when($applicationId, fn (Builder $query) => $query->where('application_id', $applicationId))
                ->latest('created_at')
                ->latest('id')
                ->paginate(25, ['*'], 'history_page')
                ->withQueryString();

            return view('admin.applicant-audit.status-history', compact('applicant', 'history'));
        } catch (Throwable $exception) {
            Log::error('Applicant status history query failed.', [
                'applicant_id' => $applicant->id,
                'exception' => $exception,
            ]);

            return view('admin.applicant-audit.status-history', [
                'applicant' => $applicant,
                'history' => null,
                'queryError' => 'Status history could not be loaded. Please try again later.',
            ]);
        }
    }

    public function export(Request $request, ApplicantAuditQuery $auditQuery, AuditLogger $auditLogger): StreamedResponse
    {
        $options = $auditQuery->filterOptions();
        $filters = $this->validatedFilters($request, $options);
        $admin = $request->user();

        return response()->streamDownload(function () use ($auditQuery, $filters, $auditLogger, $admin): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \RuntimeException('Unable to open the audit CSV output stream.');
            }
            fputcsv($output, [
                'Audit Code', 'Applicant', 'Applicant ID', 'Application ID', 'Application Period',
                'Current Status', 'Action', 'Module', 'Field', 'Previous Value', 'New Value',
                'Description', 'Result', 'Performed By', 'Role', 'IP Address', 'Date and Time',
            ]);

            $auditQuery->filteredLogs($filters)
                ->orderBy('id')
                ->chunkById(500, function ($logs) use ($output, $auditQuery): void {
                    foreach ($logs as $log) {
                        $applicant = $log->applicant;
                        $application = $log->application;
                        $row = [
                            $log->audit_code,
                            $applicant?->name ?? $log->actor_name,
                            $log->applicant_id,
                            $log->application_id,
                            $auditQuery->periodName($application?->created_at?->toDateTimeString()),
                            $application?->status,
                            $log->action,
                            $log->module,
                            $log->field_name,
                            $log->old_value,
                            $log->new_value,
                            $log->description,
                            $log->result,
                            $log->actor_name,
                            $log->actor_role,
                            $log->ip_address,
                            $log->created_at?->timezone(config('app.timezone'))->format('F j, Y g:i A'),
                        ];
                        fputcsv($output, array_map(fn ($value) => $auditQuery->csvSafe(
                            $value === null ? null : (string) $value
                        ), $row));
                    }
                });

            fclose($output);
            $auditLogger->record(
                AuditAction::APPLICANT_REPORT_GENERATED,
                'Applicant Audit',
                $admin,
                null,
                null,
                ['description' => 'Applicant audit CSV exported'],
            );
        }, 'applicant-audit-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validatedFilters(Request $request, array $options): array
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'user_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'user')],
            'period' => ['nullable', Rule::in(array_keys($options['periods']))],
            'date_range' => ['nullable', Rule::in(['all', 'today', 'last7', 'last30', 'this_month', 'custom'])],
            'date_from' => ['nullable', 'required_if:date_range,custom', 'date'],
            'date_to' => ['nullable', 'required_if:date_range,custom', 'date', 'after_or_equal:date_from'],
            'action' => ['nullable', Rule::in(array_keys($options['actions']))],
            'status' => ['nullable', Rule::in(array_keys($options['statuses']))],
        ]);

        return array_merge($this->defaultFilters(), $filters);
    }

    private function defaultFilters(): array
    {
        return [
            'search' => '',
            'user_id' => null,
            'period' => 'all',
            'date_range' => 'all',
            'date_from' => null,
            'date_to' => null,
            'action' => null,
            'status' => null,
        ];
    }
}
