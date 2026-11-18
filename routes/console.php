<?php

use App\Jobs\ProcessAdminBackupOperation;
use App\Models\AdminBackupOperation;
use App\Models\Appointment;
use App\Models\AuditAction;
use App\Models\PendingRegistration;
use App\Models\SystemSetting;
use App\Notifications\ApplicantPortalUpdate;
use App\Services\AdminBackupService;
use App\Services\ApplicantNotificationService;
use App\Services\AuditLogger;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('spes:move-documents-to-private', function (): void {
    $public = Storage::disk('public');
    $private = Storage::disk('local');
    $moved = 0;
    $skipped = 0;

    foreach ($public->allFiles('applications') as $path) {
        if ($private->exists($path)) {
            $skipped++;

            continue;
        }

        $stream = $public->readStream($path);
        if ($stream === false) {
            throw new RuntimeException("Unable to read public application document [{$path}].");
        }

        try {
            $written = $private->writeStream($path, $stream);
        } finally {
            fclose($stream);
        }

        if (! $written) {
            throw new RuntimeException("Unable to write private application document [{$path}].");
        }
        if (! $public->delete($path)) {
            throw new RuntimeException("Private copy created, but public application document [{$path}] could not be removed.");
        }

        $moved++;
    }

    $this->info("Application documents moved to private storage: {$moved}; skipped because private copies already exist: {$skipped}.");
})->purpose('Move existing public application documents to private storage');

Artisan::command('appointments:send-reminders', function (ApplicantNotificationService $notifications): void {
    $windowBase = now();
    $windowStart = $windowBase->copy()->addHours(23);
    $windowEnd = $windowBase->copy()->addHours(24);

    Appointment::query()
        ->where('is_published', true)
        ->whereBetween('starts_at', [$windowStart, $windowEnd])
        ->orderBy('id')
        ->each(function (Appointment $appointment) use ($notifications): void {
            $startsAt = $appointment->starts_at;
            $details = $startsAt->format('M j, Y g:i A');
            if ($appointment->location) {
                $details .= ' at '.$appointment->location;
            }

            $notifications->notifyUsers(
                $appointment->recipientUsers(),
                new ApplicantPortalUpdate(
                    'notify_appointments',
                    'Appointment reminder',
                    "This is a reminder of your SPES appointment, \"{$appointment->title}\", on {$details}.",
                    [
                        'appointment_id' => $appointment->id,
                        'appointment_date' => $startsAt->toIso8601String(),
                        'appointment_location' => $appointment->location,
                        'event' => 'scheduled_appointment_reminder',
                    ],
                ),
                "appointment:{$appointment->id}:reminder:{$startsAt->getTimestamp()}",
            );
        });
})->purpose('Send eligible applicant reminders for appointments approximately 24 hours away');

Schedule::command('appointments:send-reminders')
    ->hourlyAt(0)
    ->timezone(config('app.timezone'))
    ->name('spes-appointment-reminders')
    ->withoutOverlapping();

Artisan::command('appointments:send-day-of-notices', function (ApplicantNotificationService $notifications): void {
    $today = now(config('app.timezone'));

    Appointment::query()
        ->where('is_published', true)
        ->whereDate('starts_at', $today->toDateString())
        ->orderBy('id')
        ->each(function (Appointment $appointment) use ($notifications): void {
            $startsAt = $appointment->starts_at;
            $message = 'Your SPES appointment is scheduled for today: '.$appointment->title
                .' on '.$startsAt->format('M j, Y g:i A').'.';
            if (filled($appointment->location)) {
                $message .= ' Location: '.$appointment->location.'.';
            }
            if (filled($appointment->description)) {
                $message .= ' Details: '.$appointment->description;
            }

            $notifications->notifyUsers(
                $appointment->recipientUsers(),
                new ApplicantPortalUpdate(
                    'notify_appointments',
                    'Appointment today: '.$appointment->title,
                    $message,
                    [
                        'appointment_id' => $appointment->id,
                        'appointment_title' => $appointment->title,
                        'appointment_description' => $appointment->description,
                        'appointment_date' => $startsAt->format('M j, Y g:i A'),
                        'appointment_location' => $appointment->location,
                        'event' => 'appointment_day_of',
                    ],
                ),
                "appointment:{$appointment->id}:day-of:{$startsAt->getTimestamp()}",
            );
        });
})->purpose('Notify each eligible applicant by email and in-app on the day of their appointment');

