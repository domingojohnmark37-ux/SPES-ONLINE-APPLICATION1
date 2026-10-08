<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Lockout;

class RecordAuditLockout
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function handle(Lockout $event): void
    {
        $candidate = User::query()
            ->where('email', $event->request->input('email'))
            ->first();

        $this->auditLogger->recordFailedLoginAttempt($candidate);
    }
}
