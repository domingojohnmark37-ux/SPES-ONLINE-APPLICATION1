@extends('layouts.admin')

@section('title', __('Settings'))
@section('page-title', __('Settings'))
@section('page-sub', __('Manage your preferences, security, and admin tools.'))

@section('styles')
<style>
    .admin-settings-layout { width:min(1080px,100%); margin:0 auto; }
    .settings-intro { margin-bottom:20px; }
    .settings-intro h2 { margin-bottom:5px; color:var(--text); font-size:1.25rem; }
    .settings-intro p,.settings-description { color:var(--text-muted); line-height:1.55; }
    .settings-sections { position:sticky; top:72px; z-index:20; display:flex; gap:8px; overflow:auto; padding:10px 0 14px; background:var(--bg); }
    .settings-sections a { flex:0 0 auto; padding:9px 13px; border:1px solid var(--border); border-radius:999px; background:var(--white); color:var(--text); font-size:.84rem; text-decoration:none; }
    .settings-sections a:hover,.settings-sections a:focus { border-color:var(--primary); background:var(--primary); color:#fff; }
    .settings-section { scroll-margin-top:135px; margin-bottom:18px; overflow:hidden; border:1px solid var(--border); border-radius:14px; background:var(--white); box-shadow:var(--shadow); }
    .settings-section .card-body { padding:22px; }
    .settings-section-intro { margin-bottom:18px; }
    .settings-section-intro h2 { margin-bottom:5px; color:var(--primary); font-size:1rem; }
    .settings-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; align-items:end; }
    .settings-form-grid .form-group { margin:0; }
    .settings-form-grid small { display:block; margin-top:5px; color:var(--text-muted); line-height:1.45; }
    .settings-save { margin-top:16px; }
    .settings-card-subsection { margin-top:20px; padding-top:18px; border-top:1px solid var(--border); }
    .settings-card-subsection h3 { margin-bottom:5px; color:var(--text); font-size:.9rem; }
    .settings-card-subsection > p { margin-bottom:12px; color:var(--text-muted); font-size:.8rem; line-height:1.5; }
    .settings-faq { display:grid; gap:9px; }
    .settings-faq details { padding:12px 14px; border:1px solid var(--border); border-radius:9px; }
    .settings-faq summary { cursor:pointer; color:var(--text); font-weight:600; }
    .settings-faq details p { margin:8px 0 0; color:var(--text-muted); line-height:1.55; }
    .settings-table-wrap { overflow:auto; }
    .settings-table { width:100%; border-collapse:collapse; font-size:.8rem; }
    .settings-table th,.settings-table td { padding:10px 9px; border-bottom:1px solid var(--border); text-align:left; vertical-align:top; }
    .settings-table th { color:var(--text-muted); font-weight:650; }
    #admin-audit .settings-table-wrap { max-height:min(60vh,520px); overscroll-behavior:contain; scrollbar-gutter:stable; }
    #admin-audit .settings-table thead th { position:sticky; top:0; z-index:1; background:var(--white); }
    .admin-audit-filters { display:flex; flex-wrap:wrap; align-items:end; gap:10px; margin-bottom:16px; padding:14px; border:1px solid var(--border); border-radius:12px; background:var(--white); }
    .admin-audit-filters label { display:grid; min-width:160px; gap:5px; color:var(--text-muted); font-size:.75rem; font-weight:700; }
    .admin-audit-filters select,.admin-audit-filters input { min-height:38px; padding:8px 10px; border:1px solid var(--border); border-radius:7px; background:var(--white); color:var(--text); font:inherit; font-size:.82rem; }
    #personal-security .card-header h2 { color:var(--primary); }
    .personal-security-content { display:grid; gap:18px; }
    .personal-security-description { max-width:850px; color:var(--text-muted); font-size:.86rem; line-height:1.6; }
    .personal-security-status { display:flex; flex-wrap:wrap; align-items:center; gap:9px; padding:12px 14px; border:1px solid var(--border); border-radius:10px; background:var(--bg); color:var(--text); font-size:.83rem; }
    .personal-security-status-badge { display:inline-flex; align-items:center; gap:6px; padding:5px 9px; border-radius:999px; background:#eef0f3; color:#59636f; font-size:.74rem; font-weight:700; }
    .personal-security-status-badge.is-enabled { background:#e8f5e9; color:#256c32; }
    .personal-security-status-question { flex-basis:100%; color:var(--text-muted); overflow-wrap:anywhere; }
    .personal-security-guidance { display:flex; align-items:flex-start; gap:10px; padding:12px 14px; border:1px solid rgba(21,101,192,.18); border-radius:10px; background:rgba(21,101,192,.055); color:var(--text-muted); font-size:.8rem; line-height:1.55; }
    .personal-security-guidance svg.icon { flex:0 0 auto; margin-top:2px; color:var(--info); }
    .personal-security-form { display:grid; gap:16px; }
    .personal-security-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
    .personal-security-grid .form-group { display:grid; min-width:0; gap:7px; margin:0; }
    .personal-security-grid .form-group label { color:var(--text); font-size:.78rem; font-weight:700; line-height:1.4; }
    .personal-security-grid .form-group input { display:block; width:100%; min-width:0; max-width:100%; height:44px; padding:10px 12px; border:1px solid var(--border); border-radius:8px; outline:none; background:var(--white); color:var(--text); font:inherit; font-size:.84rem; transition:border-color .15s,box-shadow .15s; }
    .personal-security-grid .form-group select { width:100%; min-width:0; max-width:100%; }
    .personal-security-grid .form-group input:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(139,0,0,.12); }
    .personal-security-grid .form-group input:focus-visible { outline:none; }
    .personal-security-submit { justify-self:start; min-height:42px; }
    .personal-security-disable { margin-top:4px; padding-top:18px; border-top:1px solid var(--border); }
    .personal-security-disable h3 { margin-bottom:5px; color:var(--text); font-size:.9rem; }
    .personal-security-disable > p { margin-bottom:14px; color:var(--text-muted); font-size:.8rem; line-height:1.5; }
    html[data-admin-theme="dark"] #personal-security .card-header h2 { color:#f2c94c; }
    html[data-admin-theme="dark"] .personal-security-status-badge { background:#343941; color:#d1d5db; }
    html[data-admin-theme="dark"] .personal-security-status-badge.is-enabled { background:#183923; color:#a6e3b1; }
    html[data-admin-theme="dark"] .personal-security-guidance { border-color:rgba(96,165,250,.22); background:rgba(96,165,250,.08); }
    @media(max-width:650px) {
        .settings-sections { top:64px; }
        .settings-section { scroll-margin-top:120px; }
        .settings-section .card-body { padding:16px; }
        .settings-form-grid { grid-template-columns:1fr; }
        .personal-security-grid { grid-template-columns:minmax(0,1fr); gap:13px; }
        .personal-security-submit { width:100%; justify-content:center; }
    }
    @media(prefers-color-scheme:dark) {
        html[data-admin-theme="system"] #personal-security .card-header h2 { color:#f2c94c; }
    }
</style>
@endsection

@section('content')
<main class="admin-settings-layout">
    <div class="settings-intro">
        <h2>{{ __('Settings') }}</h2>
            <p>{{ __('Manage your account preferences, security policies, and admin tools.') }}</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul style="margin-left:20px;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <nav class="settings-sections" aria-label="Settings sections">
        <a href="#preferences">{{ __('Preferences') }}</a>
        <a href="#personal-security">{{ __('Personal Security') }}</a>
        <a href="#manual">{{ __('Manual') }}</a>
        <a href="#admin-audit">{{ __('Admin Audit Logs') }}</a>
        <a href="#backup">{{ __('Backup') }}</a>
    </nav>

    <section class="settings-section" id="preferences" aria-labelledby="preferences-heading">
        <div class="card-header"><h2 id="preferences-heading"><x-icon class="fa-solid fa-sliders" aria-hidden="true" /> {{ __('Preferences') }}</h2></div>
        <div class="card-body">
            <p class="settings-description" style="margin-bottom:16px;">{{ __('Choose how the admin portal looks and behaves on this account.') }}</p>
            <form method="POST" action="{{ route('admin.preferences.update') }}">
                @csrf
                @method('PUT')
                <div class="settings-form-grid">
                    <div class="form-group">
                        <label for="appearance">{{ __('Appearance') }}</label>
                        <select id="appearance" name="appearance" required>
                            <option value="light" @selected(old('appearance', $preference->theme ?? 'system') === 'light')>{{ __('Light') }}</option>
                            <option value="dark" @selected(old('appearance', $preference->theme ?? 'system') === 'dark')>{{ __('Dark') }}</option>
                            <option value="system" @selected(old('appearance', $preference->theme ?? 'system') === 'system')>{{ __('System Default') }}</option>
                        </select>
                        @error('appearance')<p style="color:var(--danger);margin-top:5px;">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label for="language">{{ __('Language') }}</label>
                        <select id="language" name="language" required>
                            <option value="en" @selected(old('language', $preference->language ?? 'en') === 'en')>{{ __('English') }}</option>
                            <option value="fil" @selected(old('language', $preference->language ?? 'en') === 'fil')>{{ __('Filipino') }}</option>
                        </select>
                        @error('language')<p style="color:var(--danger);margin-top:5px;">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label for="sidebar_behavior">{{ __('Sidebar Behavior') }}</label>
                        <select id="sidebar_behavior" name="sidebar_behavior" required>
                            <option value="auto" @selected(old('sidebar_behavior', $preference->sidebar_behavior ?? 'auto') === 'auto')>{{ __('Automatic (responsive)') }}</option>
                            <option value="expanded" @selected(old('sidebar_behavior', $preference->sidebar_behavior ?? 'auto') === 'expanded')>{{ __('Expanded') }}</option>
                            <option value="collapsed" @selected(old('sidebar_behavior', $preference->sidebar_behavior ?? 'auto') === 'collapsed')>{{ __('Collapsed (icons only)') }}</option>
                        </select>
                        @error('sidebar_behavior')<p style="color:var(--danger);margin-top:5px;">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label for="font_size">{{ __('Font / Display Size') }}</label>
                        <select id="font_size" name="font_size" required>
                            <option value="small" @selected(old('font_size', $preference->font_size ?? 'medium') === 'small')>{{ __('Small') }}</option>
                            <option value="medium" @selected(old('font_size', $preference->font_size ?? 'medium') === 'medium')>{{ __('Medium') }}</option>
                            <option value="large" @selected(old('font_size', $preference->font_size ?? 'medium') === 'large')>{{ __('Large') }}</option>
                        </select>
                        @error('font_size')<p style="color:var(--danger);margin-top:5px;">{{ $message }}</p>@enderror
                    </div>
                </div>
                <button class="btn btn-primary settings-save" type="submit"><x-icon class="fa-solid fa-floppy-disk" aria-hidden="true" /> {{ __('Save Preferences') }}</button>
            </form>
        </div>
    </section>

    <section class="settings-section" id="personal-security" aria-labelledby="personal-security-heading" style="border-top:4px solid var(--accent);">
        <div class="card-header"><h2 id="personal-security-heading"><x-icon class="fa-solid fa-shield-halved" aria-hidden="true" /> {{ __('Personalized Security Verification') }}</h2></div>
        <div class="card-body personal-security-content">
            <p class="personal-security-description">{{ __('Add a personal question after your password at sign-in. This knowledge-based check is not an independent second factor like a passkey or authenticator app.') }}</p>
            <div class="personal-security-status" role="status">
                <strong>{{ __('Verification status') }}</strong>
                <span class="personal-security-status-badge {{ $personalSecurityVerification ? 'is-enabled' : '' }}">
                    <x-icon class="fa-solid {{ $personalSecurityVerification ? 'fa-circle-check' : 'fa-circle-minus' }}" aria-hidden="true" />
                    {{ $personalSecurityVerification ? __('Enabled') : __('Not enabled') }}
                </span>
                @if($personalSecurityVerification)
                    <span class="personal-security-status-question">{{ $personalSecurityVerification->question }}</span>
                @endif
            </div>
            <div class="personal-security-guidance">
                <x-icon class="fa-solid fa-circle-info" aria-hidden="true" />
                <p>{{ __('Choose an answer that is memorable to you and difficult for other people to guess. Answers are case-insensitive and repeated whitespace is ignored.') }}</p>
            </div>
            <form method="POST" action="{{ route('admin.personal-security.configuration.update') }}" class="personal-security-form" data-loading-label="{{ __('Saving...') }}">
                @csrf
                @method('PUT')
                <div class="personal-security-grid">
                    <div class="form-group">
                        <x-security-question-select selected="{{ $personalSecurityVerification?->question }}" />
                    </div>
                    <div class="form-group">
                        <label for="personal_security_answer">{{ __('Private answer') }}</label>
                        <input id="personal_security_answer" name="security_answer" type="password" minlength="8" maxlength="255" autocomplete="new-password" required>
                    </div>
                    <div class="form-group">
                        <label for="personal_security_answer_confirmation">{{ __('Confirm private answer') }}</label>
                        <input id="personal_security_answer_confirmation" name="security_answer_confirmation" type="password" minlength="8" maxlength="255" autocomplete="new-password" required>
                    </div>
                    <div class="form-group">
                        <label for="personal_security_current_password">{{ __('Current password') }}</label>
                        <input id="personal_security_current_password" name="current_password" type="password" autocomplete="current-password" required>
                    </div>
                </div>
                <button class="btn btn-primary personal-security-submit" type="submit">{{ $personalSecurityVerification ? __('Replace Question and Answer') : __('Enable Personalized Verification') }}</button>
            </form>
            @if($personalSecurityVerification)
                <form method="POST" action="{{ route('admin.personal-security.configuration.disable') }}" class="personal-security-disable">
                    @csrf
                    @method('DELETE')
                    <h3>{{ __('Disable personalized verification') }}</h3>
                    <p>{{ __('Confirm your password and enter DISABLE to turn off this sign-in check.') }}</p>
                    <div class="personal-security-grid">
                        <div class="form-group">
                            <label for="personal_security_disable_password">{{ __('Current password to disable') }}</label>
                            <input id="personal_security_disable_password" name="current_password" type="password" autocomplete="current-password" required>
                        </div>
                        <div class="form-group">
                            <label for="personal_security_disable_confirmation">{{ __('Type DISABLE to confirm') }}</label>
                            <input id="personal_security_disable_confirmation" name="disable_confirmation" type="text" required>
                        </div>
                    </div>
                    <button class="btn btn-danger personal-security-submit" type="submit">{{ __('Disable Personalized Verification') }}</button>
                </form>
            @endif
        </div>
    </section>

    <section class="settings-section" id="manual" aria-labelledby="manual-heading">
        <div class="card-header"><h2 id="manual-heading"><x-icon class="fa-solid fa-book-open" aria-hidden="true" /> {{ __('Admin Guide') }}</h2></div>
        <div class="card-body" style="display:grid;gap:18px;line-height:1.65;">
            <section>
                <h3>{{ __('Applications') }}</h3>
                <p>{{ __('Review submitted applications, open an applicant record to check its information and documents, and use the available actions to approve or reject an application. Record remarks through the application review controls.') }}</p>
            </section>
            <section>
                <h3>{{ __('Applicant requirements') }}</h3>
                <p>{{ __('Open Management → Applications → Requirements to add the extra documents applicants must submit. Set the requirement name, applicant instructions, required/optional status, and visibility. Applicants can upload PDF, Word, or image files for visible requirements.') }}</p>
            </section>
            <section>
                <h3>{{ __('Announcements and schedule') }}</h3>
                <p>{{ __('Use the Announcements menu to manage appointments and news. Schedule controls the application start and end dates.') }}</p>
            </section>
            <section>
                <h3>{{ __('Settings') }}</h3>
                <p>{{ __('Preferences controls your display theme, language, sidebar, and display size. Admin Audit Logs shows recorded administrator actions and can be filtered by administrator or date. Backup creates or merges an archive of the database, stored files, and application logs.') }}</p>
            </section>
            <section>
                <h3>{{ __('Annual backup schedule') }}</h3>
                <p>{{ __('A full-system annual archive is created on December 31 at 11:59 PM in the application timezone (:timezone), covering the system state through the end of that year. On Windows, configure Task Scheduler to run :command every minute from the project directory; without the operating-system scheduler, automatic backups will not run.', ['timezone' => config('app.timezone'), 'command' => 'php artisan schedule:run']) }}</p>
                <p>{{ __('If all admin accounts are lost but the database tables remain, a server operator can recover access by running :command from the project directory, replacing YYYY with the archive year and confirming the prompt. If the database schema itself was removed, run the project migrations before restoring.', ['command' => 'php artisan backup:restore-annual YYYY']) }}</p>
            </section>
            <section>
                <h3>{{ __('Protect applicant data') }}</h3>
                <p>{{ __('Applicant records and backup archives contain private information. Access them only for official PESO work, and store downloaded backups in an approved secure location. Never share login credentials or backup files.') }}</p>
            </section>
        </div>
    </section>

    <section class="settings-section" id="admin-audit" aria-labelledby="admin-audit-heading">
        <div class="card-header"><h2 id="admin-audit-heading"><x-icon class="fa-solid fa-user-shield" aria-hidden="true" /> {{ __('Admin Audit Logs') }}</h2></div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.preferences') }}#admin-audit" class="admin-audit-filters">
                <label>{{ __('Administrator') }}
                    <select name="user_id">
                        <option value="">{{ __('All administrators') }}</option>
                        @foreach($administrators as $administrator)
                            <option value="{{ $administrator->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $administrator->id)>{{ $administrator->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>{{ __('Date Range') }}
                    <select name="date_range">
                        @foreach(['all' => __('All Time'), 'today' => __('Today'), 'last7' => __('Last 7 Days'), 'last30' => __('Last 30 Days'), 'this_month' => __('This Month'), 'custom' => __('Custom')] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['date_range'] ?? 'all') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>{{ __('From') }} <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></label>
                <label>{{ __('To') }} <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></label>
                <button class="btn btn-primary" type="submit"><x-icon class="fa-solid fa-filter" aria-hidden="true" /> {{ __('Apply') }}</button>
                <a class="btn btn-outline" href="{{ route('admin.preferences') }}#admin-audit">{{ __('Reset') }}</a>
            </form>
            @if($adminAuditLogs->isEmpty())
                <p style="color:var(--text-muted);padding:16px 0;">{{ __('No administrator audit activity matches the selected filters.') }}</p>
            @else
                <div class="settings-table-wrap" role="region" aria-label="{{ __('Administrator audit log entries') }}" tabindex="0">
                    <table class="settings-table">
                        <thead><tr><th>{{ __('Date and time') }}</th><th>{{ __('Administrator') }}</th><th>{{ __('Event') }}</th><th>{{ __('Related applicant') }}</th><th>{{ __('Result') }}</th><th>{{ __('IP address') }}</th><th>{{ __('Details') }}</th></tr></thead>
                        <tbody>
                        @foreach($adminAuditLogs as $event)
                            <tr>
                                <td>@adminDate($event->created_at, true)</td>
                                <td>{{ $event->actor_name }}</td>
                                <td>{{ __($event->action) }}</td>
                                <td>{{ $event->applicant?->name ?? $event->application?->full_name ?? '—' }}</td>
                                <td>{{ __(ucfirst($event->result)) }}</td>
                                <td>{{ $event->ip_address ?: __('Not recorded') }}</td>
                                <td><a href="{{ route('admin.applicant-audit.event', $event) }}">{{ __('Details') }}</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

    <section class="settings-section" id="backup" aria-labelledby="backup-heading">
        <div class="card-header"><h2 id="backup-heading"><x-icon class="fa-solid fa-database" aria-hidden="true" /> {{ __('Backup') }}</h2></div>
        <div class="card-body" style="max-width:820px;">
            @if($backupOperation)
                <div
                    id="backup-operation-status"
                    class="alert alert-info"
                    role="status"
                    aria-live="polite"
                    data-status-url="{{ route('admin.backup.operations.status', $backupOperation) }}"
                    data-status="{{ $backupOperation->status }}"
                    style="margin-bottom:18px;"
                >
                    <span data-backup-operation-message>
                        @if($backupOperation->status === 'failed')
                            {{ $backupOperation->failure_message }}
                        @elseif($backupOperation->status === 'completed')
                            {{ ($backupOperation->result['expired'] ?? false) ? __('This backup has expired under the configured retention policy.') : ($backupOperation->result['message'] ?? __('Backup is ready to download.')) }}
                        @else
                            {{ match ($backupOperation->status) {
                                'processing' => __('The background operation is processing.'),
                                'verifying' => __('The operation is verifying the backup.'),
                                default => __('Waiting for a background worker.'),
                            } }}
                        @endif
                    </span>
                    @if($backupOperation->status === 'completed' && $backupOperation->action === 'create' && $backupOperation->archive_path)
                        <a data-backup-download href="{{ route('admin.backup.operations.download', $backupOperation) }}">{{ __('Download backup') }}</a>
                    @endif
                </div>
            @endif
            <h3 style="margin-bottom:8px;">{{ __('Backup storage and retention') }}</h3>
            <p style="line-height:1.6;margin-bottom:8px;">{{ __('Private backup storage used: :size', ['size' => number_format($backupStorage['bytes'] / 1048576, 2).' MB']) }}</p>
            @if($backupStorage['free_bytes'] !== null)
                <p style="line-height:1.6;margin-bottom:8px;">{{ __('Available space on the backup volume: :size', ['size' => number_format($backupStorage['free_bytes'] / 1073741824, 2).' GB']) }}</p>
                @if($backupStorage['free_bytes'] < $backupStorage['minimum_free_bytes'])
                    <div class="alert alert-danger" role="alert">{{ __('Available disk space is below the configured backup safety threshold. New backups will be stopped.') }}</div>
                @endif
            @else
                <p style="line-height:1.6;margin-bottom:8px;">{{ __('Available server disk space cannot be measured by this storage driver.') }}</p>
            @endif
            @if($failedBackupCount > 0)
                <div class="alert {{ $failedBackupCount >= 3 ? 'alert-danger' : 'alert-warning' }}" role="alert" style="margin:12px 0;">
                    {{ trans_choice(':count backup operation failed in the last 7 days.|:count backup operations failed in the last 7 days.', $failedBackupCount, ['count' => $failedBackupCount]) }}
                    {{ __('Check the application log and confirm the database state before retrying any recovery operation.') }}
                </div>
                <ul style="margin:0 0 16px 20px;color:var(--text-muted);">
                    @foreach($recentBackupFailures as $failedOperation)
                        <li>{{ ucfirst($failedOperation->action) }} · {{ $failedOperation->updated_at->translatedFormat('M j, Y g:i A') }} · {{ $failedOperation->failure_message }}</li>
                    @endforeach
                </ul>
            @endif
            <p style="line-height:1.6;margin-bottom:18px;">{{ __('Full daily backups are retained for :daily days, plus the newest backup from each of the most recent :weekly weeks and :monthly months. The newest verified backup and any backup in use by a recovery job are protected. Annual safety archives are not automatically deleted.', [
                'daily' => config('backup.retention.daily'),
                'weekly' => config('backup.retention.weekly'),
                'monthly' => config('backup.retention.monthly'),
            ]) }}</p>
            @if($dailyBackups !== [])
                <div class="settings-table-wrap" style="margin-bottom:20px;">
                    <table class="settings-table">
                        <thead><tr><th>{{ __('Backup') }}</th><th>{{ __('Size') }}</th><th>{{ __('Duration') }}</th><th>{{ __('Verification') }}</th><th>{{ __('Retention') }}</th></tr></thead>
                        <tbody>
                        @foreach($dailyBackups as $dailyBackup)
                            <tr>
                                <td>{{ ucfirst($dailyBackup['type']) }} · {{ \Illuminate\Support\Carbon::parse($dailyBackup['created_at'])->translatedFormat('M j, Y g:i A') }}</td>
                                <td>{{ number_format($dailyBackup['size'] / 1048576, 2) }} MB</td>
                                <td>{{ $dailyBackup['duration_ms'] ? number_format($dailyBackup['duration_ms'] / 1000, 1).' s' : '—' }}</td>
                                <td>{{ $dailyBackup['verified_at'] ? __('Verified') : __('Unverified') }}</td>
                                <td>{{ $dailyBackup['will_expire'] ? __('Will expire at next cleanup') : __('Protected') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            <h3 style="margin-bottom:8px;">{{ __('Download a Backup') }}</h3>
            <p style="line-height:1.6;margin-bottom:12px;">{{ __('The archive includes every database table (including application transactions and audit logs), files from private and public storage, application log files, and a checksum manifest.') }}</p>
            <p style="line-height:1.6;margin-bottom:18px;"><strong>{{ __('Protect this archive:') }}</strong> {{ __('it contains applicant information and uploaded documents. Download it only to an approved secure location. Environment files, credentials, and application source code are not included.') }}</p>
            <form method="POST" action="{{ route('admin.backup.download') }}" onsubmit="return confirm(@js(__('Create and download a backup containing the database and stored applicant files? Handle it as confidential information.')));">
                @csrf
                <input type="hidden" name="_idempotency_token" value="{{ \Illuminate\Support\Str::random(40) }}">
                <button class="btn btn-primary" type="submit"><x-icon class="fa-solid fa-download" aria-hidden="true" /> {{ __('Create and Download Backup') }}</button>
            </form>

            <hr style="margin:24px 0;border:0;border-top:1px solid var(--border);">
            <h3 style="margin-bottom:8px;">{{ __('Automatic Annual Archives') }}</h3>
            <p style="line-height:1.6;margin-bottom:18px;">{{ __('The system creates one complete private archive each December 31 at 11:59 PM (:timezone), capturing the system state at the end of that year, including its transactions and logs. Archives are retained until manually removed from server storage. They are kept separately and are not copied into later backups.', ['timezone' => config('app.timezone')]) }}</p>
            @if($annualBackups === [])
                <p style="color:var(--text-muted);">{{ __('No annual archives have been created yet. The first archive will be available after the scheduled year-end backup runs.') }}</p>
            @else
                <div class="settings-table-wrap">
                    <table class="settings-table">
                        <thead><tr><th>{{ __('Backup year') }}</th><th>{{ __('Created') }}</th><th>{{ __('Size') }}</th><th>{{ __('Actions') }}</th></tr></thead>
                        <tbody>
                        @foreach($annualBackups as $annualBackup)
                            <tr>
                                <td>{{ $annualBackup['year'] }}</td>
                                <td>{{ \Illuminate\Support\Carbon::createFromTimestamp($annualBackup['last_modified'])->translatedFormat('M j, Y g:i A') }}</td>
                                <td>{{ number_format($annualBackup['size'] / 1048576, 2) }} MB</td>
                                <td style="display:flex;gap:8px;flex-wrap:wrap;">
                                    <a class="btn btn-outline btn-sm" href="{{ route('admin.backup.archive.download', $annualBackup['year']) }}">{{ __('Download') }}</a>
                                    <form style="display:grid;gap:6px;min-width:220px;" method="POST" action="{{ route('admin.backup.archive.restore', $annualBackup['year']) }}" onsubmit="return confirm(@js(__('Merge the :year annual backup? Existing records and files will be kept. You must enter RESTORE :year to confirm.', ['year' => $annualBackup['year']])));">
                                        @csrf
                                        <input type="hidden" name="_idempotency_token" value="{{ \Illuminate\Support\Str::random(40) }}">
                                        <label for="annual_restore_password_{{ $annualBackup['year'] }}">{{ __('Current password') }}</label>
                                        <input id="annual_restore_password_{{ $annualBackup['year'] }}" name="current_password" type="password" autocomplete="current-password" required>
                                        <label for="annual_restore_confirmation_{{ $annualBackup['year'] }}">{{ __('Type RESTORE :year', ['year' => $annualBackup['year']]) }}</label>
                                        <input id="annual_restore_confirmation_{{ $annualBackup['year'] }}" name="confirmation" type="text" autocomplete="off" required>
                                        <button class="btn btn-primary btn-sm" type="submit">{{ __('Restore / Merge') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <hr style="margin:24px 0;border:0;border-top:1px solid var(--border);">
            <h3 style="margin-bottom:8px;">{{ __('Restore / Merge a Backup') }}</h3>
            <p style="line-height:1.6;margin-bottom:12px;">{{ __('Upload a SPES backup ZIP to add missing records, stored files, and logs. Existing records and files are kept; duplicate database rows and file paths are skipped. The SQL file is never executed.') }}</p>
            <p style="line-height:1.6;margin-bottom:12px;">{{ __('Limits: ZIP files may be up to 1 GB, and the database snapshot may be up to 256 MB.') }}</p>
            <p style="line-height:1.6;margin-bottom:18px;"><strong>{{ __('Before continuing:') }}</strong> {{ __('use a backup from this system, verify it is complete, and keep a separate copy. Restored records may include sensitive applicant information.') }}</p>
            <form method="POST" action="{{ route('admin.backup.restore') }}" enctype="multipart/form-data" onsubmit="return confirm(@js(__('Merge this backup into the current system? Existing records and files will be kept; only missing items will be added.')));">
                @csrf
                <input type="hidden" name="_idempotency_token" value="{{ \Illuminate\Support\Str::random(40) }}">
                <div class="admin-audit-filters" style="align-items:end;margin-bottom:14px;">
                    <label for="backup">{{ __('Backup ZIP') }}
                        <input id="backup" name="backup" type="file" accept=".zip,application/zip" required>
                    </label>
                    <label for="restore_current_password">{{ __('Current password') }}
                        <input id="restore_current_password" name="current_password" type="password" autocomplete="current-password" required>
                    </label>
                    <label for="restore_confirmation">{{ __('Type MERGE to confirm') }}
                        <input id="restore_confirmation" name="confirmation" type="text" autocomplete="off" required>
                    </label>
                </div>
                <button class="btn btn-primary" type="submit"><x-icon class="fa-solid fa-rotate-left" aria-hidden="true" /> {{ __('Validate and Merge Backup') }}</button>
            </form>
        </div>
    </section>
</main>
<script>
    document.getElementById('appearance')?.addEventListener('change', function () {
        document.documentElement.dataset.adminTheme = this.value;
    });
    document.getElementById('sidebar_behavior')?.addEventListener('change', function () {
        document.documentElement.dataset.adminSidebar = this.value;
    });
    document.getElementById('font_size')?.addEventListener('change', function () {
        document.documentElement.dataset.adminFontSize = this.value;
    });
    document.getElementById('language')?.addEventListener('change', function () {
        document.documentElement.lang = this.value === 'fil' ? 'fil' : 'en';
    });
</script>
@if($backupOperation && in_array($backupOperation->status, ['queued', 'processing', 'verifying'], true))
    <script>
        (() => {
            const panel = document.getElementById('backup-operation-status');
            if (!panel) return;

            const message = panel.querySelector('[data-backup-operation-message]');
            const poll = async () => {
                try {
                    const response = await fetch(panel.dataset.statusUrl, {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) throw new Error(`Status request failed (${response.status}).`);
                    const operation = await response.json();
                    panel.dataset.status = operation.status;
                    message.textContent = operation.message;

                    if (operation.download_url) {
                        const link = document.createElement('a');
                        link.dataset.backupDownload = '';
                        link.href = operation.download_url;
                        link.textContent = @js(__('Download backup'));
                        message.after(document.createTextNode(' '), link);
                    }

                    if (['completed', 'failed'].includes(operation.status)) return;
                } catch (error) {
                    message.textContent = @js(__('Unable to check backup status. Retrying shortly.'));
                }

                window.setTimeout(poll, 3000);
            };

            window.setTimeout(poll, 1000);
        })();
    </script>
@endif
@endsection