Schedule::command('appointments:send-day-of-notices')
    ->hourlyAt(5)
    ->timezone(config('app.timezone'))
    ->name('spes-appointment-day-of-notices')
    ->withoutOverlapping();

Schedule::call(function () {
    PendingRegistration::where('expires_at', '<=', now())->delete();
})->hourly()->name('clean-expired-pending-registrations')->withoutOverlapping();

Artisan::command('backup:annual', function (AdminBackupService $backupService): void {
    $year = (int) now(config('backup.timezone', 'Asia/Manila'))->year;
    $existingArchive = $backupService->annualArchive($year);
    if ($existingArchive !== null) {
        $backupService->verifyStoredArchive($existingArchive['path'], 'annual', $year);
        $this->info("The annual backup for {$year} already exists and is verified; no archive was replaced.");

        return;
    }

    $startedAt = microtime(true);
    $operation = AdminBackupOperation::query()->create([
        'id' => (string) Str::uuid(),
        'admin_id' => null,
        'action' => 'create',
        'status' => 'processing',
        'archive_type' => 'annual',
        'original_filename' => "scheduled-annual-{$year}",
        'started_at' => now(),
    ]);

    try {
        $archive = $backupService->createAnnualArchive($year);
        if ($archive === null) {
            $existingArchive = $backupService->annualArchive($year);
            if ($existingArchive === null) {
                throw new RuntimeException('The annual backup completed without a readable archive.');
            }
            $backupService->verifyStoredArchive($existingArchive['path'], 'annual', $year);
            $archive = $existingArchive;
        }

        $backupService->verifyStoredArchive($archive['path'], 'annual', $year);
        $operation->update([
            'status' => 'completed',
            'archive_path' => $archive['path'],
            'file_size_bytes' => $archive['size'],
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'completed_at' => now(),
            'verified_at' => now(),
            'result' => ['filename' => basename($archive['path'])],
        ]);
        try {
            app(AuditLogger::class)->record(
                AuditAction::DATABASE_BACKUP_GENERATED,
                'System Backup',
                null,
                null,
                null,
                ['description' => "Annual backup for {$year} saved to private archive storage"],
            );
        } catch (Throwable $auditException) {
            Log::critical('Annual backup completed, but its audit entry could not be recorded.', [
                'operation_id' => $operation->id,
                'exception' => $auditException,
            ]);
        }

        $this->info("Annual backup for {$year} saved to private archive storage.");
    } catch (Throwable $exception) {
        $operation->update([
            'status' => 'failed',
            'completed_at' => now(),
            'failure_message' => __('The backup operation could not be completed. Contact the system administrator if the problem continues.'),
        ]);
        try {
            app(AuditLogger::class)->record(
                AuditAction::DATABASE_BACKUP_FAILED,
                'System Backup',
                null,
                null,
                null,
                [
                    'description' => "Annual backup for {$year} failed.",
                    'operation_id' => $operation->id,
                ],
            );
        } catch (Throwable $auditException) {
            Log::critical('Annual backup failure could not be recorded in the administrator audit log.', [
                'operation_id' => $operation->id,
                'exception' => $auditException,
            ]);
        }
        Log::error('Annual SPES backup failed.', ['year' => $year, 'exception' => $exception]);
        throw $exception;
    }
})->purpose('Create the yearly SPES system backup archive');

