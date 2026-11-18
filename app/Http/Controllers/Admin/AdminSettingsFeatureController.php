<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAdminBackupOperation;
use App\Models\AdminBackupOperation;
use App\Models\AuditAction;
use App\Models\PersonalizedSecurityVerification;
use App\Models\SystemSetting;
use App\Services\AdminBackupService;
use App\Services\ApplicantAuditQuery;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminSettingsFeatureController extends Controller
{
    public function preferences(Request $request, ApplicantAuditQuery $auditQuery, AdminBackupService $backupService): View
    {
        $preference = $request->user()->adminPreference;
        $administrators = $auditQuery->actorOptions('admin');
        $filters = $request->validate([
            'user_id' => ['nullable', Rule::in($administrators->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'date_range' => ['nullable', Rule::in(['all', 'today', 'last7', 'last30', 'this_month', 'custom'])],
            'date_from' => ['nullable', 'required_if:date_range,custom', 'date'],
            'date_to' => ['nullable', 'required_if:date_range,custom', 'date', 'after_or_equal:date_from'],
        ]);
        $filters = array_merge([
            'user_id' => null,
            'date_range' => 'all',
            'date_from' => null,
            'date_to' => null,
        ], $filters);
        $adminAuditLogs = $auditQuery->userAuditQuery($filters, 'admin')
            ->get();
        $backupHistory = $backupService->backupHistory();
        $automaticBackupStatus = $backupService->automaticBackupStatus();
        $backupSettings = SystemSetting::current();
        $backupStorage = $backupService->backupStorageSummary();
        $failedBackupCount = AdminBackupOperation::query()
            ->where('status', 'failed')
            ->where('updated_at', '>=', now()->subDays(7))
            ->count();
        $recentBackupFailures = AdminBackupOperation::query()
            ->where('status', 'failed')
            ->latest('updated_at')
            ->limit(5)
            ->get();
        $backupOperationId = $request->session()->get('backup_operation_id');
        $backupOperation = is_string($backupOperationId)
            ? AdminBackupOperation::query()
                ->where('admin_id', $request->user()->id)
                ->find($backupOperationId)
            : null;
        $personalSecurityVerification = PersonalizedSecurityVerification::query()
            ->where('user_id', $request->user()->id)
            ->whereNotNull('enabled_at')
            ->first();

        return view('admin.settings-features.index', compact(
            'preference',
            'administrators',
            'filters',
            'adminAuditLogs',
            'backupHistory',
            'automaticBackupStatus',
            'backupSettings',
            'backupStorage',
            'failedBackupCount',
            'recentBackupFailures',
            'backupOperation',
            'personalSecurityVerification',
        ));
    }

    public function updatePreferences(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        if (! Schema::hasColumns('admin_preferences', ['language', 'sidebar_behavior', 'font_size'])) {
            Log::warning('Admin preferences could not be saved because their migration is pending.', [
                'admin_id' => $request->user()->id,
            ]);

            return back()->withErrors([
                'preferences' => __('Admin preference settings are not ready yet. Apply the pending database migrations, then try again.'),
            ])->withInput();
        }

        $validated = $request->validate([
            'appearance' => ['required', Rule::in(['light', 'dark', 'system'])],
            'language' => ['required', Rule::in(['en', 'fil'])],
            'sidebar_behavior' => ['required', Rule::in(['auto', 'expanded', 'collapsed'])],
            'font_size' => ['required', Rule::in(['small', 'medium', 'large'])],
        ], [
            'appearance.required' => __('Choose an appearance option.'),
            'appearance.in' => __('Choose a valid appearance option.'),
            'language.required' => __('Choose a language.'),
            'language.in' => __('Choose a valid language.'),
            'sidebar_behavior.required' => __('Choose a sidebar behavior.'),
            'sidebar_behavior.in' => __('Choose a valid sidebar behavior.'),
            'font_size.required' => __('Choose a display size.'),
            'font_size.in' => __('Choose a valid display size.'),
        ]);

        $preference = $request->user()->adminPreference()->firstOrNew();
        $oldValues = [
            'theme' => $preference->theme ?? 'system',
            'language' => $preference->language ?? 'en',
            'sidebar_behavior' => $preference->sidebar_behavior ?? 'auto',
            'font_size' => $preference->font_size ?? 'medium',
        ];
        $preferences = [
            'theme' => $validated['appearance'],
            'language' => $validated['language'],
            'sidebar_behavior' => $validated['sidebar_behavior'],
            'font_size' => $validated['font_size'],
        ];

        DB::transaction(function () use ($request, $preferences, $oldValues, $auditLogger): void {
            $request->user()->adminPreference()->updateOrCreate(
                ['user_id' => $request->user()->id],
                $preferences,
            );
            $auditLogger->recordChanges(
                $oldValues,
                $preferences,
                'Admin Preferences',
                $request->user(),
                null,
                null,
                AuditAction::ADMIN_PREFERENCES_UPDATED,
            );
        });

        return redirect()->route('admin.preferences')
            ->withFragment('preferences')
            ->with('success', __('Your admin preferences have been saved.'));
    }

    public function manual(): RedirectResponse
    {
        return redirect()->route('admin.preferences')->withFragment('manual');
    }

    public function security(): RedirectResponse
    {
        return redirect()->route('admin.preferences')->withFragment('admin-audit');
    }

    public function updateSecurity(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'admin_minimum_password_length' => ['required', 'integer', 'min:8', 'max:32'],
            'admin_session_timeout_minutes' => ['required', 'integer', 'min:5', 'max:240'],
        ]);

        $settings = SystemSetting::current();
        $oldValues = [
            'admin_min_length' => $settings->admin_minimum_password_length ?: 12,
            'admin_session_timeout_minutes' => $settings->admin_session_timeout_minutes,
        ];
        $newValues = [
            'admin_min_length' => $validated['admin_minimum_password_length'],
            'admin_session_timeout_minutes' => $validated['admin_session_timeout_minutes'],
        ];

        DB::transaction(function () use ($settings, $validated, $oldValues, $newValues, $request, $auditLogger): void {
            $settings->update($validated);
            $auditLogger->recordChanges(
                $oldValues,
                $newValues,
                'Admin Security',
                $request->user(),
                null,
                null,
                AuditAction::SECURITY_POLICY_UPDATED,
            );
        });

        return redirect()->route('admin.preferences')
            ->withFragment('admin-audit')
            ->with('success', __('Admin security policies have been updated.'));
    }

    public function backup(): RedirectResponse
    {
        return redirect()->route('admin.preferences')->withFragment('backup');
    }

    public function updateBackupSettings(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        if (! Schema::hasColumns('system_settings', [
            'backup_enabled',
            'backup_frequency',
            'backup_time',
            'backup_retention_days',
        ])) {
            Log::warning('Automatic backup settings could not be saved because their migration is pending.', [
                'admin_id' => $request->user()->id,
            ]);

            return back()->withErrors([
                'backup_settings' => __('Automatic backup settings are not ready yet. Apply the pending database migrations, then try again.'),
            ])->withInput();
        }

        $validated = $request->validate([
            'backup_enabled' => ['required', 'boolean'],
            'backup_frequency' => ['required', Rule::in(['daily', 'weekly'])],
            'backup_time' => ['required', 'date_format:H:i'],
            'backup_retention_days' => ['required', 'integer', Rule::in([7, 14, 30, 90])],
        ]);
        $validated['backup_enabled'] = (bool) $validated['backup_enabled'];
        $settings = SystemSetting::current();
        $oldValues = [
            'backup_enabled' => $settings->backup_enabled,
            'backup_frequency' => $settings->backup_frequency,
            'backup_time' => $settings->backup_time,
            'backup_retention_days' => $settings->backup_retention_days,
        ];

        DB::transaction(function () use ($settings, $validated, $oldValues, $auditLogger, $request): void {
            $settings->update($validated);
            $auditLogger->recordChanges(
                $oldValues,
                $validated,
                'System Backup',
                $request->user(),
                null,
                null,
                AuditAction::BACKUP_SETTINGS_UPDATED,
            );
        });

        return redirect()->route('admin.preferences')
            ->withFragment('backup')
            ->with('success', __('Automatic backup settings have been saved.'));
    }

    public function downloadBackup(Request $request): RedirectResponse
    {
        $operation = $this->queueBackupOperation($request, 'create');

        return $this->backupOperationRedirect($operation, __('Backup creation is queued. This page will show when it is ready.'));
    }

    public function restoreBackup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'backup' => ['required', 'file', 'mimes:zip', 'max:1048576'],
            'current_password' => ['required', 'current_password'],
        ], [
            'backup.mimes' => __('Choose a valid SPES backup ZIP archive.'),
            'backup.required' => __('Choose a backup ZIP archive to restore.'),
            'current_password.required' => __('Enter your current password to restore this backup.'),
            'current_password.current_password' => __('The current password is incorrect.'),
            'backup.max' => __('The backup ZIP must not exceed 1 GB.'),
        ]);

        $operationId = (string) Str::uuid();
        $archivePath = $validated['backup']->storeAs(
            'backups/operations/uploads',
            $operationId.'.zip',
            'local',
        );
        if (! is_string($archivePath)) {
            throw new \RuntimeException('Unable to store the uploaded backup archive.');
        }

        $operation = $this->queueBackupOperation($request, 'restore', $archivePath, $operationId);

        return $this->backupOperationRedirect($operation, __('Backup restore is queued. This page will show the result when it is ready.'));
    }

    public function downloadAnnualBackup(Request $request, int $year, AdminBackupService $backupService): StreamedResponse
    {
        $archive = $backupService->annualArchive($year);
        abort_if($archive === null, 404);
        try {
            $backupService->verifyStoredArchive($archive['path'], 'annual', $year);
        } catch (\RuntimeException $exception) {
            Log::warning('Annual backup download was denied because the archive failed verification.', [
                'year' => $year,
                'exception' => $exception,
            ]);
            abort(404);
        }

        app(AuditLogger::class)->record(
            AuditAction::DATABASE_BACKUP_DOWNLOADED,
            'System Backup',
            $request->user(),
            null,
            null,
            ['description' => "Annual backup for {$year} downloaded"],
        );

        return Storage::disk('local')->download($archive['path'], basename($archive['path']), [
            'Content-Type' => 'application/zip',
        ]);
    }

    public function downloadVerifiedBackup(Request $request, string $credential, AdminBackupService $backupService): StreamedResponse
    {
        try {
            $archive = $backupService->verifiedArchiveByCredential($credential);
        } catch (\RuntimeException $exception) {
            Log::warning('A stored backup download was denied because the archive failed verification.', [
                'credential_id' => $credential,
                'exception' => $exception,
            ]);
            abort(404);
        }
        abort_if($archive === null, 404);

        app(AuditLogger::class)->record(
            AuditAction::DATABASE_BACKUP_DOWNLOADED,
            'System Backup',
            $request->user(),
            null,
            null,
            ['description' => 'Verified '.ucfirst($archive['type']).' backup archive downloaded'],
        );

        return Storage::disk('local')->download($archive['path'], $archive['filename'], [
            'Content-Type' => 'application/zip',
        ]);
    }

    public function restoreAnnualBackup(Request $request, int $year, AdminBackupService $backupService): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ], [
            'current_password.required' => __('Enter your current password to restore this backup.'),
            'current_password.current_password' => __('The current password is incorrect.'),
        ]);

        $archive = $backupService->annualArchive($year);
        abort_if($archive === null, 404);

        $operation = $this->queueBackupOperation(
            $request,
            'restore',
            $archive['path'],
            (string) Str::uuid(),
            'annual',
            $year,
            false,
        );

        return $this->backupOperationRedirect(
            $operation,
            __('Annual backup :year restore is queued. This page will show the result when it is ready.', ['year' => $year]),
        );
    }

    public function backupOperationStatus(Request $request, AdminBackupOperation $operation): JsonResponse
    {
        abort_unless((int) $operation->admin_id === (int) $request->user()->id, 404);

        return response()->json([
            'status' => $operation->status,
            'action' => $operation->action,
            'message' => match ($operation->status) {
                'queued' => __('Backup operation is starting.'),
                'processing' => __('Backup operation is processing.'),
                'verifying' => __('Verifying the backup archive.'),
                'completed' => ($operation->result['expired'] ?? false)
                    ? __('This backup has expired under the configured retention policy.')
                    : ($operation->result['message'] ?? __('Backup is ready to download.')),
                'failed' => $operation->failure_message,
                default => __('The operation status is unavailable.'),
            },
            'download_url' => $operation->status === 'completed'
                && $operation->action === 'create'
                && is_string($operation->archive_path)
                && Storage::disk('local')->exists($operation->archive_path)
                ? route('admin.backup.operations.download', $operation)
                : null,
        ]);
    }

    public function downloadBackupOperation(
        Request $request,
        AdminBackupOperation $operation,
        AdminBackupService $backupService,
    ): StreamedResponse
    {
        abort_unless(
            (int) $operation->admin_id === (int) $request->user()->id
            && $operation->status === 'completed'
            && $operation->action === 'create'
            && $operation->verified_at !== null
            && is_string($operation->archive_path)
            && Storage::disk('local')->exists($operation->archive_path),
            404,
        );
        try {
            $backupService->verifyStoredArchive($operation->archive_path, $operation->archive_type ?? 'manual');
        } catch (\RuntimeException $exception) {
            Log::warning('A backup operation download was denied because the archive failed verification.', [
                'operation_id' => $operation->id,
                'exception' => $exception,
            ]);
            abort(404);
        }

        app(AuditLogger::class)->record(
            AuditAction::DATABASE_BACKUP_DOWNLOADED,
            'System Backup',
            $request->user(),
            null,
            null,
            ['description' => 'Generated database and stored-file backup archive downloaded'],
        );

        return Storage::disk('local')->download(
            $operation->archive_path,
            $operation->result['filename'] ?? basename($operation->archive_path),
            ['Content-Type' => 'application/zip'],
        );
    }

    private function queueBackupOperation(
        Request $request,
        string $action,
        ?string $archivePath = null,
        ?string $operationId = null,
        ?string $archiveType = null,
        ?int $archiveYear = null,
        bool $removeArchiveOnFailure = true,
    ): AdminBackupOperation {
        $operationId ??= (string) Str::uuid();

        try {
            $operation = DB::transaction(function () use (
                $request,
                $action,
                $archivePath,
                $operationId,
                $archiveType,
                $archiveYear,
            ): AdminBackupOperation {
                $operation = AdminBackupOperation::query()->create([
                    'id' => $operationId,
                    'admin_id' => $request->user()->id,
                    'action' => $action,
                    'status' => 'queued',
                    'archive_path' => $archivePath,
                    'original_filename' => $request->file('backup')?->getClientOriginalName(),
                    'archive_type' => $archiveType ?? ($action === 'create' ? 'manual' : null),
                    'archive_year' => $archiveYear,
                ]);

                return $operation;
            });
        } catch (\Throwable $exception) {
            if ($removeArchiveOnFailure && $archivePath !== null) {
                Storage::disk('local')->delete($archivePath);
            }

            throw $exception;
        }

        try {
            if (config('backup.execution_mode', 'sync') === 'queue') {
                ProcessAdminBackupOperation::dispatch($operation->id);
            } else {
                (new ProcessAdminBackupOperation($operation->id))->handle(
                    app(AdminBackupService::class),
                    app(AuditLogger::class),
                );
            }
        } catch (\Throwable $exception) {
            (new ProcessAdminBackupOperation($operation->id))->failed($exception);
        }

        return $operation->fresh();
    }

    private function backupOperationRedirect(AdminBackupOperation $operation, string $message): RedirectResponse
    {
        $operation->refresh();
        if ($operation->status === 'completed') {
            $message = $operation->action === 'create'
                ? __('Backup created and verified. Download it from Backup History.')
                : ($operation->result['message'] ?? __('Backup restore completed.'));
        } elseif ($operation->status === 'failed') {
            $message = $operation->failure_message ?? __('The backup operation failed.');
        } elseif (config('backup.execution_mode', 'sync') === 'queue') {
            $message = __('Backup operation started and will appear in Backup History when complete.');
        }

        return redirect()->route('admin.preferences')
            ->withFragment('backup')
            ->with('backup_operation_id', $operation->id)
            ->with($operation->status === 'failed' ? 'error' : 'success', $message);
    }
}
