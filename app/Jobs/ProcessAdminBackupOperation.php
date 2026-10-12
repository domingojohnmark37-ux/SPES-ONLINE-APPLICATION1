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

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public int $timeout = 900;

    public function __construct(public string $operationId)
    {
    }

    public function handle(AdminBackupService $backupService, AuditLogger $auditLogger): void
    {
        $operation = AdminBackupOperation::query()->findOrFail($this->operationId);
        if ($operation->status === 'done') {
            return;
        }

        $operation->update([
            'status' => 'running',
            'failure_message' => null,
        ]);

        try {
            if ($operation->action === 'create') {
                $result = $this->createBackup($operation, $backupService);
            } elseif ($operation->action === 'restore') {
                $result = $this->restoreBackup($operation, $backupService);
            } else {
                throw new RuntimeException('The backup operation action is invalid.');
            }

            $operation->update([
                'status' => 'done',
                'result' => $result,
                'failure_message' => null,
            ]);
            $this->recordAudit($operation, $result, $auditLogger);
        } catch (Throwable $exception) {
            $operation->update(['status' => 'queued']);
            Log::warning('Admin backup operation attempt failed.', [
                'operation_id' => $operation->id,
                'admin_id' => $operation->admin_id,
                'attempt' => $this->attempts(),
                'exception' => $exception,
            ]);

            throw $exception;
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

        if (
            $operation->action === 'restore'
            && $operation->archive_type !== 'annual'
            && $operation->archive_path !== null
        ) {
            Storage::disk('local')->delete($operation->archive_path);
        } elseif ($operation->action === 'create') {
            Storage::disk('local')->delete("backups/operations/{$operation->id}.zip");
        }

        Log::error('Admin backup operation failed after all retries.', [
            'operation_id' => $operation->id,
            'admin_id' => $operation->admin_id,
            'exception' => $exception,
        ]);
    }

    private function createBackup(AdminBackupOperation $operation, AdminBackupService $backupService): array
    {
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
        if ($operation->archive_type !== 'annual') {
            Storage::disk('local')->delete($operation->archive_path);
        }
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
        $admin = User::query()->find($operation->admin_id);
        if ($admin === null) {
            Log::critical('Backup operation completed, but its administrator could not be loaded for audit.', [
                'operation_id' => $operation->id,
                'admin_id' => $operation->admin_id,
            ]);

            return;
        }

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
                'admin_id' => $admin->id,
                'exception' => $exception,
            ]);
        }
    }
}