Artisan::command('backup:daily', function (AdminBackupService $backupService): void {
    $lock = Cache::lock('admin-backup-daily-scheduler', 300);
    if (! $lock->get()) {
        $this->info('A daily backup scheduling operation is already in progress.');

        return;
    }

    try {
        $date = now(config('backup.timezone', 'Asia/Manila'))->format('Ymd');
        $marker = 'scheduled-daily-'.$date;
        if ($backupService->verifiedDailyArchiveExists()) {
            $this->info('A daily backup already exists for today.');

            return;
        }
        $alreadyQueued = AdminBackupOperation::query()
            ->where('action', 'create')
            ->where('archive_type', 'daily')
            ->where('original_filename', $marker)
            ->whereIn('status', ['queued', 'processing', 'verifying'])
            ->exists();
        if ($alreadyQueued) {
            $this->info('A daily backup is already queued or running.');

            return;
        }

        $operation = DB::transaction(function () use ($marker): AdminBackupOperation {
            $operation = AdminBackupOperation::query()->create([
                'id' => (string) Str::uuid(),
                'admin_id' => null,
                'action' => 'create',
                'status' => 'queued',
                'archive_type' => 'daily',
                'original_filename' => $marker,
            ]);
            ProcessAdminBackupOperation::dispatch($operation->id)->afterCommit();

            return $operation;
        });
        $this->info("Daily backup operation {$operation->id} queued.");
    } finally {
        $lock->release();
    }
})->purpose('Queue a full daily SPES database and document backup');

Artisan::command('backup:run-if-due', function (AdminBackupService $backupService): void {
    $lock = Cache::lock('admin-backup-schedule-dispatch', 120);
    if (! $lock->get()) {
        return;
    }

    try {
        $settings = SystemSetting::current();
        if (! $settings->backup_enabled) {
            return;
        }

        $timezone = config('backup.timezone', 'Asia/Manila');
        $now = now($timezone);
        $frequency = in_array($settings->backup_frequency, ['daily', 'weekly'], true)
            ? $settings->backup_frequency
            : 'daily';
        [$hour, $minute] = array_map('intval', explode(':', $settings->backup_time ?: '02:00'));
        if ($frequency === 'weekly') {
            $periodStart = $now->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
            $dueAt = $periodStart->copy()->setTime($hour, $minute);
            $periodKey = $periodStart->format('o-\WW');
        } else {
            $dueAt = $now->copy()->setTime($hour, $minute);
            $periodKey = $now->format('Ymd');
        }
        if ($now->lessThan($dueAt)) {
            return;
        }

        $marker = "scheduled-{$frequency}-{$periodKey}";
        $alreadyRecorded = AdminBackupOperation::query()
            ->where('action', 'create')
            ->where('archive_type', $frequency)
            ->where('original_filename', $marker)
            ->exists();
        if ($alreadyRecorded) {
            return;
        }

        $operation = AdminBackupOperation::query()->create([
            'id' => (string) Str::uuid(),
            'admin_id' => null,
            'action' => 'create',
            'status' => 'queued',
            'archive_type' => $frequency,
            'original_filename' => $marker,
        ]);

        if (config('backup.execution_mode', 'sync') === 'queue') {
            try {
                ProcessAdminBackupOperation::dispatch($operation->id);
                $this->info(ucfirst($frequency).' backup operation queued.');
            } catch (Throwable $exception) {
                (new ProcessAdminBackupOperation($operation->id))->failed($exception);
                $this->error(ucfirst($frequency).' backup could not be started.');
            }

            return;
        }

        (new ProcessAdminBackupOperation($operation->id))->handle(
            $backupService,
            app(AuditLogger::class),
        );
        $operation->refresh();
        if ($operation->status === 'completed') {
            $this->info(ucfirst($frequency).' backup completed and verified.');
        } else {
            $this->error(ucfirst($frequency).' backup failed. Review Backup History and the application log.');
        }
    } finally {
        $lock->release();
    }
})->purpose('Create a daily or weekly automatic backup when its saved schedule is due');

Artisan::command('backup:cleanup', function (AdminBackupService $backupService): void {
    $result = $backupService->cleanupExpiredBackups();
    $this->info("Examined {$result['examined']} verified backups; deleted {$result['deleted']} expired backups.");
})->purpose('Remove expired verified backups while protecting required recovery points');

Artisan::command('backup:check-interrupted', function (AdminBackupService $backupService): void {
    $count = $backupService->failInterruptedOperations();
    if ($count > 0) {
        $this->warn("Marked {$count} interrupted backup/recovery operation(s) as failed. Inspect database state before any manual restore retry.");
    }
})->purpose('Mark abandoned backup operations as failed without blindly retrying them');

