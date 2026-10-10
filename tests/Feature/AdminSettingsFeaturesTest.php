<?php

namespace Tests\Feature;

use App\Models\AuditAction;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AdminBackupService;
use App\Services\AuditLogger;
use App\Support\AdminDateFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\UncompromisedVerifier;
use Tests\TestCase;
use ZipArchive;

class AdminSettingsFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_settings_dropdown_opens_each_feature_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.preferences'))
            ->assertOk()
            ->assertSee('Settings sections', false)
            ->assertSee('Preferences')
            ->assertSee('Manual')
            ->assertSee('Admin Audit Logs')
            ->assertSee('Backup')
            ->assertSee('Admin Guide')
            ->assertSee('All administrators')
            ->assertSee('No administrator audit activity matches the selected filters.')
            ->assertSee('Create and Download Backup')
            ->assertDontSee('admin-settings-submenu')
            ->assertSee('href="'.route('admin.preferences').'"', false)
            ->assertSee('href="#manual"', false)
            ->assertSee('href="#admin-audit"', false)
            ->assertSee('href="#backup"', false);

        $this->get(route('admin.manual'))
            ->assertRedirect(route('admin.preferences').'#manual');
        $this->get(route('admin.applicant-audit.index'))
            ->assertOk()
            ->assertSee('Recent Applicant Activity')
            ->assertDontSee('User Audit Logs')
            ->assertSee('Statistics &amp; Audit', false);
        $this->get('/admin/applicant-audit/admins')->assertNotFound();
        $this->get(route('admin.security'))->assertRedirect(route('admin.preferences').'#admin-audit');
        $this->get(route('admin.backup.index'))->assertRedirect(route('admin.preferences').'#backup');
    }

    public function test_admin_can_save_appearance_preference_and_apply_it_to_admin_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.preferences'))
            ->put(route('admin.preferences.update'), [
                'appearance' => 'dark',
                'language' => 'fil',
                'sidebar_behavior' => 'collapsed',
                'font_size' => 'large',
            ])
            ->assertRedirect(route('admin.preferences').'#preferences');

        $this->assertDatabaseHas('admin_preferences', [
            'user_id' => $admin->id,
            'theme' => 'dark',
            'language' => 'fil',
            'sidebar_behavior' => 'collapsed',
            'font_size' => 'large',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditAction::ADMIN_PREFERENCES_UPDATED,
        ]);

        $admin->load('adminPreference');
        $this->assertSame(
            'Oct 3, 2026 · 8:00 PM',
            app(AdminDateFormatter::class)->format(now()->setTimezone('UTC')->setDate(2026, 10, 3)->setTime(12, 0), true),
        );

        $this->get(route('admin.preferences'))
            ->assertOk()
            ->assertSee('<html lang="fil"', false)
            ->assertSee('data-admin-theme="dark"', false)
            ->assertSee('data-admin-sidebar="collapsed"', false)
            ->assertSee('data-admin-font-size="large"', false)
            ->assertSee('for="appearance"', false)
            ->assertSee('for="language"', false)
            ->assertSee('for="sidebar_behavior"', false)
            ->assertSee('for="font_size"', false)
            ->assertSee('Wika')
            ->assertSee('Gabay ng Admin')
            ->assertSee('Suriin ang mga naisumiteng aplikasyon')
            ->assertSee('Buksan ang Pamamahala')
            ->assertSee('Taunang iskedyul ng backup')
            ->assertSee('Gumagawa ng kumpletong taunang backup')
            ->assertSee('Pangalagaan ang datos ng aplikante')
            ->assertSee('Mag-download ng Backup')
            ->assertSee('Kasama sa archive ang lahat ng talahanayan ng database')
            ->assertSee('Ibalik / Isama ang Backup')
            ->assertSee('Hindi kailanman isinasagawa ang SQL file')
            ->assertSee('Mga Tala ng Audit ng Admin')
            ->assertSee('Saklaw ng Petsa')
            ->assertSee('Na-update ang Mga Kagustuhan ng Admin')
            ->assertSee('I-type ang MERGE upang kumpirmahin')
            ->assertSee('Gumagawa ang system ng kumpletong pribadong archive');

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('<html lang="fil"', false)
            ->assertSee('data-admin-theme="dark"', false)
            ->assertSee('data-admin-sidebar="collapsed"', false)
            ->assertSee('data-admin-font-size="large"', false)
            ->assertSee('Mga Aplikasyon')
            ->assertSee('Mga Setting');
    }

    public function test_admin_preference_save_reports_pending_columns_without_throwing_a_server_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Schema::shouldReceive('hasColumns')
            ->once()
            ->with('admin_preferences', ['language', 'sidebar_behavior', 'font_size'])
            ->andReturn(false);

        $this->actingAs($admin)
            ->from(route('admin.preferences'))
            ->put(route('admin.preferences.update'), [
                'appearance' => 'dark',
                'language' => 'fil',
                'sidebar_behavior' => 'collapsed',
                'font_size' => 'large',
            ])
            ->assertRedirect(route('admin.preferences'))
            ->assertSessionHasErrors('preferences');
    }

    public function test_admin_audit_logs_in_settings_can_be_filtered_by_administrator(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'First Admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin', 'name' => 'Second Admin']);

        app(AuditLogger::class)->record(AuditAction::LOGIN_SUCCESS, 'Authentication', $admin);
        app(AuditLogger::class)->record(AuditAction::LOGOUT, 'Authentication', $otherAdmin);

        $this->actingAs($admin)
            ->get(route('admin.preferences', ['user_id' => $admin->id]))
            ->assertOk()
            ->assertSee('Admin Audit Logs')
            ->assertSee('First Admin')
            ->assertSee('Second Admin')
            ->assertSee('Login Success')
            ->assertDontSee('<td>Logout</td>', false);
    }

    public function test_admin_audit_logs_are_scrollable_without_pagination(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        for ($index = 0; $index < 25; $index++) {
            app(AuditLogger::class)->record(
                AuditAction::LOGIN_SUCCESS,
                'Authentication',
                $admin,
                null,
                null,
                ['description' => 'Distinct admin event '.$index],
            );
        }

        $response = $this->actingAs($admin)
            ->get(route('admin.preferences', ['admin_audit_page' => 2]))
            ->assertOk()
            ->assertSee('aria-label="Administrator audit log entries"', false)
            ->assertDontSee('admin_audit_page=', false)
            ->assertDontSee('Showing 21–25 of 25 admin audit logs')
            ->assertDontSee('Show more admin audit logs')
            ->assertDontSee('Show recent 5');

        $this->assertSame(25, substr_count($response->getContent(), '<td>Login Success</td>'));
    }

    public function test_security_policy_updates_are_audited_and_applied_to_admin_sessions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.security'))
            ->put(route('admin.security.update'), [
                'admin_minimum_password_length' => 16,
                'admin_session_timeout_minutes' => 15,
            ])
            ->assertRedirect(route('admin.preferences').'#admin-audit');

        $this->assertSame(16, (int) SystemSetting::current()->admin_minimum_password_length);
        $this->assertSame(15, (int) SystemSetting::current()->admin_session_timeout_minutes);
        $this->assertSame(
            2,
            AuditLog::query()->where('action', AuditAction::SECURITY_POLICY_UPDATED)->count(),
            AuditLog::query()->where('action', AuditAction::SECURITY_POLICY_UPDATED)->get(['field_name', 'old_value', 'new_value'])->toJson(),
        );

        $this->travelTo(now()->addMinutes(20));
        $this->withSession(['admin_last_activity_at' => now()->subMinutes(16)->timestamp])
            ->get(route('admin.manual'))
            ->assertRedirect(route('login'));
    }

    public function test_minimum_password_length_policy_is_enforced_for_admin_password_changes(): void
    {
        $verifier = \Mockery::mock(UncompromisedVerifier::class);
        $verifier->shouldReceive('verify')->never();
        $this->app->instance(UncompromisedVerifier::class, $verifier);
        $admin = User::factory()->create(['role' => 'admin']);
        SystemSetting::current()->update(['admin_minimum_password_length' => 16]);

        $this->actingAs($admin)
            ->put(route('password.update'), [
                'current_password' => 'password',
                'password' => 'StrongPass1!',
                'password_confirmation' => 'StrongPass1!',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'password');
    }

    public function test_backup_contains_database_and_private_and_public_uploads(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('local')->put('applications/private-proof.pdf', 'private document');
        Storage::disk('public')->put('applications/resume.pdf', 'public document');
        $logName = 'backup-test-'.uniqid('', true).'.log';
        $logPath = storage_path('logs/'.$logName);
        file_put_contents($logPath, 'backup log fixture');

        $archivePath = app(AdminBackupService::class)->create();
        $archive = new ZipArchive;

        try {
            $this->assertTrue($archive->open($archivePath));
            $this->assertNotFalse($archive->locateName('database/backup.sql'));
            $this->assertNotFalse($archive->locateName('database/data.json'));
            $this->assertNotFalse($archive->locateName('manifest.json'));
            $this->assertNotFalse($archive->locateName('uploads/private/applications/private-proof.pdf'));
            $this->assertNotFalse($archive->locateName('uploads/public/applications/resume.pdf'));
            $this->assertNotFalse($archive->locateName('logs/'.$logName));
            $this->assertNotFalse($archive->locateName('README.txt'));
            $archive->close();
        } finally {
            @unlink($archivePath);
            @unlink($logPath);
        }
    }

    public function test_annual_backup_is_saved_privately_and_not_nested_in_future_backups(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $backupService = app(AdminBackupService::class);

        $archive = $backupService->createAnnualArchive(2025);

        $this->assertNotNull($archive);
        $this->assertSame('backups/annual/spes-annual-backup-2025.zip', $archive['path']);
        Storage::disk('local')->assertExists($archive['path']);
        $this->assertSame(2025, $backupService->annualArchives()[0]['year']);
        $this->assertNull($backupService->createAnnualArchive(2025));

        $regularBackupPath = $backupService->create();
        $regularBackup = new ZipArchive;
        try {
            $this->assertTrue($regularBackup->open($regularBackupPath));
            $this->assertFalse($regularBackup->locateName($archive['path']));
            $regularBackup->close();
        } finally {
            @unlink($regularBackupPath);
        }
    }

    public function test_annual_backup_command_is_idempotent_and_creates_an_audit_record(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $year = (int) now(config('app.timezone'))->year;

        Artisan::call('backup:annual');
        Artisan::call('backup:annual');

        Storage::disk('local')->assertExists("backups/annual/spes-annual-backup-{$year}.zip");
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::DATABASE_BACKUP_GENERATED,
            'module' => 'System Backup',
            'actor_role' => 'system',
        ]);
    }

    public function test_annual_backup_schedule_runs_on_december_31_at_1159_pm_in_app_timezone(): void
    {
        $event = collect(Schedule::events())->first(
            fn ($scheduledEvent): bool => $scheduledEvent->expression === '59 23 31 12 *',
        );

        $this->assertNotNull($event);
        $this->assertSame('59 23 31 12 *', $event->expression);
        $this->assertSame('spes-annual-backup', $event->description);
        $this->assertSame(config('app.timezone'), $event->timezone);
    }

    public function test_admin_can_download_and_merge_an_annual_backup_archive(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'user', 'name' => 'Annual Backup Applicant']);
        $year = 2024;
        $archive = app(AdminBackupService::class)->createAnnualArchive($year);
        $applicantId = $applicant->id;
        $applicant->delete();

        $this->actingAs($admin)
            ->get(route('admin.preferences'))
            ->assertOk()
            ->assertSee('Automatic Annual Archives')
            ->assertSee((string) $year)
            ->assertSee(route('admin.backup.archive.download', $year), false);

        $this->get(route('admin.backup.archive.download', $year))
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');

        $this->post(route('admin.backup.archive.restore', $year), [
            'current_password' => 'password',
            'confirmation' => "RESTORE {$year}",
        ])->assertRedirect(route('admin.preferences').'#backup')
            ->assertSessionHas('success');

        $this->assertSame('Annual Backup Applicant', User::findOrFail($applicantId)->name);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditAction::DATABASE_BACKUP_DOWNLOADED,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditAction::DATABASE_BACKUP_RESTORED,
        ]);
    }

    public function test_annual_backup_can_be_restored_from_the_console_for_disaster_recovery(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $applicant = User::factory()->create(['role' => 'user', 'name' => 'Console Recovered Applicant']);
        $year = 2023;
        app(AdminBackupService::class)->createAnnualArchive($year);
        $applicantId = $applicant->id;
        $applicant->delete();

        $this->artisan('backup:restore-annual', ['year' => (string) $year])
            ->expectsConfirmation("Merge the {$year} annual backup into the current system? Existing records and files will be kept.", 'yes')
            ->expectsOutputToContain("Annual backup {$year} merged:")
            ->assertExitCode(0);

        $this->assertSame('Console Recovered Applicant', User::findOrFail($applicantId)->name);
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::DATABASE_BACKUP_RESTORED,
            'actor_role' => 'system',
        ]);
    }

    public function test_admin_can_merge_backup_records_and_files_without_overwriting_current_data(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Before Backup']);
        $applicant = User::factory()->create(['role' => 'user', 'name' => 'Restored Applicant']);
        $auditEntry = app(AuditLogger::class)->record(
            AuditAction::LOGIN_SUCCESS,
            'Authentication',
            $admin,
        );
        $logName = 'merge-restore-'.uniqid('', true).'.log';
        $logPath = storage_path('logs/'.$logName);
        file_put_contents($logPath, 'restored application log');
        Storage::disk('local')->put('backup-test/private.pdf', 'private backup file');
        Storage::disk('public')->put('backup-test/public.pdf', 'public backup file');

        $archivePath = app(AdminBackupService::class)->create();
        $applicant->delete();
        DB::table('audit_logs')->where('id', $auditEntry->id)->delete();
        $admin->update(['name' => 'Keep Current Admin']);
        @unlink($logPath);
        Storage::disk('local')->delete('backup-test/private.pdf');
        Storage::disk('public')->delete('backup-test/public.pdf');

        try {
            $result = app(AdminBackupService::class)->restore($archivePath);

            $this->assertSame('Restored Applicant', User::findOrFail($applicant->id)->name);
            $this->assertSame('Keep Current Admin', $admin->fresh()->name);
            $this->assertSame('private backup file', Storage::disk('local')->get('backup-test/private.pdf'));
            $this->assertSame('public backup file', Storage::disk('public')->get('backup-test/public.pdf'));
            $this->assertSame('restored application log', file_get_contents($logPath));
            $this->assertDatabaseHas('audit_logs', ['id' => $auditEntry->id]);
            $this->assertGreaterThan(0, $result['rows']);
            $this->assertGreaterThanOrEqual(2, $result['files']);
        } finally {
            @unlink($archivePath);
            @unlink($logPath);
        }
    }

    public function test_backup_manifest_credential_is_registered_and_can_rebuild_a_lost_registry_record(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $applicant = User::factory()->create(['role' => 'user', 'name' => 'Recovered With Credential']);
        $archivePath = app(AdminBackupService::class)->create();
        $archive = new ZipArchive;
        $this->assertTrue($archive->open($archivePath));
        $manifest = json_decode($archive->getFromName('manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        $archive->close();

        $credentialId = $manifest['credential']['id'];
        $this->assertDatabaseHas('backup_archive_credentials', [
            'id' => $credentialId,
            'credential_hash' => hash('sha256', $manifest['credential']['signature']),
            'archive_type' => 'manual',
            'archive_year' => null,
        ]);

        $applicantId = $applicant->id;
        $applicant->delete();
        DB::table('backup_archive_credentials')->where('id', $credentialId)->delete();

        try {
            app(AdminBackupService::class)->restore($archivePath);

            $this->assertSame('Recovered With Credential', User::findOrFail($applicantId)->name);
            $this->assertDatabaseHas('backup_archive_credentials', [
                'id' => $credentialId,
                'credential_hash' => hash('sha256', $manifest['credential']['signature']),
                'archive_type' => 'manual',
            ]);
        } finally {
            @unlink($archivePath);
        }
    }

    public function test_backup_restore_rejects_a_credential_that_conflicts_with_the_database_registry(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $applicant = User::factory()->create(['role' => 'user']);
        $archivePath = app(AdminBackupService::class)->create();
        DB::table('backup_archive_credentials')->update(['credential_hash' => str_repeat('0', 64)]);
        $applicantId = $applicant->id;
        $applicant->delete();

        try {
            try {
                app(AdminBackupService::class)->restore($archivePath);
                $this->fail('A credential that conflicts with the database registry must be rejected.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('does not match the credential registered', $exception->getMessage());
            }

            $this->assertNull(User::find($applicantId));
        } finally {
            @unlink($archivePath);
        }
    }

    public function test_backup_restore_rejects_archive_with_changed_contents(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $applicant = User::factory()->create(['role' => 'user']);
        $archivePath = app(AdminBackupService::class)->create();
        $applicantId = $applicant->id;
        $applicant->delete();

        $archive = new ZipArchive;
        $this->assertTrue($archive->open($archivePath));
        $this->assertTrue($archive->deleteName('README.txt'));
        $this->assertTrue($archive->addFromString('README.txt', 'modified backup'));
        $archive->close();

        try {
            $this->expectException(\RuntimeException::class);
            app(AdminBackupService::class)->restore($archivePath);
        } finally {
            @unlink($archivePath);
            $this->assertNull(User::find($applicantId));
        }
    }

    public function test_backup_restore_rejects_executable_uploads_even_with_a_matching_manifest(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $archivePath = app(AdminBackupService::class)->create();
        $archive = new ZipArchive;
        $this->assertTrue($archive->open($archivePath));
        $manifest = json_decode($archive->getFromName('manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        $payload = '<?php echo "unsafe";';
        $archive->addFromString('uploads/public/shell.php', $payload);
        $manifest['hashes']['uploads/public/shell.php'] = hash('sha256', $payload);
        $archive->deleteName('manifest.json');
        $archive->addFromString('manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR));
        $archive->close();

        try {
            $this->expectException(\RuntimeException::class);
            app(AdminBackupService::class)->restore($archivePath);
        } finally {
            @unlink($archivePath);
        }
    }

    public function test_restore_rejects_a_backup_without_a_registered_credential_manifest(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $applicant = User::factory()->create(['role' => 'user', 'name' => "O'Connor Applicant"]);
        $versionTwoPath = app(AdminBackupService::class)->create();
        $versionOnePath = tempnam(sys_get_temp_dir(), 'spes-legacy-backup-');
        $versionTwo = new ZipArchive;
        $this->assertTrue($versionTwo->open($versionTwoPath));
        $sql = $versionTwo->getFromName('database/backup.sql');
        $readme = $versionTwo->getFromName('README.txt');
        $versionTwo->close();

        $legacy = new ZipArchive;
        $this->assertTrue($legacy->open($versionOnePath, ZipArchive::OVERWRITE));
        $legacy->addFromString('database/backup.sql', $sql);
        $legacy->addFromString('README.txt', $readme);
        $legacy->close();
        $applicantId = $applicant->id;
        $applicant->delete();

        try {
            try {
                app(AdminBackupService::class)->restore($versionOnePath);
                $this->fail('An unsigned legacy archive must not be restored.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('no SPES backup credential', $exception->getMessage());
            }
            $this->assertNull(User::find($applicantId));
        } finally {
            @unlink($versionTwoPath);
            @unlink($versionOnePath);
        }
    }

    public function test_admin_restore_requires_password_and_typed_confirmation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.preferences'))
            ->assertOk()
            ->assertSee('Restore / Merge a Backup')
            ->assertSee('name="current_password"', false)
            ->assertSee('name="confirmation"', false);

        $this->post(route('admin.backup.restore'), [])
            ->assertSessionHasErrors(['backup', 'current_password', 'confirmation']);
    }

    public function test_admin_can_restore_a_verified_backup_from_the_settings_form(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'user', 'name' => 'Restored Through Form']);
        $archivePath = app(AdminBackupService::class)->create();
        $applicantId = $applicant->id;
        $applicant->delete();

        try {
            $this->actingAs($admin)
                ->post(route('admin.backup.restore'), [
                    'backup' => new UploadedFile($archivePath, 'spes-backup.zip', 'application/zip', UPLOAD_ERR_OK, true),
                    'current_password' => 'password',
                    'confirmation' => 'MERGE',
                ])
                ->assertRedirect(route('admin.preferences').'#backup')
                ->assertSessionHas('success');

            $this->assertSame('Restored Through Form', User::findOrFail($applicantId)->name);
            $this->assertDatabaseHas('audit_logs', [
                'user_id' => $admin->id,
                'action' => AuditAction::DATABASE_BACKUP_RESTORED,
                'module' => 'System Backup',
            ]);
        } finally {
            @unlink($archivePath);
        }
    }

    public function test_admin_backup_download_is_audited(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.backup.download'))
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditAction::DATABASE_BACKUP_GENERATED,
            'module' => 'System Backup',
        ]);
        $this->assertDatabaseHas('backup_archive_credentials', [
            'generated_by' => $admin->id,
            'archive_type' => 'manual',
            'archive_year' => null,
        ]);
    }

    public function test_applicants_cannot_access_admin_settings_features(): void
    {
        $applicant = User::factory()->create(['role' => 'user']);

        $this->actingAs($applicant)
            ->get(route('admin.preferences'))
            ->assertForbidden();

        $this->get(route('admin.security'))->assertForbidden();
        $this->get(route('admin.backup.index'))->assertForbidden();
    }
}
