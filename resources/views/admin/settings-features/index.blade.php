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
    #admin-audit nav[role="navigation"] svg { display:block; width:1rem; height:1rem; }
    .admin-audit-filters { display:flex; flex-wrap:wrap; align-items:end; gap:10px; margin-bottom:16px; padding:14px; border:1px solid var(--border); border-radius:12px; background:var(--white); }
    .admin-audit-filters label { display:grid; min-width:160px; gap:5px; color:var(--text-muted); font-size:.75rem; font-weight:700; }
    .admin-audit-filters select,.admin-audit-filters input { min-height:38px; padding:8px 10px; border:1px solid var(--border); border-radius:7px; background:var(--white); color:var(--text); font:inherit; font-size:.82rem; }
    @media(max-width:650px) {
        .settings-sections { top:64px; }
        .settings-section { scroll-margin-top:120px; }
        .settings-section .card-body { padding:16px; }
        .settings-form-grid { grid-template-columns:1fr; }
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
        <a href="#manual">{{ __('Manual') }}</a>
        <a href="#admin-audit">{{ __('Admin Audit Logs') }}</a>
        <a href="#backup">{{ __('Backup') }}</a>
    </nav>

    <section class="settings-section" id="preferences" aria-labelledby="preferences-heading">
        <div class="card-header"><h2 id="preferences-heading"><i class="fa-solid fa-sliders" aria-hidden="true"></i> {{ __('Preferences') }}</h2></div>
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
                <button class="btn btn-primary settings-save" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> {{ __('Save Preferences') }}</button>
            </form>
        </div>
    </section>

    <section class="settings-section" id="manual" aria-labelledby="manual-heading">
        <div class="card-header"><h2 id="manual-heading"><i class="fa-solid fa-book-open" aria-hidden="true"></i> {{ __('Admin Guide') }}</h2></div>
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
        <div class="card-header"><h2 id="admin-audit-heading"><i class="fa-solid fa-user-shield" aria-hidden="true"></i> {{ __('Admin Audit Logs') }}</h2></div>
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
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> {{ __('Apply') }}</button>
                <a class="btn btn-outline" href="{{ route('admin.preferences') }}#admin-audit">{{ __('Reset') }}</a>
            </form>
            @if($adminAuditLogs->isEmpty())
                <p style="color:var(--text-muted);padding:16px 0;">{{ __('No administrator audit activity matches the selected filters.') }}</p>
            @else
                <div class="settings-table-wrap">
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
                <div class="audit-table-footer">
                    @if($showAllAdminAuditLogs)
                        <span>{{ __('Showing :first–:last of :total admin audit logs', ['first' => $adminAuditLogs->firstItem(), 'last' => $adminAuditLogs->lastItem(), 'total' => $adminAuditLogs->total()]) }}</span>
                        {{ $adminAuditLogs->links() }}
                        <a class="btn btn-outline btn-sm" href="{{ route('admin.preferences', array_merge(request()->query(), ['show_all_admin_audit_logs' => null, 'admin_audit_page' => null])) }}#admin-audit">{{ __('Show recent 5') }}</a>
                    @else
                        <span>{{ __('Showing :count recent of :total admin audit logs', ['count' => $adminAuditLogs->count(), 'total' => $adminAuditLogs->total()]) }}</span>
                        @if($adminAuditLogs->total() > 5)
                            <a class="btn btn-outline btn-sm" href="{{ route('admin.preferences', array_merge(request()->query(), ['show_all_admin_audit_logs' => 1, 'admin_audit_page' => 1])) }}#admin-audit">{{ __('Show more admin audit logs') }}</a>
                        @endif
                    @endif
                </div>
            @endif
        </div>
    </section>

    <section class="settings-section" id="backup" aria-labelledby="backup-heading">
        <div class="card-header"><h2 id="backup-heading"><i class="fa-solid fa-database" aria-hidden="true"></i> {{ __('Backup') }}</h2></div>
        <div class="card-body" style="max-width:820px;">
            <h3 style="margin-bottom:8px;">{{ __('Download a Backup') }}</h3>
            <p style="line-height:1.6;margin-bottom:12px;">{{ __('The archive includes every database table (including application transactions and audit logs), files from private and public storage, application log files, and a checksum manifest.') }}</p>
            <p style="line-height:1.6;margin-bottom:18px;"><strong>{{ __('Protect this archive:') }}</strong> {{ __('it contains applicant information and uploaded documents. Download it only to an approved secure location. Environment files, credentials, and application source code are not included.') }}</p>
            <form method="POST" action="{{ route('admin.backup.download') }}" onsubmit="return confirm(@js(__('Create and download a backup containing the database and stored applicant files? Handle it as confidential information.')));">
                @csrf
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-download" aria-hidden="true"></i> {{ __('Create and Download Backup') }}</button>
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
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i> {{ __('Validate and Merge Backup') }}</button>
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
@endsection
