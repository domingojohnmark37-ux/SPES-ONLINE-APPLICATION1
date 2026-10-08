<?php

namespace Tests\Feature;

use App\Models\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_logging_records_only_normalized_changed_fields(): void
    {
        $applicant = User::factory()->create(['role' => 'user']);

        app(AuditLogger::class)->recordChanges(
            ['barangay' => ' Bical ', 'email' => 'student@example.com', 'password' => 'old-hash'],
            ['barangay' => 'Alibago', 'email' => ' student@example.com ', 'password' => 'new-hash'],
            'Applicant Information',
            $applicant,
            $applicant,
            null,
            AuditAction::INFORMATION_UPDATED,
        );

        $this->assertDatabaseCount('audit_logs', 1);
        $log = AuditLog::query()->firstOrFail();
        $this->assertSame('barangay', $log->field_name);
        $this->assertSame('Bical', $log->old_value);
        $this->assertSame('Alibago', $log->new_value);
        $this->assertSame($applicant->id, $log->applicant_id);
        $this->assertMatchesRegularExpression('/^AUD-\d{4}-\d{6,}$/', $log->audit_code);
    }

    public function test_password_change_never_stores_password_values(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $log = app(AuditLogger::class)->recordPasswordChanged($user);

        $this->assertSame(AuditAction::PASSWORD_CHANGED, $log->action);
        $this->assertNull($log->old_value);
        $this->assertNull($log->new_value);
        $this->assertSame('Password changed', $log->description);
    }

    public function test_audit_records_cannot_be_updated_or_deleted(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $log = app(AuditLogger::class)->record(
            AuditAction::ACCOUNT_CREATED,
            'Authentication',
            $user,
            $user,
        );

        try {
            $log->update(['description' => 'Changed']);
            $this->fail('Updating an audit log should throw.');
        } catch (LogicException $exception) {
            $this->assertSame('Audit records are immutable.', $exception->getMessage());
        }

        try {
            $log->delete();
            $this->fail('Deleting an audit log should throw.');
        } catch (LogicException $exception) {
            $this->assertSame('Audit records cannot be deleted.', $exception->getMessage());
        }
    }
}
