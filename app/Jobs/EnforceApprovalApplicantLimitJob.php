<?php

namespace App\Jobs;

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\ApplicationApprovalCapacity;
use App\Services\AuditLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class EnforceApprovalApplicantLimitJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public int $timeout = 900;

    public function __construct(public int $actorId) {}

    public function handle(
        ApplicationApprovalCapacity $capacity,
        AuditLogger $auditLogger,
    ): void {
        $actor = User::query()->findOrFail($this->actorId);
        $pendingApplicationIds = [];
        $limit = 0;

        DB::transaction(function () use ($capacity, $auditLogger, $actor, &$pendingApplicationIds, &$limit): void {
            $settings = SystemSetting::query()->lockForUpdate()->firstOrFail();
            if (! $capacity->isFull($settings)) {
                return;
            }

            $limit = (int) $settings->approved_applicant_limit;
            $pendingApplicationIds = $capacity->denyRemainingPending($settings, $actor, $auditLogger)
                ->pluck('id')
                ->all();
        });

        if ($pendingApplicationIds !== []) {
            NotifyClosedApplicantsJob::dispatch($pendingApplicationIds, $limit)->afterCommit();
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('SPES approval-limit enforcement job failed.', [
            'admin_id' => $this->actorId,
            'exception' => $exception ? class_basename($exception) : null,
        ]);
    }
}
