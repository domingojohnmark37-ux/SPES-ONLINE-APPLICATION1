<?php

use App\Jobs\ProcessAdminBackupOperation;
use App\Models\AdminBackupOperation;
use App\Models\Appointment;
use App\Models\AuditAction;
use App\Models\PendingRegistration;
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
    $year = (int) now(config('app.timezone'))->year;

    try {
        $archive = $backupService->createAnnualArchive($year);
        if ($archive === null) {
            $this->info("The annual backup for {$year} already exists; no archive was replaced.");

            return;
        }

        app(AuditLogger::class)->record(
            AuditAction::DATABASE_BACKUP_GENERATED,
            'System Backup',
            null,
            null,
            null,
            ['description' => "Annual backup for {$year} saved to private archive storage"],
        );

        $this->info("Annual backup for {$year} saved to {$archive['path']}.");
    } catch (Throwable $exception) {
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
        $date = now(config('app.timezone'))->format('Ymd');
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

Artisan::command('backup:cleanup', function (AdminBackupService $backupService): void {
    $result = $backupService->cleanupExpiredBackups();
    $this->info("Examined {$result['examined']} verified daily backups; deleted {$result['deleted']} expired backups.");
})->purpose('Remove expired daily backups while protecting required recovery points');

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

    try {
        $result = $backupService->restore(Storage::disk('local')->path($archive['path']), 'annual', (int) $year);
        $auditLogger->record(
            AuditAction::DATABASE_BACKUP_RESTORED,
            'System Backup',
            null,
            null,
            null,
            ['description' => "Annual backup {$year} restored from the console: {$result['rows']} rows and {$result['files']} files added"],
        );

        $this->info("Annual backup {$year} merged: {$result['rows']} database rows and {$result['files']} files added.");
    } catch (Throwable $exception) {
        Log::error('Console annual SPES backup restore failed.', ['year' => $year, 'exception' => $exception]);
        throw $exception;
    }
})->purpose('Merge a saved yearly SPES backup from the command line');

Schedule::command('backup:annual')
    ->yearlyOn(12, 31, '23:59')
    ->timezone(config('app.timezone'))
    ->name('spes-annual-backup')
    ->withoutOverlapping();

Schedule::command('backup:daily')
    ->dailyAt(config('backup.schedule_time', '02:00'))
    ->timezone(config('app.timezone'))
    ->name('spes-daily-backup')
    ->withoutOverlapping();

Schedule::command('backup:cleanup')
    ->dailyAt('03:00')
    ->timezone(config('app.timezone'))
    ->name('spes-backup-retention-cleanup')
    ->withoutOverlapping();

Schedule::command('backup:check-interrupted')
    ->everyFiveMinutes()
    ->name('spes-backup-check-interrupted')
    ->withoutOverlapping();
