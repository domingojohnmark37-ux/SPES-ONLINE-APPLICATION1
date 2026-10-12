<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\User;
use App\Notifications\ApplicantPortalUpdate;
use App\Services\ApplicantNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class NotifyApplicantsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public int $timeout = 120;

    /**
     * @param array<string, mixed> $data
     * @param array<int, int> $userIds
     */
    public function __construct(
        public string $audience,
        public string $eventKey,
        public string $type,
        public string $title,
        public string $body,
        public array $data = [],
        public ?int $audienceId = null,
        public array $userIds = [],
    ) {}

    public function handle(ApplicantNotificationService $notifications): void
    {
        $applicants = match ($this->audience) {
            'all' => User::query()->where('role', 'user'),
            'appointment' => $this->appointmentRecipients(),
            'users' => User::query()->whereIn('id', $this->userIds),
            default => throw new InvalidArgumentException('The applicant notification audience is invalid.'),
        };

        $notifications->notifyUsers(
            $applicants,
            new ApplicantPortalUpdate($this->type, $this->title, $this->body, $this->data),
            $this->eventKey,
        );
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('SPES applicant notification job failed.', [
            'audience' => $this->audience,
            'audience_id' => $this->audienceId,
            'event_key' => $this->eventKey,
            'exception' => $exception ? class_basename($exception) : null,
        ]);
    }

    private function appointmentRecipients()
    {
        return Appointment::query()
            ->findOrFail($this->audienceId)
            ->recipientUsers();
    }
}
