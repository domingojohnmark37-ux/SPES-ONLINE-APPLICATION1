<?php

use App\Models\AuditAction;
use App\Models\PendingRegistration;
use App\Services\AdminBackupService;
use App\Services\AuditLogger;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
