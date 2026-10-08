<?php

namespace App\Listeners;

use App\Models\AuditAction;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Logout;

class RecordAuditLogout
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function handle(Logout $event): void
    {
        if (! $event->user instanceof User || ! in_array($event->user->role, ['admin', 'user'], true)) {
            return;
        }

        $applicant = $event->user->role === 'user' ? $event->user : null;
        $this->auditLogger->record(
            AuditAction::LOGOUT,
            'Authentication',
            $event->user,
            $applicant,
            $applicant?->applications()->latest('created_at')->first(),
        );
    }
}
