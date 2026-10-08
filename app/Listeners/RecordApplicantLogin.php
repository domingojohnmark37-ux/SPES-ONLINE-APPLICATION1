<?php

namespace App\Listeners;

use App\Models\ApplicantLoginActivity;
use App\Models\AuditAction;
use App\Models\User;
use App\Notifications\ApplicantLoginAlert;
use App\Services\AuditLogger;
use App\Support\DeviceIdentifier;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;

class RecordApplicantLogin
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User || ! in_array($event->user->role, ['admin', 'user'], true)) {
            return;
        }

        $request = request();
        $activity = DB::transaction(function () use ($event, $request): ?ApplicantLoginActivity {
            $activity = null;
            if ($event->user->role === 'user') {
                $activity = ApplicantLoginActivity::create([
                    'user_id' => $event->user->getAuthIdentifier(),
                    'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                    'ip_address' => $request->ip(),
                    'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
                    'logged_in_at' => now(),
                ]);

                if ($request->hasSession()) {
                    $request->session()->put('applicant_login_activity_id', $activity->id);
                }
            }

            $applicant = $event->user->role === 'user' ? $event->user : null;
            $this->auditLogger->record(
                AuditAction::LOGIN_SUCCESS,
                'Authentication',
                $event->user,
                $applicant,
                $applicant?->applications()->latest('created_at')->first(),
            );

            return $activity;
        });

        $event->user->notify(new ApplicantLoginAlert(
            $activity?->id,
            DeviceIdentifier::describe((string) $request->userAgent()),
            $request->ip(),
        ));
    }
}
