<?php

namespace App\Services;

use App\Models\AuditAction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;
use SplFileInfo;
use ZipArchive;

class AdminBackupService
{
    private const FORMAT_VERSION = 2;

    private const TRANSIENT_TABLES = [
        'admin_backup_operations',
        'failed_jobs',
        'idempotent_form_submissions',
        'job_batches',
        'jobs',
    ];

    public function create(string $archiveType = 'manual', ?int $year = null, ?int $generatedBy = null): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP ZIP extension is required to create backups.');
        }
        if (! in_array($archiveType, ['manual', 'annual', 'daily', 'weekly'], true)) {
            throw new RuntimeException('The backup archive type is invalid.');
        }
        $startedAt = microtime(true);
        if (! Schema::hasTable('backup_archive_credentials')) {
            throw new RuntimeException('Run the database migrations before creating a protected backup.');
        }
        $storage = $this->backupStorageSummary();
        if (! $storage['available']) {
            throw new RuntimeException('The private backup directory is unavailable or not writable.');
        }
        if ($storage['free_bytes'] !== null && $storage['free_bytes'] < $storage['minimum_free_bytes']) {
            throw new RuntimeException('Backup creation was stopped because available disk space is below the configured safety threshold.');
        }

        $archiveId = (string) Str::uuid();
        $archivePath = tempnam(sys_get_temp_dir(), 'spes-backup-');
        $sqlPath = tempnam(sys_get_temp_dir(), 'spes-database-');
        $dataPath = tempnam(sys_get_temp_dir(), 'spes-database-data-');
        if ($archivePath === false || $sqlPath === false || $dataPath === false) {
            throw new RuntimeException('Unable to allocate temporary storage for the backup.');
        }

        try {
            DB::transaction(function () use ($sqlPath, $dataPath): void {
                $this->writeDatabaseDump($sqlPath);
                $this->writeDataSnapshot($dataPath);
            });
            $snapshotSize = filesize($dataPath);
            if ($snapshotSize === false || $snapshotSize > 268_435_456) {
                throw new RuntimeException('The database snapshot exceeds the supported 256 MB restore limit.');
            }
            $zip = new ZipArchive;
            if ($zip->open($archivePath, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to create the backup archive.');
            }

            if (! $zip->addFile($sqlPath, 'database/backup.sql')) {
                $zip->close();
                throw new RuntimeException('Unable to add the database dump to the backup.');
            }
            if (! $zip->addFile($dataPath, 'database/data.json')) {
                $zip->close();
                throw new RuntimeException('Unable to add the database data snapshot to the backup.');
            }
            if (
                ! $zip->setCompressionName('database/backup.sql', ZipArchive::CM_DEFLATE, 6)
                || ! $zip->setCompressionName('database/data.json', ZipArchive::CM_DEFLATE, 6)
            ) {
                $zip->close();
                throw new RuntimeException('Unable to configure database backup compression.');
            }

            if (! $zip->addFromString(
                'README.txt',
                "SPES Online Application backup\n"
                ."Contains the application database, files from storage/app/private and storage/app/public, and application logs.\n"
                ."Restore merges records and files that do not already exist; it does not overwrite current records or files.\n"
                ."It does not contain environment files, credentials, or application source code.\n"
                ."Keep this archive in a secure location because it contains applicant information.\n",
            )) {
                $zip->close();
                throw new RuntimeException('Unable to add the backup information file.');
            }
            $this->addStorageFiles($zip);
            $this->addLogFiles($zip);
            if (! $zip->close()) {
                throw new RuntimeException('Unable to finalize the backup archive before verification.');
            }

            $zip = new ZipArchive;
            if ($zip->open($archivePath) !== true) {
                throw new RuntimeException('Unable to reopen the backup archive for integrity verification.');
            }
            $manifest = $this->buildManifest($zip, $archiveId, $archiveType, $year);
            if (! $zip->addFromString('manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT))) {
                $zip->close();
                throw new RuntimeException('Unable to add the backup integrity manifest.');
            }

            if (! $zip->close()) {
                throw new RuntimeException('Unable to finalize the backup archive.');
            }

            $zip = new ZipArchive;
            if ($zip->open($archivePath) !== true) {
                throw new RuntimeException('Unable to reopen the completed backup for verification.');
            }
            try {
                $verifiedManifest = $this->validateArchive($zip);
                if ($verifiedManifest['credential']['id'] !== $archiveId) {
                    throw new RuntimeException('The backup credential changed during verification.');
                }
            } finally {
                $zip->close();
            }

            $this->registerCredential($manifest, $archiveType, $year, $generatedBy);
            DB::table('backup_archive_credentials')
                ->where('id', $archiveId)
                ->update([
                    'file_size_bytes' => filesize($archivePath) ?: null,
                    'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                    'verified_at' => now(),
                    'updated_at' => now(),
                ]);

            return $archivePath;
        } catch (\Throwable $exception) {
            @unlink($archivePath);
            throw $exception;
        } finally {
            @unlink($sqlPath);
            @unlink($dataPath);
        }
    }

    public function validateForRestore(string $archivePath, ?string $expectedType = null, ?int $expectedYear = null): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP ZIP extension is required to restore backups.');
        }

        $zip = new ZipArchive;
        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException('The uploaded file is not a readable backup archive.');
        }

        try {
            [$manifest, $tables] = $this->prepareRestoreSnapshot($zip, $expectedType, $expectedYear);

            return [
                'manifest' => $manifest,
                'tables' => count($tables),
            ];
        } finally {
            $zip->close();
        }
    }

    public function createSafetyArchiveForRestore(string $operationId, ?int $generatedBy, bool $operationLockHeld = false): array
    {
        $temporaryArchive = $this->createManualArchive($generatedBy, $operationLockHeld);
        $archivePath = "backups/recovery-safety/{$operationId}.zip";
        $disk = Storage::disk('local');
        $stream = fopen($temporaryArchive, 'rb');
        if ($stream === false) {
            @unlink($temporaryArchive);
            throw new RuntimeException('Unable to open the safety backup archive.');
        }

        try {
            if (! $disk->writeStream($archivePath, $stream)) {
                $disk->delete($archivePath);
                throw new RuntimeException('Unable to save the verified safety backup.');
            }
        } catch (\Throwable $exception) {
            $disk->delete($archivePath);
            throw $exception;
        } finally {
            fclose($stream);
            @unlink($temporaryArchive);
        }

        try {
            $manifest = $this->verifyArchiveFile($disk->path($archivePath), 'manual');
            $credentialId = $manifest['credential']['id'] ?? null;
            if (! is_string($credentialId)) {
                throw new RuntimeException('The safety backup credential is missing.');
            }
            DB::table('backup_archive_credentials')
                ->where('id', $credentialId)
                ->update([
                    'archive_path' => $archivePath,
                    'file_size_bytes' => $disk->size($archivePath),
                    'updated_at' => now(),
                ]);

            return [
                'credential_id' => $credentialId,
                'filename' => basename($archivePath),
                'size' => $disk->size($archivePath),
            ];
        } catch (\Throwable $exception) {
            $disk->delete($archivePath);
            throw $exception;
        }
    }

    public function verifiedArchiveByCredential(string $credentialId): ?array
    {
        $credential = DB::table('backup_archive_credentials')->where('id', $credentialId)->first();
        if (
            $credential === null
            || $credential->verified_at === null
            || ! is_string($credential->archive_path)
            || ! preg_match('#^backups/(?:daily|weekly|annual|recovery-safety)/[A-Za-z0-9._-]+\.zip$#D', $credential->archive_path)
        ) {
            return null;
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($credential->archive_path)) {
            return null;
        }

        $manifest = $this->verifyArchiveFile($disk->path($credential->archive_path), $credential->archive_type);
        if (
            ($manifest['credential']['id'] ?? null) !== $credentialId
            || ($manifest['archive_year'] ?? null) !== $credential->archive_year
        ) {
            return null;
        }

        return [
            'path' => $credential->archive_path,
            'filename' => basename($credential->archive_path),
            'size' => $disk->size($credential->archive_path),
            'type' => $credential->archive_type,
        ];
    }

    public function verifyStoredArchive(string $archivePath, string $expectedType, ?int $expectedYear = null): array
    {
        if (
            ! preg_match('#^backups/(?:annual|daily|weekly|operations)/[A-Za-z0-9._/-]+\.zip$#D', $archivePath)
            || str_contains($archivePath, '..')
        ) {
            throw new RuntimeException('The stored backup path is invalid.');
        }
        $disk = Storage::disk('local');
        if (! $disk->exists($archivePath)) {
            throw new RuntimeException('The stored backup archive is unavailable.');
        }
        $manifest = $this->verifyArchiveFile($disk->path($archivePath), $expectedType);
        if ($expectedYear !== null && ($manifest['archive_year'] ?? null) !== $expectedYear) {
            throw new RuntimeException('The stored backup archive does not match the selected year.');
        }

        return $manifest;
    }

    public function backupHistory(int $limit = 50): array
    {
        $disk = Storage::disk('local');
        $history = [];
        $operations = DB::table('admin_backup_operations')
            ->whereIn('action', ['create', 'restore'])
            ->where(function ($query): void {
                $query->whereNotIn('archive_type', ['daily', 'weekly'])
                    ->orWhereNull('archive_type')
                    ->orWhere('status', '!=', 'completed');
            })
            ->latest('created_at')
            ->limit($limit)
            ->get();
        $operationArchivePaths = $operations->pluck('archive_path')->filter()->all();

        foreach ($operations as $operation) {
            $exists = is_string($operation->archive_path) && $disk->exists($operation->archive_path);
            $verified = $operation->verified_at !== null && $exists;
            $result = is_string($operation->result) ? json_decode($operation->result, true) : [];
            $result = is_array($result) ? $result : [];
            $history[] = [
                'key' => 'operation:'.$operation->id,
                'name' => $result !== []
                    ? ($result['filename'] ?? basename((string) $operation->archive_path))
                    : ($operation->original_filename ?: __('Restore operation')),
                'type' => $operation->action === 'restore'
                    ? 'restore'
                    : ($operation->archive_type === 'annual'
                        ? 'annual'
                        : (in_array($operation->archive_type, ['daily', 'weekly'], true) ? $operation->archive_type : 'manual')),
                'year' => $operation->archive_year,
                'created_at' => Carbon::parse($operation->created_at),
                'size' => $operation->file_size_bytes,
                'verified' => $verified,
                'status' => ($result['expired'] ?? false) ? 'expired' : $operation->status,
                'download_url' => $operation->action === 'create'
                    && (int) ($operation->admin_id ?? 0) === (int) auth()->id()
                    && $operation->status === 'completed'
                    && $verified
                    ? route('admin.backup.operations.download', $operation->id)
                    : null,
            ];
        }

        $credentials = DB::table('backup_archive_credentials')
            ->where(function ($query): void {
                $query->whereIn('archive_type', ['daily', 'weekly', 'annual'])
                    ->orWhere('archive_path', 'like', 'backups/recovery-safety/%');
            })
            ->latest('created_at')
            ->limit($limit)
            ->get();

        foreach ($credentials as $credential) {
            if (
                $credential->archive_type === 'annual'
                && is_string($credential->archive_path)
                && in_array($credential->archive_path, $operationArchivePaths, true)
            ) {
                continue;
            }
            $exists = is_string($credential->archive_path) && $disk->exists($credential->archive_path);
            $verified = $credential->verified_at !== null && $exists;
            $createdAt = Carbon::parse($credential->created_at);
            $archiveType = str_starts_with((string) $credential->archive_path, 'backups/recovery-safety/')
                ? 'safety'
                : $credential->archive_type;
            $history[] = [
                'key' => 'credential:'.$credential->id,
                'name' => is_string($credential->archive_path)
                    ? basename($credential->archive_path)
                    : ($credential->archive_type === 'annual'
                        ? 'spes-annual-backup-'.$credential->archive_year.'.zip'
                        : ($credential->archive_type === 'weekly'
                            ? 'spes-weekly-backup-'.$createdAt->format('o-W').'.zip'
                            : 'spes-daily-backup-'.$createdAt->format('Ymd').'.zip')),
                'type' => $archiveType,
                'year' => $credential->archive_type === 'annual' ? (int) $credential->archive_year : null,
                'created_at' => $createdAt,
                'size' => $credential->file_size_bytes,
                'verified' => $verified,
                'status' => $exists ? 'completed' : (in_array($credential->archive_type, ['daily', 'weekly'], true) ? 'expired' : 'failed'),
                'download_url' => $verified
                    ? ($credential->archive_type === 'annual'
                        ? route('admin.backup.archive.download', $credential->archive_year)
                        : route('admin.backup.verified.download', $credential->id))
                    : null,
            ];
        }

        usort($history, static fn (array $first, array $second): int => $second['created_at'] <=> $first['created_at']);

        return array_slice($history, 0, $limit);
    }

    public function automaticBackupStatus(): array
    {
        $disk = Storage::disk('local');
        $settings = \App\Models\SystemSetting::current();
        $frequency = in_array($settings->backup_frequency, ['daily', 'weekly'], true)
            ? $settings->backup_frequency
            : 'daily';
        $lastSuccessful = DB::table('backup_archive_credentials')
            ->whereIn('archive_type', ['daily', 'weekly'])
            ->whereNotNull('archive_path')
            ->whereNotNull('verified_at')
            ->latest('created_at')
            ->limit(100)
            ->get()
            ->first(fn ($archive): bool => $disk->exists($archive->archive_path));
        $lastConfiguredSuccessful = DB::table('backup_archive_credentials')
            ->where('archive_type', $frequency)
            ->whereNotNull('archive_path')
            ->whereNotNull('verified_at')
            ->latest('created_at')
            ->limit(100)
            ->get()
            ->first(fn ($archive): bool => $disk->exists($archive->archive_path));
        $latestOperation = DB::table('admin_backup_operations')
            ->where('action', 'create')
            ->where('archive_type', $frequency)
            ->latest('created_at')
            ->first();

        $status = 'unverified';
        if ($latestOperation !== null && in_array($latestOperation->status, ['queued', 'processing', 'verifying'], true)) {
            $status = 'processing';
        } elseif (
            $latestOperation !== null
            && $latestOperation->status === 'failed'
            && ($lastConfiguredSuccessful === null || Carbon::parse($latestOperation->created_at)->greaterThan(Carbon::parse($lastConfiguredSuccessful->created_at)))
        ) {
            $status = 'failed';
        }

        $timezone = config('backup.timezone', 'Asia/Manila');
        $now = Carbon::now($timezone);
        $nextScheduled = $this->nextAutomaticBackupAt($settings, $now);
        $latestScheduled = $nextScheduled->copy()->subWeek();
        if ($frequency === 'daily') {
            $latestScheduled = $nextScheduled->copy()->subDay();
        }
        if (
            $status === 'unverified'
            && $lastConfiguredSuccessful !== null
            && Carbon::parse($lastConfiguredSuccessful->created_at, $timezone)->greaterThanOrEqualTo($latestScheduled)
        ) {
            $status = 'completed';
        }
        $nextAnnual = Carbon::createFromDate($now->year, 12, 31, $timezone)->setTime(23, 59);
        if ($nextAnnual->lessThanOrEqualTo($now)) {
            $nextAnnual->addYear();
        }

        return [
            'status' => $status,
            'last_successful_at' => $lastSuccessful?->created_at,
            'size' => $lastSuccessful?->file_size_bytes,
            'verification' => $lastSuccessful === null ? 'unverified' : 'verified',
            'next_daily_at' => $settings->backup_enabled ? $nextScheduled : null,
            'frequency' => $frequency,
            'enabled' => (bool) $settings->backup_enabled,
            'timezone' => $timezone,
            'next_annual_at' => $nextAnnual,
        ];
    }

    public function nextAutomaticBackupAt(?\App\Models\SystemSetting $settings = null, ?Carbon $from = null): Carbon
    {
        $settings ??= \App\Models\SystemSetting::current();
        $timezone = config('backup.timezone', 'Asia/Manila');
        $now = ($from ?? Carbon::now($timezone))->copy()->setTimezone($timezone);
        $time = $settings->backup_time ?: config('backup.schedule_time', '02:00');
        [$hour, $minute] = array_map('intval', explode(':', $time));
        if ($settings->backup_frequency === 'weekly') {
            $next = $now->copy()->startOfWeek(Carbon::MONDAY)->setTime($hour, $minute);
            if ($next->lessThanOrEqualTo($now)) {
                $next->addWeek();
            }

            return $next;
        }

        $next = $now->copy()->setTime($hour, $minute);
        if ($next->lessThanOrEqualTo($now)) {
            $next->addDay();
        }

        return $next;
    }

    public function restore(string $archivePath, ?string $expectedType = null, ?int $expectedYear = null): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP ZIP extension is required to restore backups.');
        }

        $zip = new ZipArchive;
        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException('The uploaded file is not a readable backup archive.');
        }

        $createdFiles = [];
        try {
            [$manifest, $tables] = $this->prepareRestoreSnapshot($zip, $expectedType, $expectedYear);
            $this->restoreFiles($zip, $createdFiles);
            $importedRows = $this->mergeRows($tables, $manifest);

            return [
                'tables' => count($tables),
                'rows' => $importedRows,
                'files' => count($createdFiles),
                'manifest' => $manifest,
            ];
        } catch (\Throwable $exception) {
            $this->removeRestoredFiles($createdFiles);
            throw $exception;
        } finally {
            $zip->close();
        }
    }

    public function createAnnualArchive(int $year): ?array
    {
        if ($year < 2000 || $year > 9999) {
            throw new RuntimeException('The annual backup year is invalid.');
        }

        $creationLock = Cache::lock('admin-backup-operation', 3600);
        if (! $creationLock->get()) {
            throw new RuntimeException('Another backup creation is already in progress.');
        }

        try {
            $filename = "spes-annual-backup-{$year}.zip";
            $archivePath = 'backups/annual/'.$filename;
            $disk = Storage::disk('local');

            if ($disk->exists($archivePath)) {
                $this->verifyStoredArchive($archivePath, 'annual', $year);

                return null;
            }

            $temporaryArchive = $this->create('annual', $year);
            $stream = fopen($temporaryArchive, 'rb');
            if ($stream === false) {
                @unlink($temporaryArchive);
                throw new RuntimeException('Unable to open the annual backup for archival.');
            }

            try {
                if (! $disk->writeStream($archivePath, $stream)) {
                    throw new RuntimeException('Unable to save the annual backup to private archive storage.');
                }
            } catch (\Throwable $exception) {
                $disk->delete($archivePath);
                throw $exception;
            } finally {
                fclose($stream);
                @unlink($temporaryArchive);
            }
            try {
                $manifest = $this->verifyStoredArchive($archivePath, 'annual', $year);
            } catch (\Throwable $exception) {
                $disk->delete($archivePath);
                throw $exception;
            }
            DB::table('backup_archive_credentials')
                ->where('id', $manifest['credential']['id'])
                ->update([
                    'archive_path' => $archivePath,
                    'file_size_bytes' => $disk->size($archivePath),
                    'verified_at' => now(),
                    'updated_at' => now(),
                ]);

            return $this->annualArchive($year);
        } finally {
            $creationLock->release();
        }
    }

    public function annualArchives(): array
    {
        $archives = [];
        foreach (Storage::disk('local')->files('backups/annual') as $path) {
            if (! preg_match('#^backups/annual/spes-annual-backup-(\d{4})\.zip$#D', $path, $matches)) {
                continue;
            }

            $archives[] = $this->annualArchive((int) $matches[1]);
        }

        usort($archives, static fn (array $first, array $second): int => $second['year'] <=> $first['year']);

        return $archives;
    }

    public function createDailyArchive(): ?array
    {
        return $this->createScheduledArchive('daily', Carbon::now(config('backup.timezone', 'Asia/Manila'))->format('Ymd'));
    }

    public function createScheduledArchive(string $archiveType, string $periodKey, bool $creationLockHeld = false): ?array
    {
        if (! in_array($archiveType, ['daily', 'weekly'], true)) {
            throw new RuntimeException('The scheduled backup type is invalid.');
        }
        $validPeriod = $archiveType === 'daily'
            ? preg_match('/^\d{8}$/D', $periodKey) === 1
            : preg_match('/^\d{4}-W\d{2}$/D', $periodKey) === 1;
        if (! $validPeriod) {
            throw new RuntimeException('The scheduled backup period is invalid.');
        }

        $creationLock = $creationLockHeld ? null : Cache::lock('admin-backup-operation', 3600);
        if ($creationLock !== null && ! $creationLock->get()) {
            throw new RuntimeException('Another backup creation is already in progress.');
        }
        $lock = Cache::lock("admin-backup-{$archiveType}-create", 1800);
        if (! $lock->get()) {
            $creationLock?->release();
            throw new RuntimeException('A scheduled backup is already being created.');
        }
        $filename = "spes-{$archiveType}-backup-{$periodKey}.zip";
        $archivePath = "backups/{$archiveType}/{$filename}";
        $disk = Storage::disk('local');

        try {
            if ($disk->exists($archivePath)) {
                $this->verifyArchiveFile($disk->path($archivePath), $archiveType);

                return null;
            }

            $temporaryArchive = $this->create($archiveType);
            $stream = fopen($temporaryArchive, 'rb');
            if ($stream === false) {
                @unlink($temporaryArchive);
                throw new RuntimeException('Unable to open the scheduled backup for archival.');
            }

            try {
                if (! $disk->writeStream($archivePath, $stream)) {
                    $disk->delete($archivePath);
                    throw new RuntimeException('Unable to save the scheduled backup to private archive storage.');
                }
            } finally {
                fclose($stream);
                @unlink($temporaryArchive);
            }

            try {
                $manifest = $this->verifyStoredArchive($archivePath, $archiveType);
            } catch (\Throwable $exception) {
                $disk->delete($archivePath);
                throw $exception;
            }
            DB::table('backup_archive_credentials')
                ->where('id', $manifest['credential']['id'])
                ->update([
                    'archive_path' => $archivePath,
                    'file_size_bytes' => $disk->size($archivePath),
                    'updated_at' => now(),
                ]);

            return [
                'path' => $archivePath,
                'size' => $disk->size($archivePath),
                'created_at' => now(),
            ];
        } finally {
            $lock->release();
            $creationLock?->release();
        }
    }

    public function verifiedDailyArchiveExists(): bool
    {
        $path = 'backups/daily/spes-daily-backup-'.Carbon::now(config('backup.timezone', 'Asia/Manila'))->format('Ymd').'.zip';
        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            return false;
        }
        $this->verifyArchiveFile($disk->path($path), 'daily');

        return true;
    }

    public function createManualArchive(?int $generatedBy = null, bool $creationLockHeld = false): string
    {
        $creationLock = $creationLockHeld ? null : Cache::lock('admin-backup-operation', 3600);
        if ($creationLock !== null && ! $creationLock->get()) {
            throw new RuntimeException('Another backup creation is already in progress.');
        }
        $lock = Cache::lock('admin-backup-manual-create', 1800);
        if (! $lock->get()) {
            $creationLock?->release();
            throw new RuntimeException('Another manual or safety backup is already being created.');
        }

        try {
            return $this->create('manual', null, $generatedBy);
        } finally {
            $lock->release();
            $creationLock?->release();
        }
    }

    private function verifyArchiveFile(string $archivePath, string $expectedType): array
    {
        $zip = new ZipArchive;
        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException('The existing backup file cannot be opened for verification.');
        }
        try {
            $manifest = $this->validateArchive($zip);
            if (($manifest['archive_type'] ?? null) !== $expectedType) {
                throw new RuntimeException('The existing backup file has an unexpected archive type.');
            }

            return $manifest;
        } finally {
            $zip->close();
        }
    }

    private function prepareRestoreSnapshot(ZipArchive $zip, ?string $expectedType, ?int $expectedYear): array
    {
        $manifest = $this->validateArchive($zip);
        $pdoDriver = DB::connection()->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
        $backupDriver = $manifest['database_driver'] ?? null;
        $compatibleDriver = $pdoDriver === 'mysql'
            ? in_array($backupDriver, ['mysql', 'mariadb'], true)
            : $backupDriver === $pdoDriver;
        if (! $compatibleDriver) {
            throw new RuntimeException('The backup database engine is incompatible with the configured application database.');
        }
        if (
            ($expectedType !== null && ($manifest['archive_type'] ?? null) !== $expectedType)
            || ($expectedYear !== null && ($manifest['archive_year'] ?? null) !== $expectedYear)
        ) {
            throw new RuntimeException('The backup credential does not match the selected archive.');
        }
        $snapshotContents = $zip->getFromName('database/data.json');
        if (! is_string($snapshotContents)) {
            throw new RuntimeException('The backup is missing its database data snapshot.');
        }

        try {
            $snapshot = json_decode($snapshotContents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException('The backup database snapshot is invalid.', previous: $exception);
        }
        unset($snapshotContents);

        return [$manifest, $this->validateSnapshot($snapshot)];
    }

    public function annualArchive(int $year): ?array
    {
        $path = "backups/annual/spes-annual-backup-{$year}.zip";
        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            return null;
        }

        return [
            'year' => $year,
            'path' => $path,
            'size' => $disk->size($path),
            'last_modified' => $disk->lastModified($path),
        ];
    }

    public function retentionPreview(): array
    {
        $daily = DB::table('backup_archive_credentials')
            ->select('id', 'archive_path', 'created_at', 'duration_ms', 'verified_at')
            ->whereIn('archive_type', ['daily', 'weekly'])
            ->whereNotNull('archive_path')
            ->whereNotNull('verified_at')
            ->get();
        $manual = DB::table('admin_backup_operations')
            ->select('id', 'archive_path', 'created_at', 'duration_ms', 'verified_at')
            ->addSelect('result')
            ->whereIn('action', ['create', 'restore'])
            ->where('status', 'completed')
            ->whereNotNull('archive_path')
            ->whereNotNull('verified_at')
            ->where(function ($query): void {
                $query->where('archive_path', 'like', 'backups/operations/%')
                    ->orWhere('archive_path', 'like', 'backups/operations/uploads/%');
            })
            ->get();
        $archives = [];
        foreach ($daily->concat($manual)->unique('archive_path') as $archive) {
            if (! Storage::disk('local')->exists($archive->archive_path)) {
                continue;
            }
            $archives[] = [
                'path' => $archive->archive_path,
                'created_at' => $archive->created_at,
                'size' => Storage::disk('local')->size($archive->archive_path),
                'duration_ms' => $archive->duration_ms,
                'verified_at' => $archive->verified_at,
                'type' => str_starts_with($archive->archive_path, 'backups/weekly/')
                    ? 'weekly'
                    : (str_starts_with($archive->archive_path, 'backups/daily/')
                        ? 'daily'
                        : (str_contains($archive->archive_path, '/uploads/') ? 'restore upload' : 'manual')),
            ];
        }

        $keep = $this->retainedDailyArchiveIds($daily->concat($manual));
        foreach ($archives as &$archive) {
            $archive['will_expire'] = ! isset($keep[$archive['path']]);
        }
        unset($archive);

        return $archives;
    }

    public function backupStorageSummary(): array
    {
        $disk = Storage::disk('local');
        $bytes = 0;
        $root = $disk->path('');
        $available = is_dir($root) && is_writable($root);
        try {
            if ($available) {
                foreach ($disk->allFiles('backups') as $path) {
                    $bytes += $disk->size($path);
                }
            }
        } catch (\Throwable $exception) {
            $available = false;
            Log::warning('Private backup storage could not be inspected.', ['exception' => $exception]);
        }

        $freeBytes = function_exists('disk_free_space') ? @disk_free_space($root) : false;

        return [
            'available' => $available,
            'bytes' => $bytes,
            'free_bytes' => is_int($freeBytes) || is_float($freeBytes) ? (int) $freeBytes : null,
            'minimum_free_bytes' => (int) config('backup.minimum_free_bytes', 1_073_741_824),
        ];
    }

    public function cleanupExpiredBackups(): array
    {
        $lock = Cache::lock('admin-backup-retention-cleanup', 1800);
        if (! $lock->get()) {
            return ['examined' => 0, 'deleted' => 0, 'results' => ['skipped' => 'A cleanup operation is already running.']];
        }

        try {
            $daily = DB::table('backup_archive_credentials')
                ->whereIn('archive_type', ['daily', 'weekly'])
                ->whereNotNull('archive_path')
                ->whereNotNull('verified_at')
                ->get();
            $manual = DB::table('admin_backup_operations')
                ->whereIn('action', ['create', 'restore'])
                ->where('status', 'completed')
                ->whereNotNull('archive_path')
                ->whereNotNull('verified_at')
                ->where(function ($query): void {
                    $query->where('archive_path', 'like', 'backups/operations/%')
                        ->orWhere('archive_path', 'like', 'backups/operations/uploads/%');
                })
                ->get();
            $archives = $daily->concat($manual)->unique('archive_path')->sortByDesc('created_at')->values();
            $keep = $this->retainedDailyArchiveIds($archives);
            $results = [];
            $deleted = 0;
            $disk = Storage::disk('local');

            foreach ($archives as $archive) {
                $path = $archive->archive_path;
                if (isset($keep[$path])) {
                    continue;
                }
                $inUse = DB::table('admin_backup_operations')
                    ->whereIn('status', ['queued', 'running', 'processing', 'verifying'])
                    ->where('archive_path', $path)
                    ->exists();
                if ($inUse) {
                    $results[] = ['path' => $path, 'result' => 'protected_in_use'];

                    continue;
                }
                if ($disk->exists($path)) {
                    if (! $disk->delete($path)) {
                        $results[] = ['path' => $path, 'result' => 'delete_failed'];

                        continue;
                    }
                }
                if (str_starts_with($path, 'backups/daily/') || str_starts_with($path, 'backups/weekly/')) {
                    DB::table('backup_archive_credentials')
                        ->where('id', $archive->id)
                        ->update(['archive_path' => null, 'updated_at' => now()]);
                } else {
                    $result = json_decode((string) $archive->result, true) ?: [];
                    $result['expired'] = true;
                    DB::table('admin_backup_operations')
                        ->where('id', $archive->id)
                        ->update([
                            'archive_path' => null,
                            'result' => json_encode($result, JSON_THROW_ON_ERROR),
                            'updated_at' => now(),
                        ]);
                }
                $results[] = ['path' => $path, 'result' => 'deleted'];
                $deleted++;
            }

            $failedUploads = DB::table('admin_backup_operations')
                ->where('action', 'restore')
                ->where('status', 'failed')
                ->whereNotNull('archive_path')
                ->where('archive_path', 'like', 'backups/operations/uploads/%')
                ->where('completed_at', '<', now()->subDays($this->backupRetentionDays()))
                ->get();
            foreach ($failedUploads as $operation) {
                $path = $operation->archive_path;
                if ($disk->exists($path) && ! $disk->delete($path)) {
                    $results[] = ['path' => $path, 'result' => 'delete_failed'];

                    continue;
                }
                DB::table('admin_backup_operations')
                    ->where('id', $operation->id)
                    ->update(['archive_path' => null, 'updated_at' => now()]);
                $results[] = ['path' => $path, 'result' => 'failed_upload_expired'];
                $deleted++;
            }

            DB::table('backup_cleanup_runs')->insert([
                'examined' => count($archives) + count($failedUploads),
                'deleted' => $deleted,
                'results' => json_encode($results, JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ]);

            return ['examined' => count($archives), 'deleted' => $deleted, 'results' => $results];
        } finally {
            $lock->release();
        }
    }

    public function failInterruptedOperations(): int
    {
        $cutoff = now()->subSeconds(960);
        $operations = DB::table('admin_backup_operations')
            ->whereIn('status', ['queued', 'processing', 'verifying'])
            ->where(function ($query) use ($cutoff): void {
                $query->where('started_at', '<', $cutoff)
                    ->orWhere(function ($query) use ($cutoff): void {
                        $query->whereNull('started_at')->where('created_at', '<', $cutoff);
                    });
            })
            ->get();

        $failedCount = 0;
        foreach ($operations as $operation) {
            $updated = DB::table('admin_backup_operations')
                ->where('id', $operation->id)
                ->whereIn('status', ['queued', 'processing', 'verifying'])
                ->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'failure_message' => __('The operation did not complete before its safety timeout. Do not retry a restore until its database state has been checked.'),
                    'updated_at' => now(),
                ]);
            if ($updated === 0) {
                continue;
            }
            $failedCount++;
            Log::error('An interrupted admin backup operation was marked failed and requires inspection.', [
                'operation_id' => $operation->id,
                'action' => $operation->action,
            ]);
            try {
                app(AuditLogger::class)->record(
                    AuditAction::DATABASE_BACKUP_FAILED,
                    'System Backup',
                    null,
                    null,
                    null,
                    [
                        'description' => 'An interrupted backup or recovery operation was marked failed; database state requires inspection.',
                        'operation_id' => $operation->id,
                    ],
                );
            } catch (\Throwable $exception) {
                Log::critical('An interrupted backup operation could not be recorded in the audit log.', [
                    'operation_id' => $operation->id,
                    'exception' => $exception,
                ]);
            }
        }

        return $failedCount;
    }

    private function retainedDailyArchiveIds(iterable $archives): array
    {
        $archives = collect($archives)
            ->filter(fn ($archive): bool => $archive->verified_at !== null)
            ->sortByDesc('created_at')
            ->values();
        $keep = [];
        $cutoff = now(config('backup.timezone', 'Asia/Manila'))
            ->subDays($this->backupRetentionDays())
            ->startOfDay();

        foreach ($archives as $archive) {
            $createdAt = Carbon::parse($archive->created_at);
            $path = $archive->archive_path;
            if ($createdAt->greaterThanOrEqualTo($cutoff)) {
                $keep[$path] = true;
            }
        }

        $newestVerified = $archives->first();
        if ($newestVerified !== null) {
            $keep[$newestVerified->archive_path] = true;
        }

        return $keep;
    }

    private function backupRetentionDays(): int
    {
        return max(1, (int) (\App\Models\SystemSetting::current()->backup_retention_days ?: 7));
    }

    private function writeDatabaseDump(string $path): void
    {
        $pdo = DB::connection()->getPdo();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Unable to write the database dump.');
        }

        try {
            $this->writeAll($handle, "-- SPES database backup\n");
            if ($driver === 'mysql') {
                $this->writeAll($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
                $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
            } elseif ($driver === 'sqlite') {
                $this->writeAll($handle, "PRAGMA foreign_keys=OFF;\n");
                $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
            } else {
                throw new RuntimeException("Database backup does not support [{$driver}].");
            }

            while (($table = $tables->fetch(PDO::FETCH_NUM)) !== false) {
                $tableName = (string) $table[0];
                if (in_array($tableName, self::TRANSIENT_TABLES, true)) {
                    continue;
                }
                $identifier = $this->quoteIdentifier($tableName);
                $createStatement = $driver === 'mysql'
                    ? $pdo->query("SHOW CREATE TABLE {$identifier}")
                    : $pdo->prepare("SELECT sql FROM sqlite_master WHERE type='table' AND name=?");
                if ($driver === 'sqlite') {
                    $createStatement->execute([$tableName]);
                }
                $create = $createStatement->fetch(PDO::FETCH_NUM);
                $createSql = $driver === 'mysql' ? ($create[1] ?? null) : ($create[0] ?? null);
                if (! is_string($createSql) || $createSql === '') {
                    throw new RuntimeException("Unable to read the schema for table [{$tableName}].");
                }

                $this->writeAll($handle, "\nDROP TABLE IF EXISTS {$identifier};\n{$createSql};\n");
                $rows = $pdo->query("SELECT * FROM {$identifier}");

                while (($row = $rows->fetch(PDO::FETCH_ASSOC)) !== false) {
                    $columns = array_map(fn (string $column): string => $this->quoteIdentifier($column), array_keys($row));
                    $values = array_map(fn ($value): string => $this->quoteValue($pdo, $value), array_values($row));
                    $this->writeAll($handle, 'INSERT INTO '.$identifier.' ('.implode(', ', $columns).') VALUES ('.implode(', ', $values).");\n");
                }
            }

            $this->writeAll($handle, $driver === 'mysql' ? "\nSET FOREIGN_KEY_CHECKS=1;\n" : "\nPRAGMA foreign_keys=ON;\n");
        } finally {
            fclose($handle);
        }
    }

    private function addStorageFiles(ZipArchive $zip): void
    {
        foreach (['local' => 'private', 'public' => 'public'] as $disk => $archiveDisk) {
            $storage = Storage::disk($disk);
            foreach ($storage->allFiles() as $relativePath) {
                if (
                    $disk === 'local'
                    && (str_starts_with($relativePath, 'backups/annual/')
                        || str_starts_with($relativePath, 'backups/operations/')
                        || str_starts_with($relativePath, 'backups/recovery-safety/'))
                ) {
                    continue;
                }
                $filePath = $storage->path($relativePath);
                $file = new SplFileInfo($filePath);
                if (! $file->isFile() || $file->isLink()) {
                    continue;
                }

                $archiveName = 'uploads/'.$archiveDisk.'/'.str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
                if (! $zip->addFile($filePath, $archiveName)) {
                    throw new RuntimeException("Unable to add stored file [{$relativePath}] to the backup.");
                }
            }
        }
    }

    private function addLogFiles(ZipArchive $zip): void
    {
        $logRoot = storage_path('logs');
        if (! is_dir($logRoot)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($logRoot, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->isLink()) {
                continue;
            }

            $relativePath = substr($file->getPathname(), strlen($logRoot) + 1);
            $archiveName = 'logs/'.str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            if (! $zip->addFile($file->getPathname(), $archiveName)) {
                throw new RuntimeException("Unable to add application log [{$relativePath}] to the backup.");
            }
        }
    }

    private function writeDataSnapshot(string $path): void
    {
        $pdo = DB::connection()->getPdo();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $tables = array_values(array_diff(
            $this->databaseTableNames($pdo, $driver),
            self::TRANSIENT_TABLES,
        ));
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Unable to write the database data snapshot.');
        }

        try {
            $this->writeAll($handle, '{"format":"spes-db-rows-v1","tables":{');
            foreach ($tables as $tableIndex => $tableName) {
                if ($tableIndex > 0) {
                    $this->writeAll($handle, ',');
                }
                $this->writeAll($handle, json_encode($tableName, JSON_THROW_ON_ERROR).':[');

                $rows = $pdo->query('SELECT * FROM '.$this->quoteIdentifier($tableName));
                $rowIndex = 0;
                while (($row = $rows->fetch(PDO::FETCH_ASSOC)) !== false) {
                    if ($rowIndex++ > 0) {
                        $this->writeAll($handle, ',');
                    }
                    $encodedRow = [];
                    foreach ($row as $column => $value) {
                        $encodedRow[$column] = $this->encodeSnapshotValue($value);
                    }
                    $this->writeAll($handle, json_encode($encodedRow, JSON_THROW_ON_ERROR));
                }

                $this->writeAll($handle, ']');
            }
            $this->writeAll($handle, '}}');
        } finally {
            fclose($handle);
        }
    }

    private function writeAll(mixed $handle, string $contents): void
    {
        $length = strlen($contents);
        $written = 0;
        while ($written < $length) {
            $count = fwrite($handle, substr($contents, $written));
            if ($count === false || $count === 0) {
                throw new RuntimeException('The backup could not be written completely; check available disk space and permissions.');
            }
            $written += $count;
        }
    }

    private function encodeSnapshotValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return preg_match('//u', $value) === 1
            ? ['__spes_backup_type' => 'string', 'value' => $value]
            : ['__spes_backup_type' => 'binary', 'value' => base64_encode($value)];
    }

    private function decodeSnapshotValue(mixed $value): mixed
    {
        if (! is_array($value) || ! isset($value['__spes_backup_type'], $value['value'])) {
            return $value;
        }

        if ($value['__spes_backup_type'] === 'string' && is_string($value['value'])) {
            return $value['value'];
        }
        if ($value['__spes_backup_type'] === 'binary' && is_string($value['value'])) {
            $decoded = base64_decode($value['value'], true);
            if ($decoded !== false) {
                return $decoded;
            }
        }

        throw new RuntimeException('The backup contains a malformed database value.');
    }

    private function databaseTableNames(PDO $pdo, string $driver): array
    {
        if ($driver === 'mysql') {
            $statement = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        } elseif ($driver === 'sqlite') {
            $statement = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        } else {
            throw new RuntimeException("Database backup does not support [{$driver}].");
        }

        return array_map(
            static fn (array $table): string => (string) $table[0],
            $statement->fetchAll(PDO::FETCH_NUM),
        );
    }

    private function buildManifest(ZipArchive $zip, string $archiveId, string $archiveType, ?int $year): array
    {
        $hashes = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (! is_string($name) || $name === 'manifest.json' || str_ends_with($name, '/')) {
                continue;
            }

            $stream = $zip->getStream($name);
            if ($stream === false) {
                throw new RuntimeException("Unable to verify backup entry [{$name}].");
            }
            $context = hash_init('sha256');
            hash_update_stream($context, $stream);
            fclose($stream);
            $hashes[$name] = hash_final($context);
        }

        ksort($hashes);

        $manifest = [
            'format' => self::FORMAT_VERSION,
            'created_at' => now()->toIso8601String(),
            'database_driver' => DB::connection()->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME),
            'database_version' => $this->databaseServerVersion(),
            'archive_type' => $archiveType,
            'archive_year' => $year,
            'hashes' => $hashes,
        ];
        $manifest['credential'] = [
            'id' => $archiveId,
            'signature' => $this->signCredential($archiveId, $archiveType, $year, $hashes),
        ];

        return $manifest;
    }

    private function databaseServerVersion(): string
    {
        $pdo = DB::connection()->getPdo();

        return (string) $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    private function signCredential(string $archiveId, string $archiveType, ?int $year, array $hashes): string
    {
        ksort($hashes);
        $payload = json_encode([
            'version' => 1,
            'id' => $archiveId,
            'archive_type' => $archiveType,
            'archive_year' => $year,
            'hashes' => $hashes,
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return hash_hmac('sha256', $payload, $this->credentialSigningKey());
    }

    private function credentialSigningKey(): string
    {
        $applicationKey = (string) config('app.key');
        if (str_starts_with($applicationKey, 'base64:')) {
            $applicationKey = base64_decode(substr($applicationKey, 7), true) ?: '';
        }
        if ($applicationKey === '') {
            throw new RuntimeException('APP_KEY must be configured to issue or verify backup credentials.');
        }

        return hash_hmac('sha256', 'spes-backup-credential-v1', $applicationKey, true);
    }

    private function validateCredential(array $manifest): void
    {
        $credential = $manifest['credential'] ?? null;
        $archiveType = $manifest['archive_type'] ?? null;
        $year = $manifest['archive_year'] ?? null;
        $hashes = $manifest['hashes'] ?? null;
        if (
            ! is_array($credential)
            || ! is_string($credential['id'] ?? null)
            || ! preg_match('/^[0-9a-f-]{36}$/D', $credential['id'])
            || ! is_string($credential['signature'] ?? null)
            || ! preg_match('/^[a-f0-9]{64}$/D', $credential['signature'])
            || ! in_array($archiveType, ['manual', 'annual', 'daily', 'weekly'], true)
            || ($archiveType === 'annual' && (! is_int($year) || $year < 2000 || $year > 9999))
            || (in_array($archiveType, ['manual', 'daily', 'weekly'], true) && $year !== null)
            || ! is_array($hashes)
        ) {
            throw new RuntimeException('The backup credential is invalid or missing.');
        }

        $expectedSignature = $this->signCredential($credential['id'], $archiveType, $year, $hashes);
        if (! hash_equals($expectedSignature, $credential['signature'])) {
            throw new RuntimeException('The backup credential is not authorized or its contents have been modified.');
        }
        if (! Schema::hasTable('backup_archive_credentials')) {
            throw new RuntimeException('The backup credential registry is unavailable. Run the database migrations before restoring.');
        }

        $registered = DB::table('backup_archive_credentials')->where('id', $credential['id'])->first();
        if ($registered !== null && (
            ! hash_equals($registered->credential_hash, hash('sha256', $credential['signature']))
            || $registered->archive_type !== $archiveType
            || $registered->archive_year !== $year
        )) {
            throw new RuntimeException('The backup credential does not match the credential registered in this database.');
        }
    }

    private function registerCredential(array $manifest, string $archiveType, ?int $year, ?int $generatedBy): void
    {
        $credential = $manifest['credential'];
        DB::table('backup_archive_credentials')->insertOrIgnore([
            'id' => $credential['id'],
            'credential_hash' => hash('sha256', $credential['signature']),
            'archive_type' => $archiveType,
            'archive_year' => $year,
            'generated_by' => $generatedBy,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $registered = DB::table('backup_archive_credentials')->where('id', $credential['id'])->first();
        if ($registered === null || ! hash_equals($registered->credential_hash, hash('sha256', $credential['signature']))) {
            throw new RuntimeException('The backup credential could not be attached to the database registry.');
        }
    }

    private function validateArchive(ZipArchive $zip): array
    {
        if ($zip->numFiles < 2 || $zip->numFiles > 100_000) {
            throw new RuntimeException('The backup has an invalid number of files.');
        }

        $manifestContents = $zip->getFromName('manifest.json');
        if ($manifestContents === false) {
            throw new RuntimeException('This ZIP has no SPES backup credential and cannot be restored.');
        }
        if (! is_string($manifestContents) || strlen($manifestContents) > 5_000_000) {
            throw new RuntimeException('The backup integrity manifest is invalid.');
        }
        try {
            $manifest = json_decode($manifestContents, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException('The backup integrity manifest is invalid.', previous: $exception);
        }

        if (($manifest['format'] ?? null) !== self::FORMAT_VERSION || ! is_array($manifest['hashes'] ?? null)) {
            throw new RuntimeException('This backup format is not supported. Create a new backup with the current version.');
        }
        $this->validateCredential($manifest);
        if (! isset($manifest['hashes']['database/backup.sql'], $manifest['hashes']['database/data.json'])) {
            throw new RuntimeException('The backup is missing its database contents.');
        }

        $totalSize = 0;
        $seen = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            $stat = $zip->statIndex($index);
            if (! is_string($name) || ! is_array($stat) || str_ends_with($name, '/')) {
                throw new RuntimeException('The backup contains an invalid archive entry.');
            }
            if ($this->isUnsafeArchivePath($name) || isset($seen[$name])) {
                throw new RuntimeException('The backup contains a duplicate or unsafe file path.');
            }
            $seen[$name] = true;
            $operatingSystem = 0;
            $attributes = 0;
            $zip->getExternalAttributesIndex($index, $operatingSystem, $attributes);
            if (($operatingSystem === ZipArchive::OPSYS_UNIX) && ((($attributes >> 16) & 0170000) === 0120000)) {
                throw new RuntimeException('The backup contains a symbolic link, which cannot be restored.');
            }
            $totalSize += (int) ($stat['size'] ?? 0);
            if ($totalSize > 4_000_000_000) {
                throw new RuntimeException('The backup exceeds the maximum uncompressed archive size.');
            }
            if ($name === 'database/data.json' && (int) ($stat['size'] ?? 0) > 268_435_456) {
                throw new RuntimeException('The database snapshot exceeds the supported 256 MB restore limit.');
            }

            if ($name !== 'manifest.json' && ! isset($manifest['hashes'][$name])) {
                throw new RuntimeException('The backup contains a file that is not listed in its integrity manifest.');
            }
            if ($name === 'manifest.json') {
                continue;
            }
            if (! preg_match('/^[a-f0-9]{64}$/D', (string) ($manifest['hashes'][$name] ?? ''))) {
                throw new RuntimeException('The backup manifest contains an invalid checksum.');
            }

            $stream = $zip->getStream($name);
            if ($stream === false) {
                throw new RuntimeException("Unable to read backup entry [{$name}].");
            }
            $context = hash_init('sha256');
            hash_update_stream($context, $stream);
            fclose($stream);
            if (! hash_equals($manifest['hashes'][$name], hash_final($context))) {
                throw new RuntimeException("Backup integrity check failed for [{$name}].");
            }
        }

        if (count($seen) !== count($manifest['hashes']) + 1) {
            throw new RuntimeException('The backup manifest does not match the archive contents.');
        }

        return $manifest;
    }

    private function validateLegacyArchive(ZipArchive $zip): array
    {
        if ($zip->numFiles < 2 || $zip->numFiles > 100_000) {
            throw new RuntimeException('The backup has an invalid number of files.');
        }

        $seen = [];
        $totalSize = 0;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            $stat = $zip->statIndex($index);
            if (! is_string($name) || ! is_array($stat) || str_ends_with($name, '/')
                || $this->isUnsafeLegacyArchivePath($name) || isset($seen[$name])) {
                throw new RuntimeException('The legacy backup contains an invalid or unsafe file path.');
            }
            $seen[$name] = true;
            $totalSize += (int) ($stat['size'] ?? 0);
            if ($totalSize > 4_000_000_000) {
                throw new RuntimeException('The backup exceeds the maximum uncompressed archive size.');
            }
            if ($name === 'database/backup.sql' && (int) ($stat['size'] ?? 0) > 268_435_456) {
                throw new RuntimeException('The legacy SQL dump exceeds the supported 256 MB restore limit.');
            }

            $operatingSystem = 0;
            $attributes = 0;
            $zip->getExternalAttributesIndex($index, $operatingSystem, $attributes);
            if ($operatingSystem === ZipArchive::OPSYS_UNIX && ((($attributes >> 16) & 0170000) === 0120000)) {
                throw new RuntimeException('The backup contains a symbolic link, which cannot be restored.');
            }
        }

        if (! isset($seen['database/backup.sql'], $seen['README.txt'])) {
            throw new RuntimeException('The uploaded ZIP is not a supported SPES backup.');
        }

        return ['format' => 'legacy', 'legacy' => true];
    }

    private function isUnsafeLegacyArchivePath(string $path): bool
    {
        if ($path === '' || str_contains($path, "\0") || str_contains($path, '\\') || str_starts_with($path, '/')) {
            return true;
        }
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return true;
            }
        }
        if ($this->isExecutablePath($path)) {
            return true;
        }

        return ! (
            in_array($path, ['README.txt', 'database/backup.sql'], true)
            || str_starts_with($path, 'uploads/private/')
            || str_starts_with($path, 'uploads/public/')
        );
    }

    private function snapshotFromLegacyDump(string $sql): array
    {
        $tables = [];
        foreach ($this->splitSqlStatements($sql) as $statement) {
            $statement = trim($statement);
            if ($statement === '' || str_starts_with($statement, '--')
                || preg_match('/^(?:PRAGMA\s+foreign_keys\s*=\s*(?:OFF|ON)|SET\s+FOREIGN_KEY_CHECKS\s*=\s*[01])$/iD', $statement)) {
                continue;
            }
            if (preg_match('/^(?:DROP\s+TABLE\s+IF\s+EXISTS|CREATE\s+TABLE)\s+/i', $statement)) {
                continue;
            }
            if (! preg_match('/^INSERT\s+INTO\s+`((?:``|[^`])+)`\s*\(/i', $statement, $match, PREG_OFFSET_CAPTURE)) {
                throw new RuntimeException('The legacy database dump contains an unsupported SQL statement.');
            }

            $table = str_replace('``', '`', $match[1][0]);
            $position = $match[0][1] + strlen($match[0][0]);
            $columns = $this->parseLegacyIdentifierList($statement, $position);
            $tail = substr($statement, $position);
            if (! preg_match('/^\s*VALUES\s*/i', $tail, $valuesMatch)) {
                throw new RuntimeException('The legacy database dump contains an unsupported INSERT statement.');
            }
            $position += strlen($valuesMatch[0]);
            $rows = [];
            while (true) {
                $this->skipSqlWhitespace($statement, $position);
                if (($statement[$position] ?? '') !== '(') {
                    break;
                }
                $position++;
                $values = [];
                while (true) {
                    $values[] = $this->parseLegacySqlValue($statement, $position);
                    $this->skipSqlWhitespace($statement, $position);
                    $delimiter = $statement[$position] ?? '';
                    $position++;
                    if ($delimiter === ')') {
                        break;
                    }
                    if ($delimiter !== ',') {
                        throw new RuntimeException('The legacy database dump contains malformed row data.');
                    }
                }
                if (count($values) !== count($columns)) {
                    throw new RuntimeException('The legacy database dump contains mismatched row data.');
                }
                $row = [];
                foreach ($columns as $columnIndex => $column) {
                    $row[$column] = $this->encodeSnapshotValue($values[$columnIndex]);
                }
                $rows[] = $row;
                $this->skipSqlWhitespace($statement, $position);
                if (($statement[$position] ?? '') !== ',') {
                    break;
                }
                $position++;
            }
            $this->skipSqlWhitespace($statement, $position);
            if ($position !== strlen($statement)) {
                throw new RuntimeException('The legacy database dump contains unexpected SQL after its row data.');
            }
            $tables[$table] = array_merge($tables[$table] ?? [], $rows);
        }

        return ['format' => 'spes-db-rows-v1', 'tables' => $tables];
    }

    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $quote = null;
        $length = strlen($sql);
        for ($index = 0; $index < $length; $index++) {
            $character = $sql[$index];
            if ($quote !== null) {
                $buffer .= $character;
                if ($quote === "'" && $character === '\\' && isset($sql[$index + 1])) {
                    $buffer .= $sql[++$index];
                } elseif ($character === $quote) {
                    if (($sql[$index + 1] ?? '') === $quote) {
                        $buffer .= $sql[++$index];
                    } else {
                        $quote = null;
                    }
                }

                continue;
            }
            if ($character === "'" || $character === '"' || $character === '`') {
                $quote = $character;
                $buffer .= $character;
            } elseif ($character === ';') {
                $statements[] = $buffer;
                $buffer = '';
            } else {
                $buffer .= $character;
            }
        }
        if ($quote !== null) {
            throw new RuntimeException('The legacy database dump contains an unterminated quoted value.');
        }
        if (trim($buffer) !== '') {
            $statements[] = $buffer;
        }

        return $statements;
    }

    private function parseLegacyIdentifierList(string $sql, int &$position): array
    {
        $identifiers = [];
        while (true) {
            $this->skipSqlWhitespace($sql, $position);
            if (($sql[$position] ?? '') !== '`') {
                throw new RuntimeException('The legacy database dump contains an invalid column name.');
            }
            $position++;
            $identifier = '';
            while (isset($sql[$position])) {
                if ($sql[$position] === '`') {
                    if (($sql[$position + 1] ?? '') === '`') {
                        $identifier .= '`';
                        $position += 2;

                        continue;
                    }
                    $position++;
                    break;
                }
                $identifier .= $sql[$position++];
            }
            if ($identifier === '') {
                throw new RuntimeException('The legacy database dump contains an empty column name.');
            }
            $identifiers[] = $identifier;
            $this->skipSqlWhitespace($sql, $position);
            $delimiter = $sql[$position] ?? '';
            $position++;
            if ($delimiter === ')') {
                return $identifiers;
            }
            if ($delimiter !== ',') {
                throw new RuntimeException('The legacy database dump contains a malformed column list.');
            }
        }
    }

    private function parseLegacySqlValue(string $sql, int &$position): mixed
    {
        $this->skipSqlWhitespace($sql, $position);
        if (preg_match('/\GNULL\b/i', $sql, $match, 0, $position)) {
            $position += strlen($match[0]);

            return null;
        }
        if (preg_match('/\G0x([a-f0-9]*)/i', $sql, $match, 0, $position)) {
            $position += strlen($match[0]);

            return hex2bin($match[1]) ?: '';
        }
        if (preg_match('/\GX\'([a-f0-9]*)\'/i', $sql, $match, 0, $position)) {
            $position += strlen($match[0]);

            return hex2bin($match[1]) ?: '';
        }
        if (($sql[$position] ?? '') === "'") {
            $position++;
            $value = '';
            while (isset($sql[$position])) {
                $character = $sql[$position++];
                if ($character === '\\' && isset($sql[$position])) {
                    $escaped = $sql[$position++];
                    $value .= match ($escaped) {
                        '0' => "\0",
                        'n' => "\n",
                        'r' => "\r",
                        'Z' => "\x1a",
                        default => $escaped,
                    };

                    continue;
                }
                if ($character === "'") {
                    if (($sql[$position] ?? '') === "'") {
                        $value .= "'";
                        $position++;

                        continue;
                    }

                    return $value;
                }
                $value .= $character;
            }
            throw new RuntimeException('The legacy database dump contains an unterminated string.');
        }
        if (preg_match('/\G-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?/', $sql, $match, 0, $position)) {
            $position += strlen($match[0]);

            return $match[0];
        }

        throw new RuntimeException('The legacy database dump contains a non-literal SQL value.');
    }

    private function skipSqlWhitespace(string $sql, int &$position): void
    {
        $length = strlen($sql);
        while ($position < $length && ctype_space($sql[$position])) {
            $position++;
        }
    }

    private function isUnsafeArchivePath(string $path): bool
    {
        if ($path === '' || str_contains($path, "\0") || str_contains($path, '\\') || str_starts_with($path, '/')) {
            return true;
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return true;
            }
        }
        if ($this->isExecutablePath($path)) {
            return true;
        }

        return ! (
            in_array($path, ['manifest.json', 'README.txt', 'database/backup.sql', 'database/data.json'], true)
            || str_starts_with($path, 'uploads/private/')
            || str_starts_with($path, 'uploads/public/')
            || str_starts_with($path, 'logs/')
        );
    }

    private function isExecutablePath(string $path): bool
    {
        $name = strtolower(basename($path));

        return in_array(pathinfo($name, PATHINFO_EXTENSION), [
            'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
            'html', 'htm', 'shtml', 'svg', 'js', 'mjs', 'cgi', 'pl', 'asp', 'aspx',
        ], true) || in_array($name, ['.htaccess', '.user.ini'], true);
    }

    private function validateSnapshot(mixed $snapshot): array
    {
        if (! is_array($snapshot) || ($snapshot['format'] ?? null) !== 'spes-db-rows-v1' || ! is_array($snapshot['tables'] ?? null)) {
            throw new RuntimeException('The backup database snapshot has an unsupported structure.');
        }

        $pdo = DB::connection()->getPdo();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $availableTables = array_flip($this->databaseTableNames($pdo, $driver));
        $validated = [];
        foreach ($snapshot['tables'] as $table => $rows) {
            if (is_string($table) && in_array($table, self::TRANSIENT_TABLES, true)) {
                continue;
            }
            if (! is_string($table) || ! isset($availableTables[$table]) || ! is_array($rows)) {
                throw new RuntimeException("The backup table [{$table}] is not available in this application.");
            }
            $columns = array_flip($this->databaseColumnNames($pdo, $driver, $table));
            $validated[$table] = [];
            foreach ($rows as $row) {
                if (! is_array($row) || array_is_list($row) && $row !== []) {
                    throw new RuntimeException("The backup contains an invalid row in table [{$table}].");
                }
                $normalizedRow = [];
                foreach ($row as $column => $value) {
                    if (
                        $table === 'applications'
                        && $column === 'resume'
                        && isset($columns['birth_certificate'])
                        && array_key_exists('birth_certificate', $row)
                    ) {
                        continue;
                    }
                    if ($table === 'applications' && $column === 'resume' && isset($columns['birth_certificate'])) {
                        $column = 'birth_certificate';
                    }
                    if (! is_string($column) || ! isset($columns[$column])) {
                        throw new RuntimeException("The backup contains an unknown column in table [{$table}].");
                    }
                    $normalizedRow[$column] = $this->decodeSnapshotValue($value);
                }
                $row = $normalizedRow;
                if ($table === 'applications') {
                    if (
                        is_string($row['birth_certificate'] ?? null)
                        && str_starts_with($row['birth_certificate'], 'applications/resumes/')
                    ) {
                        $row['birth_certificate'] = 'applications/birth_certificates/'.substr(
                            $row['birth_certificate'],
                            strlen('applications/resumes/'),
                        );
                    }
                    if (is_string($row['document_original_names'] ?? null)) {
                        $row['document_original_names'] = $this->renameLegacyDocumentOriginalName($row['document_original_names']);
                    }
                }
                $validated[$table][] = $row;
            }
        }

        return $validated;
    }

    private function databaseColumnNames(PDO $pdo, string $driver, string $table): array
    {
        $identifier = $this->quoteIdentifier($table);
        $statement = $driver === 'mysql'
            ? $pdo->query("SHOW COLUMNS FROM {$identifier}")
            : $pdo->query("PRAGMA table_info({$identifier})");
        $columnIndex = $driver === 'mysql' ? 'Field' : 'name';

        return array_map(
            static fn (array $column): string => (string) $column[$columnIndex],
            $statement->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    private function mergeRows(array $tables, array $manifest): int
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        if ($driver === 'sqlite') {
            $connection->statement('PRAGMA foreign_keys=OFF');
        } elseif ($driver === 'mysql') {
            $connection->statement('SET FOREIGN_KEY_CHECKS=0');
        }

        $importedRows = 0;
        try {
            $connection->transaction(function () use ($tables, &$importedRows, $driver, $connection, $manifest): void {
                foreach ($tables as $table => $rows) {
                    foreach (array_chunk($rows, 200) as $batch) {
                        if ($batch !== []) {
                            $importedRows += DB::table($table)->insertOrIgnore($batch);
                        }
                    }
                }
                if ($driver === 'sqlite') {
                    if ($connection->select('PRAGMA foreign_key_check') !== []) {
                        throw new RuntimeException('The backup contains database records with missing related records.');
                    }
                }
                $this->registerCredential(
                    $manifest,
                    $manifest['archive_type'],
                    $manifest['archive_year'],
                    null,
                );
            });
        } finally {
            if ($driver === 'sqlite') {
                $connection->statement('PRAGMA foreign_keys=ON');
            } elseif ($driver === 'mysql') {
                $connection->statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }

        return $importedRows;
    }

    private function restoreFiles(ZipArchive $zip, array &$createdFiles): void
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (! is_string($name)) {
                continue;
            }

            if (str_starts_with($name, 'uploads/private/')) {
                $disk = 'local';
                $relativePath = substr($name, strlen('uploads/private/'));
            } elseif (str_starts_with($name, 'uploads/public/')) {
                $relativePath = substr($name, strlen('uploads/public/'));
                $disk = str_starts_with($relativePath, 'applications/') ? 'local' : 'public';
                if (str_starts_with($relativePath, 'applications/resumes/')) {
                    $relativePath = 'applications/birth_certificates/'.substr(
                        $relativePath,
                        strlen('applications/resumes/'),
                    );
                }
            } elseif (str_starts_with($name, 'logs/')) {
                $disk = null;
                $relativePath = substr($name, strlen('logs/'));
            } else {
                continue;
            }

            if ($disk === null) {
                $targetPath = storage_path('logs'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
                $exists = is_file($targetPath);
            } else {
                $storage = Storage::disk($disk);
                $targetPath = null;
                $exists = $storage->exists($relativePath);
            }
            if ($exists) {
                continue;
            }

            $stream = $zip->getStream($name);
            if ($stream === false) {
                throw new RuntimeException("Unable to read backup file [{$name}].");
            }
            if ($disk === null) {
                $directory = dirname((string) $targetPath);
                if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
                    fclose($stream);
                    throw new RuntimeException("Unable to create the log directory for [{$name}].");
                }
                $target = fopen((string) $targetPath, 'xb');
                if ($target === false) {
                    fclose($stream);
                    throw new RuntimeException("Unable to restore log file [{$name}].");
                }
                $copied = stream_copy_to_stream($stream, $target);
                fclose($target);
                fclose($stream);
                if ($copied === false) {
                    @unlink((string) $targetPath);
                    throw new RuntimeException("Unable to finish restoring log file [{$name}].");
                }
                $createdFiles[] = ['path' => (string) $targetPath, 'disk' => null];
            } else {
                try {
                    $written = $storage->writeStream($relativePath, $stream);
                } catch (\Throwable $exception) {
                    $storage->delete($relativePath);
                    throw $exception;
                } finally {
                    fclose($stream);
                }
                if (! $written) {
                    $storage->delete($relativePath);
                    throw new RuntimeException("Unable to restore stored file [{$name}].");
                }
                $createdFiles[] = ['path' => $relativePath, 'disk' => $disk];
            }

        }
    }

    private function renameLegacyDocumentOriginalName(string $encodedNames): string
    {
        $names = json_decode($encodedNames, true);
        if (! is_array($names) || ! array_key_exists('resume', $names)) {
            return $encodedNames;
        }

        if (! array_key_exists('birth_certificate', $names)) {
            $names['birth_certificate'] = $names['resume'];
        }
        unset($names['resume']);

        return json_encode($names, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function removeRestoredFiles(array $createdFiles): void
    {
        foreach ($createdFiles as $file) {
            if ($file['disk'] === null) {
                if (is_file($file['path'])) {
                    @unlink($file['path']);
                }
            } else {
                Storage::disk($file['disk'])->delete($file['path']);
            }
        }
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }

    private function quoteValue(PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_string($value) && str_contains($value, "\0")) {
            return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
                ? "X'".bin2hex($value)."'"
                : '0x'.bin2hex($value);
        }

        $quoted = $pdo->quote((string) $value);
        if ($quoted === false) {
            throw new RuntimeException('Unable to safely encode a database value for backup.');
        }

        return $quoted;
    }
}
