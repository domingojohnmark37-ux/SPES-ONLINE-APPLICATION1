<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use App\Notifications\ApplicantPortalUpdate;

class ApplicantNotificationService
{
    public function notifyApplicants(ApplicantPortalUpdate $notification): void
    {
        $this->notifyUsers(User::query(), $notification);
    }

    public function notifyUsers(Builder $applicants, ApplicantPortalUpdate $notification): void
    {
        $applicants
            ->where('role', 'user')
            ->orderBy('id')
            ->chunkById(100, function ($applicants) use ($notification): void {
                foreach ($applicants as $applicant) {
                    $applicant->notify($notification);
                }
            });
    }
}
