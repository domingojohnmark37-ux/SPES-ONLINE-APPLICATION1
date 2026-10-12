<?php

namespace App\Jobs;

use App\Models\Application;
use App\Notifications\ApplicantPortalUpdate;
use App\Services\ApplicantNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotifyClosedApplicantsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public int $timeout = 120;

    /**
     * @param array<int, int> $applicationIds
     */
    public function __construct(
        public array $applicationIds,
        public int $limit,
    ) {}

    public function handle(ApplicantNotificationService $notifications): void
    {
        foreach (array_chunk($this->applicationIds, 100) as $applicationIds) {
            Application::query()
                ->with('user')
                ->whereIn('id', $applicationIds)
                ->orderBy('id')
                ->get()
                ->each(function (Application $application) use ($notifications): void {
                    $notifications->notifyApplicant($application->user, new ApplicantPortalUpdate(
                        'notify_documents',
                        'SPES application period closed',
                        "The SPES program has reached its limit of {$this->limit} approved applicants. Your application was not included in the approved list. Please try again next SPES season.",
                        [
                            'application_id' => $application->id,
                            'status' => 'denied',
                            'event' => 'approval_limit_reached',
                            'approved_applicant_limit' => $this->limit,
                        ],
                    ), "application:{$application->id}:approval-limit-reached");
                });
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('SPES closed-applicant notification job failed.', [
            'application_ids' => $this->applicationIds,
            'approved_applicant_limit' => $this->limit,
            'exception' => $exception ? class_basename($exception) : null,
        ]);
    }
}
