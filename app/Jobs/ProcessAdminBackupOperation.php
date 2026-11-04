<?php

namespace App\Jobs;

use App\Models\AdminBackupOperation;
use App\Models\AuditAction;
use App\Models\User;
use App\Services\AdminBackupService;
use App\Services\AuditLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProcessAdminBackupOperation implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(public string $operationId) {}

    public function handle(AdminBackupService $backupService, AuditLogger $auditLogger): void
    {
        $operation = AdminBackupOperation::query()->findOrFail($this->operationId);
        if ($operation->status === 'completed' || $operation->status === 'failed') {
            return;
        }

        $operation->update([
            'status' => 'processing',
            'started_at' => now(),
            'completed_at' => null,
            'failure_message' => null,
        ]);
        $startedAt = microtime(true);

        try {
            if ($operation->action === 'create') {
                $result = $this->createBackup($operation, $backupService);
            } elseif ($operation->action === 'restore') {
                $lock = Cache::lock('admin-backup-recovery', 1200);
                if (! $lock->get()) {
                    throw new RuntimeException('Another recovery operation is already in progress.');
                }
                try {
                    $result = $this->restoreBackup($operation, $backupService);
                } finally {
                    $lock->release();
                }
            } else {
                throw new RuntimeException('The backup operation action is invalid.');
            }

            $operation->update([
                'status' => 'completed',
                'result' => $result,
                'file_size_bytes' => is_string($operation->archive_path)
                    ? (Storage::disk('local')->exists($operation->archive_path)
                        ? Storage::disk('local')->size($operation->archive_path)
                        : null)
                    : null,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'completed_at' => now(),
                'verified_at' => now(),
                'failure_message' => null,
            ]);
            $this->recordAudit($operation, $result, $auditLogger);
        } catch (Throwable $exception) {
            $operation->update([
                'status' => 'failed',
                'completed_at' => now(),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'failure_message' => __('The backup operation failed. Check the application log or contact the system administrator.'),
            ]);
            Log::error('Admin backup operation failed.', [
                'operation_id' => $operation->id,
                'admin_id' => $operation->admin_id,
                'exception' => $exception,
            ]);
            $this->recordFailureAudit($operation);

            return;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $operation = AdminBackupOperation::query()->find($this->operationId);
        if ($operation === null) {
            Log::error('Admin backup operation record was missing after a job failure.', [
                'operation_id' => $this->operationId,
                'exception' => $exception,
            ]);

            return;
        }

        $operation->update([
            'status' => 'failed',
            'failure_message' => __('The backup operation failed. Check the application log or contact the system administrator.'),
        ]);

        if ($operation->action === 'create') {
            Storage::disk('local')->delete("backups/operations/{$operation->id}.zip");
        }

        Log::error('Admin backup operation failed after all retries.', [
            'operation_id' => $operation->id,
            'admin_id' => $operation->admin_id,
            'exception' => $exception,
        ]);
        $this->recordFailureAudit($operation);
    }

    private function recordFailureAudit(AdminBackupOperation $operation): void
    {
        try {
            $admin = $operation->admin_id === null ? null : User::query()->find($operation->admin_id);
            app(AuditLogger::class)->record(
                AuditAction::DATABASE_BACKUP_FAILED,
                'System Backup',
                $admin,
                null,
                null,
                [
                    'description' => __('A :action backup operation failed.', ['action' => $operation->action]),
                    'operation_id' => $operation->id,
                ],
            );
        } catch (Throwable $auditException) {
            Log::critical('A failed backup operation could not be recorded in the audit log.', [
                'operation_id' => $operation->id,
                'exception' => $auditException,
            ]);
        }
    }

    private function createBackup(AdminBackupOperation $operation, AdminBackupService $backupService): array
    {
        if ($operation->archive_type === 'daily') {
            $archive = $backupService->createDailyArchive();
            if ($archive === null) {
                throw new RuntimeException('A verified daily backup already exists for this date.');
            }
            $operation->update([
                'archive_path' => $archive['path'],
                'status' => 'verifying',
            ]);

            return [
                'archive_path' => $archive['path'],
                'filename' => basename($archive['path']),
            ];
        }

        $temporaryPath = $backupService->create('manual', null, $operation->admin_id);
        $archivePath = "backups/operations/{$operation->id}.zip";
        $stream = fopen($temporaryPath, 'rb');
        if ($stream === false) {
            @unlink($temporaryPath);
            throw new RuntimeException('Unable to open the generated backup archive.');
        }

        try {
            if (! Storage::disk('local')->writeStream($archivePath, $stream)) {
                throw new RuntimeException('Unable to save the generated backup archive.');
            }
        } finally {
            fclose($stream);
            @unlink($temporaryPath);
        }

        $operation->update(['archive_path' => $archivePath]);
        $operation->update(['status' => 'verifying']);

        return [
            'archive_path' => $archivePath,
            'filename' => 'spes-backup-'.now()->format('Ymd-His').'.zip',
        ];
    }

    private function restoreBackup(AdminBackupOperation $operation, AdminBackupService $backupService): array
    {
        if (! is_string($operation->archive_path) || ! Storage::disk('local')->exists($operation->archive_path)) {
            throw new RuntimeException('The uploaded backup archive is no longer available.');
        }

        $result = $backupService->restore(
            Storage::disk('local')->path($operation->archive_path),
            $operation->archive_type,
            $operation->archive_year,
        );
        $source = $operation->archive_type === 'annual'
            ? __('Annual backup :year', ['year' => $operation->archive_year])
            : __('Uploaded backup');

        return [
            'rows' => $result['rows'],
            'files' => $result['files'],
            'message' => __('Backup merged successfully: :rows new database rows and :files files added. Existing records and files were kept.', [
                'rows' => $result['rows'],
                'files' => $result['files'],
            ]),
            'source' => $source,
        ];
    }

    private function recordAudit(
        AdminBackupOperation $operation,
        array $result,
        AuditLogger $auditLogger,
    ): void {
        $admin = $operation->admin_id === null ? null : User::query()->find($operation->admin_id);

        try {
            if ($operation->action === 'create') {
                $auditLogger->record(
                    AuditAction::DATABASE_BACKUP_GENERATED,
                    'System Backup',
                    $admin,
                    null,
                    null,
                    ['description' => 'Database and stored-file backup archive generated'],
                );

                return;
            }

            $auditLogger->record(
                AuditAction::DATABASE_BACKUP_RESTORED,
                'System Backup',
                $admin,
                null,
                null,
                [
                    'description' => __(':source merged: :rows database rows and :files files added', [
                        'source' => $result['source'],
                        'rows' => $result['rows'],
                        'files' => $result['files'],
                    ]),
                ],
            );
        } catch (Throwable $exception) {
            Log::critical('Backup data completed, but its audit entry could not be recorded.', [
                'operation_id' => $operation->id,
                'admin_id' => $admin?->id,
                'exception' => $exception,
            ]);
        }
    }
}
