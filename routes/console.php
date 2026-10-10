<?php

use App\Models\AuditAction;
use App\Models\Appointment;
use App\Models\PendingRegistration;
use App\Notifications\ApplicantPortalUpdate;
use App\Services\AdminBackupService;
use App\Services\ApplicantNotificationService;
use App\Services\AuditLogger;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