Artisan::command('backup:restore-annual {year}', function (string $year, AdminBackupService $backupService, AuditLogger $auditLogger): void {
    if (! preg_match('/^\d{4}$/D', $year)) {
        $this->error('Provide a four-digit backup year.');

        return;
    }

    $archive = $backupService->annualArchive((int) $year);
    if ($archive === null) {
        $this->error("No annual backup archive exists for {$year}.");

        return;
    }

    if (! $this->confirm("Merge the {$year} annual backup into the current system? Existing records and files will be kept.")) {
        $this->info('Annual backup restore cancelled.');

        return;
    }

    $lock = Cache::lock('admin-backup-recovery', 1200);
    if (! $lock->get()) {
        $this->error('Another recovery operation is already in progress.');

        return;
    }
    $operationLock = Cache::lock('admin-backup-operation', 3600);
    if (! $operationLock->get()) {
        $lock->release();
        $this->error('Another backup or recovery operation is already in progress.');

        return;
    }

    $operation = null;
    try {
        $operation = AdminBackupOperation::query()->create([
            'id' => (string) Str::uuid(),
            'admin_id' => null,
            'action' => 'restore',
            'status' => 'processing',
            'archive_path' => $archive['path'],
            'archive_type' => 'annual',
            'archive_year' => (int) $year,
            'started_at' => now(),
        ]);
        $backupService->validateForRestore(Storage::disk('local')->path($archive['path']), 'annual', (int) $year);
        $safetyBackup = $backupService->createSafetyArchiveForRestore($operation->id, null, true);
        $result = $backupService->restore(Storage::disk('local')->path($archive['path']), 'annual', (int) $year);
        $operation->update([
            'status' => 'completed',
            'result' => [
                'rows' => $result['rows'],
                'files' => $result['files'],
                'safety_backup' => $safetyBackup['filename'],
            ],
            'completed_at' => now(),
            'verified_at' => now(),
            'file_size_bytes' => Storage::disk('local')->size($archive['path']),
        ]);
        try {
            $auditLogger->record(
                AuditAction::DATABASE_BACKUP_RESTORED,
                'System Backup',
                null,
                null,
                null,
                ['description' => "Annual backup {$year} merged from the console: {$result['rows']} rows and {$result['files']} files added; a verified safety backup was created."],
            );
        } catch (Throwable $auditException) {
            Log::critical('Annual restore completed, but its audit entry could not be recorded.', [
                'operation_id' => $operation->id,
                'exception' => $auditException,
            ]);
        }

        $this->info("Annual backup {$year} merged: {$result['rows']} database rows and {$result['files']} files added. A verified safety backup was kept separately.");
    } catch (Throwable $exception) {
        if ($operation !== null) {
            $operation->update([
                'status' => 'failed',
                'completed_at' => now(),
                'failure_message' => __('The backup operation failed. Check the application log or contact the system administrator.'),
            ]);
            try {
                $auditLogger->record(
                    AuditAction::DATABASE_BACKUP_FAILED,
                    'System Backup',
                    null,
                    null,
                    null,
                    ['description' => "Annual backup {$year} merge restore failed.", 'operation_id' => $operation->id],
                );
            } catch (Throwable $auditException) {
                Log::critical('Annual restore failure could not be recorded in the administrator audit log.', [
                    'operation_id' => $operation->id,
                    'exception' => $auditException,
                ]);
            }
        }
        Log::error('Console annual SPES backup restore failed.', ['year' => $year, 'exception' => $exception]);
        throw $exception;
    } finally {
        $operationLock->release();
        $lock->release();
    }
})->purpose('Merge a saved yearly SPES backup from the command line');

Schedule::command('backup:annual')
    ->yearlyOn(12, 31, '23:59')
    ->timezone(config('backup.timezone', 'Asia/Manila'))
    ->name('spes-annual-backup')
    ->withoutOverlapping();

Schedule::command('backup:run-if-due')
    ->everyMinute()
    ->timezone(config('backup.timezone', 'Asia/Manila'))
    ->name('spes-automatic-backup')
    ->withoutOverlapping();

Schedule::command('backup:cleanup')
    ->dailyAt('03:00')
    ->timezone(config('backup.timezone', 'Asia/Manila'))
    ->name('spes-backup-retention-cleanup')
    ->withoutOverlapping();

Schedule::command('backup:check-interrupted')
    ->everyFiveMinutes()
    ->name('spes-backup-check-interrupted')
    ->withoutOverlapping();
