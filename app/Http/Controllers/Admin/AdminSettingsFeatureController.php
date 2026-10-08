<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditAction;
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
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

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
        $showAllAdminAuditLogs = $request->boolean('show_all_admin_audit_logs');
        $adminAuditLogs = $auditQuery->userAuditQuery($filters, 'admin')
            ->paginate($showAllAdminAuditLogs ? 20 : 5, ['*'], 'admin_audit_page')
            ->withQueryString()
            ->fragment('admin-audit');
        $annualBackups = $backupService->annualArchives();

        return view('admin.settings-features.index', compact(
            'preference',
            'administrators',
            'filters',
            'adminAuditLogs',
            'showAllAdminAuditLogs',
            'annualBackups',
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

    public function downloadBackup(AdminBackupService $backupService): BinaryFileResponse|RedirectResponse
    {
        $archivePath = null;
        try {
            $archivePath = $backupService->create('manual', null, request()->user()->id);
            app(AuditLogger::class)->record(
                AuditAction::DATABASE_BACKUP_GENERATED,
                'System Backup',
                request()->user(),
                null,
                null,
                ['description' => 'Database and stored-file backup archive generated'],
            );

            return response()->download(
                $archivePath,
                'spes-backup-'.now()->format('Ymd-His').'.zip',
                ['Content-Type' => 'application/zip'],
            )->deleteFileAfterSend(true);
        } catch (Throwable $exception) {
            if (is_string($archivePath) && is_file($archivePath)) {
                @unlink($archivePath);
            }
            Log::error('Admin backup generation failed.', ['exception' => $exception]);

            return back()->withErrors([
                'backup' => __('The backup could not be created. Check the application log or contact the system administrator.'),
            ]);
        }
    }

    public function restoreBackup(Request $request, AdminBackupService $backupService): RedirectResponse
    {
        $validated = $request->validate([
            'backup' => ['required', 'file', 'mimes:zip', 'max:1048576'],
            'current_password' => ['required', 'current_password'],
            'confirmation' => ['required', Rule::in(['MERGE'])],
        ], [
            'backup.mimes' => __('Choose a valid SPES backup ZIP archive.'),
            'backup.required' => __('Choose a backup ZIP archive to restore.'),
            'confirmation.in' => __('Type MERGE exactly to confirm the restore.'),
            'confirmation.required' => __('Type MERGE to confirm the restore.'),
            'current_password.required' => __('Enter your current password to restore this backup.'),
            'current_password.current_password' => __('The current password is incorrect.'),
            'backup.max' => __('The backup ZIP must not exceed 1 GB.'),
        ]);

        try {
            $result = $backupService->restore($validated['backup']->getRealPath());
        } catch (Throwable $exception) {
            Log::error('Admin backup restore failed.', ['exception' => $exception]);

            return back()->withFragment('backup')->withErrors([
                'backup' => __('The backup could not be restored. No existing records or files were intentionally overwritten. Check the application log or contact the system administrator.'),
            ]);
        }

        return $this->completeBackupRestore($request, $result);
    }

    public function downloadAnnualBackup(int $year, AdminBackupService $backupService): StreamedResponse
    {
        $archive = $backupService->annualArchive($year);
        abort_if($archive === null, 404);

        app(AuditLogger::class)->record(
            AuditAction::DATABASE_BACKUP_DOWNLOADED,
            'System Backup',
            request()->user(),
            null,
            null,
            ['description' => "Annual backup for {$year} downloaded"],
        );

        return Storage::disk('local')->download($archive['path'], basename($archive['path']), [
            'Content-Type' => 'application/zip',
        ]);
    }

    public function restoreAnnualBackup(Request $request, int $year, AdminBackupService $backupService): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'confirmation' => ['required', Rule::in(["RESTORE {$year}"])],
        ], [
            'current_password.required' => __('Enter your current password to restore this backup.'),
            'confirmation.in' => __('Type RESTORE :year exactly to confirm the restore.', ['year' => $year]),
            'confirmation.required' => __('Type RESTORE :year to confirm the restore.', ['year' => $year]),
            'current_password.current_password' => __('The current password is incorrect.'),
        ]);

        $archive = $backupService->annualArchive($year);
        abort_if($archive === null, 404);

        try {
            $result = $backupService->restore(Storage::disk('local')->path($archive['path']), 'annual', $year);
        } catch (Throwable $exception) {
            Log::error('Admin annual backup restore failed.', ['year' => $year, 'exception' => $exception]);

            return back()->withFragment('backup')->withErrors([
                'backup' => __('The :year annual backup could not be restored. Check the application log or contact the system administrator.', ['year' => $year]),
            ]);
        }

        return $this->completeBackupRestore($request, $result, __('Annual backup :year', ['year' => $year]));
    }

    private function completeBackupRestore(Request $request, array $result, ?string $source = null): RedirectResponse
    {
        $source ??= __('Uploaded backup');
        try {
            app(AuditLogger::class)->record(
                AuditAction::DATABASE_BACKUP_RESTORED,
                'System Backup',
                $request->user(),
                null,
                null,
                [
                    'description' => __(':source merged: :rows database rows and :files files added', [
                        'source' => $source,
                        'rows' => $result['rows'],
                        'files' => $result['files'],
                    ]),
                ],
            );
        } catch (Throwable $exception) {
            Log::critical('Backup data was restored, but its audit entry could not be recorded.', [
                'admin_id' => $request->user()->id,
                'exception' => $exception,
            ]);

            return redirect()->route('admin.preferences')->withFragment('backup')->with('success', __('Backup data was merged, but its audit entry could not be saved. Notify the system administrator.'));
        }

        return redirect()->route('admin.preferences')->withFragment('backup')
            ->with('success', __('Backup merged successfully: :rows new database rows and :files files added. Existing records and files were kept.', [
                'rows' => $result['rows'],
                'files' => $result['files'],
            ]));
    }
}
