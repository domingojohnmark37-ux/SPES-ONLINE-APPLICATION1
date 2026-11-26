@extends('layouts.admin')

@section('title', __('Settings'))
@section('page-title', __('Settings'))
@section('page-sub', __('Manage your preferences, security, and admin tools.'))

@section('styles')
<style>
    .admin-settings-layout { width:min(1160px,100%); margin:0 auto; }
    .settings-intro { margin-bottom:8px; }
    .settings-intro p,.settings-description { color:var(--text-muted); line-height:1.55; }
    .settings-sections { position:sticky; top:72px; z-index:20; display:flex; gap:6px; overflow-x:auto; padding:10px; border:1px solid var(--border); border-radius:12px; background:var(--white); box-shadow:var(--shadow); scrollbar-width:thin; }
    .settings-sections a { display:inline-flex; min-height:40px; flex:0 0 auto; align-items:center; justify-content:center; padding:8px 13px; border:1px solid transparent; border-radius:8px; background:transparent; color:var(--text-muted); font-size:.82rem; font-weight:700; text-decoration:none; white-space:nowrap; transition:background .15s,border-color .15s,color .15s; }
    .settings-sections a:hover,.settings-sections a:focus-visible { border-color:var(--border); background:var(--bg); color:var(--primary); outline:none; }
    .settings-sections a[aria-selected="true"] { border-color:var(--primary); background:var(--primary); color:#fff; }
    .settings-section { scroll-margin-top:140px; margin-top:16px; overflow:hidden; border:1px solid var(--border); border-radius:14px; background:var(--white); box-shadow:var(--shadow); }
    .settings-section[hidden] { display:none !important; }
    .settings-section .card-header { padding:17px 22px; }
    .settings-section .card-body { padding:22px; }
    .settings-section-intro { margin-bottom:18px; }
    .settings-section-intro h2 { margin-bottom:5px; color:var(--primary); font-size:1rem; }
    .settings-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; align-items:end; }
    .settings-form-grid .form-group { margin:0; }
    .settings-form-grid .form-group > label { display:block; margin-bottom:6px; color:var(--text); font-size:.78rem; font-weight:700; }
    .settings-form-grid select,.settings-form-grid input:not([type="checkbox"]) { width:100%; min-height:42px; padding:9px 11px; border:1px solid var(--border); border-radius:8px; background:var(--white); color:var(--text); font:inherit; font-size:.84rem; }
    .settings-form-grid select:focus,.settings-form-grid input:focus { border-color:var(--primary); outline:0; box-shadow:0 0 0 3px rgba(139,0,0,.12); }
    .settings-form-grid small { display:block; margin-top:5px; color:var(--text-muted); line-height:1.45; }
    .settings-save { margin-top:16px; }
    .settings-card-subsection { margin-top:16px; padding:18px; border:1px solid var(--border); border-radius:12px; background:var(--white); }
    .settings-card-subsection h3 { margin-bottom:6px; color:var(--text); font-size:.92rem; }
    .settings-card-subsection > p { margin-bottom:12px; color:var(--text-muted); font-size:.8rem; line-height:1.5; }
    .settings-faq { display:grid; gap:9px; }
    .settings-faq details { overflow:hidden; border:1px solid var(--border); border-radius:9px; background:var(--white); }
    .settings-faq summary { position:relative; padding:13px 40px 13px 14px; cursor:pointer; color:var(--text); font-size:.84rem; font-weight:700; list-style:none; }
    .settings-faq summary::-webkit-details-marker { display:none; }
    .settings-faq summary::after { position:absolute; top:50%; right:16px; width:8px; height:8px; border-right:2px solid currentColor; border-bottom:2px solid currentColor; content:""; transform:translateY(-70%) rotate(45deg); transition:transform .18s; }
    .settings-faq details[open] summary { color:var(--primary); }
    .settings-faq details[open] summary::after { transform:translateY(-25%) rotate(225deg); }
    .settings-faq details p { margin:0; color:var(--text-muted); line-height:1.55; }
    .settings-faq-panel { display:grid; grid-template-rows:0fr; transition:grid-template-rows .18s ease; }
    .settings-faq details[open] .settings-faq-panel { grid-template-rows:1fr; }
    .settings-faq-panel > div { min-height:0; overflow:hidden; padding:0 14px; }
    .settings-faq details[open] .settings-faq-panel > div { padding:0 14px 14px; }
    .settings-table-wrap { overflow:auto; border:1px solid var(--border); border-radius:9px; }
    .settings-table { width:100%; border-collapse:collapse; font-size:.79rem; }
    .settings-table th,.settings-table td { padding:11px 10px; border-bottom:1px solid var(--border); text-align:left; vertical-align:top; }
    .settings-table th { color:var(--text-muted); background:var(--bg); font-size:.7rem; font-weight:750; letter-spacing:.035em; text-transform:uppercase; }
    .settings-table tbody tr:last-child td { border-bottom:0; }
    .settings-table tbody tr:hover { background:var(--bg); }
    #admin-audit .settings-table { min-width:920px; }
    #admin-audit .settings-table-wrap { max-height:min(60vh,520px); overscroll-behavior:contain; scrollbar-gutter:stable; }
    #admin-audit .settings-table thead th { position:sticky; top:0; z-index:1; background:var(--white); }
    .admin-audit-filters { display:flex; flex-wrap:wrap; align-items:end; gap:10px; margin-bottom:16px; padding:14px; border:1px solid var(--border); border-radius:12px; background:var(--bg); }
    .admin-audit-filters label { display:grid; min-width:160px; gap:5px; color:var(--text-muted); font-size:.75rem; font-weight:700; }
    .admin-audit-filters select,.admin-audit-filters input { min-height:38px; padding:8px 10px; border:1px solid var(--border); border-radius:7px; background:var(--white); color:var(--text); font:inherit; font-size:.82rem; }
    .settings-status { display:inline-flex; align-items:center; gap:5px; padding:4px 8px; border:1px solid var(--border); border-radius:999px; background:var(--bg); color:var(--text-muted); font-size:.7rem; font-weight:750; white-space:nowrap; }
    .settings-status.is-success { border-color:rgba(46,125,50,.25); background:rgba(46,125,50,.09); color:var(--success); }
    .settings-status.is-danger { border-color:rgba(198,40,40,.25); background:rgba(198,40,40,.08); color:var(--danger); }
    .settings-status.is-warning { border-color:rgba(255,215,0,.45); background:rgba(255,215,0,.18); color:var(--primary); }
    .settings-status.is-info { border-color:rgba(21,101,192,.22); background:rgba(21,101,192,.08); color:var(--info); }
    .settings-status.is-enabled { border-color:rgba(46,125,50,.25); background:rgba(46,125,50,.09); color:var(--success); }
    .settings-status.is-disabled { border-color:var(--border); background:var(--bg); color:var(--text-muted); }
    .settings-summary-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:9px; }
    .settings-summary-item { display:flex; min-width:0; align-items:flex-start; justify-content:space-between; gap:14px; padding:11px 12px; border:1px solid var(--border); border-radius:8px; background:var(--bg); }
    .settings-summary-item dt { color:var(--text-muted); font-size:.75rem; font-weight:650; }
    .settings-summary-item dd { margin:0; color:var(--text); font-size:.78rem; font-weight:700; text-align:right; overflow-wrap:anywhere; }
    .settings-audit-details { display:inline-flex; min-height:30px; align-items:center; padding:5px 9px; border:1px solid var(--primary); border-radius:6px; color:var(--primary); font-size:.73rem; font-weight:700; text-decoration:none; white-space:nowrap; }
    .settings-audit-details:hover,.settings-audit-details:focus-visible { background:var(--primary); color:#fff; outline:none; }
    .settings-backup-actions { display:flex; flex-wrap:wrap; align-items:center; gap:10px; }
    .settings-backup-history { min-width:860px; }
    .settings-backup-history td:last-child { min-width:120px; }
    .settings-restore-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; margin:14px 0; }
    .settings-restore-fields label { display:grid; min-width:0; gap:6px; color:var(--text); font-size:.78rem; font-weight:700; }
    .settings-restore-fields input { width:100%; min-width:0; min-height:40px; padding:8px 10px; border:1px solid var(--border); border-radius:7px; background:var(--white); color:var(--text); font:inherit; font-size:.8rem; }
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
        .settings-section .card-header { padding:14px 16px; }
        .settings-form-grid { grid-template-columns:1fr; }
        .personal-security-grid { grid-template-columns:minmax(0,1fr); gap:13px; }
        .personal-security-submit { width:100%; justify-content:center; }
        .settings-card-subsection { padding:14px; }
        .settings-summary-grid { grid-template-columns:1fr; }
        .settings-summary-item { align-items:flex-start; }
        .settings-backup-actions > .btn { flex:1 1 100%; justify-content:center; }
        .settings-sections a { min-height:38px; padding:7px 11px; }
        .settings-restore-fields { grid-template-columns:1fr; }
    }
    @media(prefers-color-scheme:dark) {
        html[data-admin-theme="system"] #personal-security .card-header h2 { color:#f2c94c; }
    }
</style>
@endsection

@section('content')
@php
    $settingsSectionIds = ['preferences', 'personal-security', 'manual', 'admin-audit', 'backup'];
    $settingsInitialSection = null;
    if (! in_array($settingsInitialSection, $settingsSectionIds, true)) {
        $settingsInitialSection = 'preferences';

        if (request()->query('date_range') !== null
            || request()->query('user_id') !== null
            || request()->query('date_from') !== null
            || request()->query('date_to') !== null) {
            $settingsInitialSection = 'admin-audit';
        } elseif ($errors->hasAny(['security_answer', 'security_answer_confirmation', 'disable_confirmation', 'question'])
            || old('question') !== null
            || old('disable_confirmation') !== null) {
            $settingsInitialSection = 'personal-security';
        } elseif ($errors->hasAny(['backup_settings', 'backup_enabled', 'backup_frequency', 'backup_time', 'backup_retention_days', 'backup', 'current_password'])) {
            $settingsInitialSection = 'backup';
        } elseif ($errors->hasAny(['appearance', 'language', 'sidebar_behavior', 'font_size', 'preferences'])) {
            $settingsInitialSection = 'preferences';
        }
    }
@endphp
<main class="admin-settings-layout">
    <div class="settings-intro">
        <h2>{{ __('Settings') }}</h2>
            <p>{{ __('Manage your account preferences, security policies, and admin tools.') }}</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success" role="status" data-spes-alert="success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger" role="alert" data-spes-alert="error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul style="margin-left:20px;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <nav class="settings-sections" aria-label="{{ __('Settings sections') }}" role="tablist" data-settings-tabs data-initial-section="{{ $settingsInitialSection }}">
        <a id="settings-tab-preferences" href="#preferences" role="tab" aria-controls="preferences" aria-selected="{{ $settingsInitialSection === 'preferences' ? 'true' : 'false' }}" tabindex="{{ $settingsInitialSection === 'preferences' ? '0' : '-1' }}" data-settings-tab>{{ __('Preferences') }}</a>
        <a id="settings-tab-personal-security" href="#personal-security" role="tab" aria-controls="personal-security" aria-selected="{{ $settingsInitialSection === 'personal-security' ? 'true' : 'false' }}" tabindex="{{ $settingsInitialSection === 'personal-security' ? '0' : '-1' }}" data-settings-tab>{{ __('Personal Security') }}</a>
        <a id="settings-tab-manual" href="#manual" role="tab" aria-controls="manual" aria-selected="{{ $settingsInitialSection === 'manual' ? 'true' : 'false' }}" tabindex="{{ $settingsInitialSection === 'manual' ? '0' : '-1' }}" data-settings-tab>{{ __('Admin Guide') }}</a>
        <a id="settings-tab-admin-audit" href="#admin-audit" role="tab" aria-controls="admin-audit" aria-selected="{{ $settingsInitialSection === 'admin-audit' ? 'true' : 'false' }}" tabindex="{{ $settingsInitialSection === 'admin-audit' ? '0' : '-1' }}" data-settings-tab>{{ __('Admin Audit Logs') }}</a>
        <a id="settings-tab-backup" href="#backup" role="tab" aria-controls="backup" aria-selected="{{ $settingsInitialSection === 'backup' ? 'true' : 'false' }}" tabindex="{{ $settingsInitialSection === 'backup' ? '0' : '-1' }}" data-settings-tab>{{ __('Backup Management') }}</a>
    </nav>

    <section class="settings-section" id="preferences" role="tabpanel" tabindex="0" aria-labelledby="settings-tab-preferences" data-settings-panel>
        <div class="card-header"><h2 id="preferences-heading"><x-icon class="fa-solid fa-sliders" aria-hidden="true" /> {{ __('Preferences') }}</h2></div>
        <div class="card-body">
            <p class="settings-description" style="margin-bottom:16px;">{{ __('Choose how the admin portal looks and behaves on this account.') }}</p>
            <form method="POST" action="{{ route('admin.preferences.update') }}"
                  data-swal-confirm="{{ __('Save these admin preferences?') }}"
                  data-swal-title="{{ __('Save Preferences') }}"
                  data-swal-confirm-action="{{ __('Save Preferences') }}"
                  data-swal-cancel-action="{{ __('Cancel') }}">
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
                        <small>{{ __('Choose the appearance used for your admin pages.') }}</small>
                        @error('appearance')<p style="color:var(--danger);margin-top:5px;">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label for="language">{{ __('Language') }}</label>
                        <select id="language" name="language" required>
                            <option value="en" @selected(old('language', $preference->language ?? 'en') === 'en')>{{ __('English') }}</option>
                            <option value="fil" @selected(old('language', $preference->language ?? 'en') === 'fil')>{{ __('Filipino') }}</option>
                        </select>
                        <small>{{ __('Set the display language for the admin portal.') }}</small>
                        @error('language')<p style="color:var(--danger);margin-top:5px;">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label for="sidebar_behavior">{{ __('Sidebar Behavior') }}</label>
                        <select id="sidebar_behavior" name="sidebar_behavior" required>
                            <option value="auto" @selected(old('sidebar_behavior', $preference->sidebar_behavior ?? 'auto') === 'auto')>{{ __('Automatic (responsive)') }}</option>
                            <option value="expanded" @selected(old('sidebar_behavior', $preference->sidebar_behavior ?? 'auto') === 'expanded')>{{ __('Expanded') }}</option>
                            <option value="collapsed" @selected(old('sidebar_behavior', $preference->sidebar_behavior ?? 'auto') === 'collapsed')>{{ __('Collapsed (icons only)') }}</option>
                        </select>
                        <small>{{ __('Choose how the admin navigation behaves across screen sizes.') }}</small>
                        @error('sidebar_behavior')<p style="color:var(--danger);margin-top:5px;">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label for="font_size">{{ __('Font / Display Size') }}</label>
                        <select id="font_size" name="font_size" required>
                            <option value="small" @selected(old('font_size', $preference->font_size ?? 'medium') === 'small')>{{ __('Small') }}</option>
                            <option value="medium" @selected(old('font_size', $preference->font_size ?? 'medium') === 'medium')>{{ __('Medium') }}</option>
                            <option value="large" @selected(old('font_size', $preference->font_size ?? 'medium') === 'large')>{{ __('Large') }}</option>
                        </select>
                        <small>{{ __('Adjust the size of text throughout the admin interface.') }}</small>
                        @error('font_size')<p style="color:var(--danger);margin-top:5px;">{{ $message }}</p>@enderror
                    </div>
                </div>
                <button class="btn btn-primary settings-save" type="submit"><x-icon class="fa-solid fa-floppy-disk" aria-hidden="true" /> {{ __('Save Preferences') }}</button>
            </form>
        </div>
    </section>

    <section class="settings-section" id="personal-security" role="tabpanel" tabindex="0" aria-labelledby="settings-tab-personal-security" data-settings-panel style="border-top:4px solid var(--accent);">
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
            <form method="POST" action="{{ route('admin.personal-security.configuration.update') }}" class="personal-security-form" data-loading-label="{{ __('Saving...') }}"
                  data-swal-confirm="{{ $personalSecurityVerification ? __('Replace the saved security question and answer?') : __('Enable personalized security verification for your administrator sign-in?') }}"
                  data-swal-title="{{ __('Confirm security change') }}"
                  data-swal-confirm-action="{{ __('Continue') }}"
                  data-swal-cancel-action="{{ __('Cancel') }}">
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
                <form method="POST" action="{{ route('admin.personal-security.configuration.disable') }}" class="personal-security-disable"
                      data-swal-confirm="{{ __('Disable personalized verification for your administrator sign-in?') }}"
                      data-swal-title="{{ __('Confirm security change') }}"
                      data-swal-confirm-action="{{ __('Disable') }}"
                      data-swal-cancel-action="{{ __('Cancel') }}">
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

    <section class="settings-section" id="manual" role="tabpanel" tabindex="0" aria-labelledby="settings-tab-manual" data-settings-panel>
        <div class="card-header"><h2 id="manual-heading"><x-icon class="fa-solid fa-book-open" aria-hidden="true" /> {{ __('Admin Guide') }}</h2></div>
        <div class="card-body">
            <div class="settings-faq">
                <details open>
                    <summary>{{ __('Applications') }}</summary>
                    <div class="settings-faq-panel"><div><p>{{ __('Review submitted applications, open an applicant record to check its information and documents, and use the available actions to approve or reject an application. Record remarks through the application review controls.') }}</p></div></div>
                </details>
                <details>
                    <summary>{{ __('Applicant requirements') }}</summary>
                    <div class="settings-faq-panel"><div><p>{{ __('Open Management → Applications → Requirements to add the extra documents applicants must submit. Set the requirement name, applicant instructions, required/optional status, and visibility. Applicants can upload PDF, Word, or image files for visible requirements.') }}</p></div></div>
                </details>
                <details>
                    <summary>{{ __('Announcements and schedule') }}</summary>
                    <div class="settings-faq-panel"><div><p>{{ __('Use the Announcements menu to manage appointments and news. Schedule controls the application start and end dates.') }}</p></div></div>
                </details>
                <details>
                    <summary>{{ __('Settings') }}</summary>
                    <div class="settings-faq-panel"><div><p>{{ __('Preferences controls your display theme, language, sidebar, and display size. Admin Audit Logs shows recorded administrator actions and can be filtered by administrator or date. Backup creates archives and can merge missing records or files, but it does not replace the live database.') }}</p></div></div>
                </details>
                <details>
                    <summary>{{ __('Annual backup schedule') }}</summary>
                    <div class="settings-faq-panel">
                        <div>
                            <p>{{ __('Automatic backups use the saved daily or weekly schedule in :timezone. The annual archive is created on December 31 at 11:59 PM. Deployment staff must configure Windows Task Scheduler, cron, or an equivalent host scheduler once; without that service, automatic backups cannot run. Small installations use synchronous backup processing by default.', ['timezone' => config('backup.timezone', 'Asia/Manila')]) }}</p>
                            <p>{{ __('A server operator can run :command from the project directory to merge missing records and files from an annual archive. This is not a full database replacement restore.', ['command' => 'php artisan backup:restore-annual YYYY']) }}</p>
                        </div>
                    </div>
                </details>
                <details>
                    <summary>{{ __('Protect applicant data') }}</summary>
                    <div class="settings-faq-panel"><div><p>{{ __('Applicant records and backup archives contain private information. Access them only for official PESO work, and store downloaded backups in an approved secure location. Never share login credentials or backup files.') }}</p></div></div>
                </details>
            </div>
        </div>
    </section>

    <section class="settings-section" id="admin-audit" role="tabpanel" tabindex="0" aria-labelledby="settings-tab-admin-audit" data-settings-panel>
        <div class="card-header"><h2 id="admin-audit-heading"><x-icon class="fa-solid fa-user-shield" aria-hidden="true" /> {{ __('Admin Audit Logs') }}</h2></div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.preferences') }}#admin-audit" class="admin-audit-filters" aria-label="{{ __('Filter administrator audit logs') }}">
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
                <label>{{ __('From Date') }} <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></label>
                <label>{{ __('To Date') }} <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></label>
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
                            @php
                                $resultBadgeClass = match (strtolower($event->result)) {
                                    'successful', 'success' => 'is-success',
                                    'failed', 'failure' => 'is-danger',
                                    'warning' => 'is-warning',
                                    default => '',
                                };
                            @endphp
                            <tr>
                                <td>@adminDate($event->created_at, true)</td>
                                <td>{{ $event->actor_name }}</td>
                                <td>{{ __($event->action) }}</td>
                                <td>{{ $event->applicant?->name ?? $event->application?->full_name ?? '—' }}</td>
                                <td><span class="settings-status {{ $resultBadgeClass }}">{{ __(ucfirst($event->result)) }}</span></td>
                                <td>{{ $event->ip_address ?: __('Not recorded') }}</td>
                                <td><a class="settings-audit-details" href="{{ route('admin.applicant-audit.event', $event) }}">{{ __('Details') }}</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

    <section class="settings-section" id="backup" role="tabpanel" tabindex="0" aria-labelledby="settings-tab-backup" data-settings-panel>
        <div class="card-header"><h2 id="backup-heading"><x-icon class="fa-solid fa-database" aria-hidden="true" /> {{ __('Backup') }}</h2></div>
        <div class="card-body">
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
                                default => __('Backup operation is starting.'),
                            } }}
                        @endif
                    </span>
                    @if(in_array($backupOperation->status, ['queued', 'processing', 'verifying'], true))
                        <progress data-backup-progress aria-label="{{ __('Backup operation progress') }}" style="display:block;width:100%;height:8px;margin-top:12px;"></progress>
                    @endif
                    @if($backupOperation->status === 'completed' && $backupOperation->action === 'create' && $backupOperation->archive_path)
                        <a data-backup-download href="{{ route('admin.backup.operations.download', $backupOperation) }}">{{ __('Download backup') }}</a>
                    @endif
                </div>
            @endif
            <section class="settings-card-subsection settings-backup-card" aria-labelledby="manual-backup-heading">
                <h3 id="manual-backup-heading" style="margin-bottom:8px;">{{ __('Manual Backup') }}</h3>
                <p style="line-height:1.6;margin-bottom:16px;">{{ __('Create a verified ZIP backup of the database, private and public stored files, and application logs. Backup archives contain confidential applicant information.') }}</p>
                <form method="POST" action="{{ route('admin.backup.download') }}" data-backup-create-form
                      data-swal-confirm="{{ __('Create a new verified backup containing confidential application data?') }}"
                      data-swal-title="{{ __('Create Backup') }}"
                      data-swal-confirm-action="{{ __('Create Backup') }}"
                      data-swal-cancel-action="{{ __('Cancel') }}">
                    @csrf
                    <input type="hidden" name="_idempotency_token" value="{{ \Illuminate\Support\Str::random(40) }}">
                    <div class="settings-backup-actions">
                        <button class="btn btn-primary" type="submit"><x-icon class="fa-solid fa-database" aria-hidden="true" /> {{ __('Create Backup') }}</button>
                        <span class="settings-description">{{ __('A verified archive will appear in Backup History when ready.') }}</span>
                    </div>
                    <p data-backup-submit-progress role="status" aria-live="polite" hidden style="margin-top:10px;color:var(--text-muted);">
                        {{ __('Creating and verifying your backup. This may take a few minutes.') }}
                        <progress aria-label="{{ __('Backup creation progress') }}" style="display:block;width:100%;height:8px;margin-top:8px;"></progress>
                    </p>
                </form>
                <p style="margin-top:12px;color:var(--text-muted);">{{ __('To download a completed backup, use its Download action in Backup History.') }}</p>
                <p style="margin-top:12px;color:var(--text-muted);">{{ __('Backup storage used: :size', ['size' => number_format($backupStorage['bytes'] / 1048576, 2).' MB']) }}</p>
                @if(!$backupStorage['available'])
                    <div class="alert alert-danger" role="alert">{{ __('Private backup storage is unavailable or not writable. Contact the system administrator.') }}</div>
                @elseif($backupStorage['free_bytes'] !== null && $backupStorage['free_bytes'] < $backupStorage['minimum_free_bytes'])
                    <div class="alert alert-danger" role="alert">{{ __('Available disk space is below the safety threshold. Backup creation is paused.') }}</div>
                @endif
            </section>

            <section class="settings-card-subsection settings-backup-card" aria-labelledby="automatic-backup-heading">
                <h3 id="automatic-backup-heading">{{ __('Automatic Backup') }}</h3>
                @php
                    $automaticStatusClass = match ($automaticBackupStatus['status']) {
                        'completed' => 'is-success',
                        'processing' => 'is-info',
                        'failed' => 'is-danger',
                        default => '',
                    };
                @endphp
                <dl class="settings-summary-grid">
                    <div class="settings-summary-item">
                        <dt>{{ __('Automatic Backup') }}</dt>
                        <dd><span class="settings-status {{ $automaticBackupStatus['enabled'] ? 'is-enabled' : 'is-disabled' }}">{{ __($automaticBackupStatus['enabled'] ? 'Enabled' : 'Disabled') }}</span></dd>
                    </div>
                    <div class="settings-summary-item">
                        <dt>{{ __('Backup status') }}</dt>
                        <dd><span class="settings-status {{ $automaticStatusClass }}">{{ __(match ($automaticBackupStatus['status']) {
                            'completed' => 'Completed',
                            'processing' => 'Processing',
                            'failed' => 'Failed',
                            default => 'Unverified',
                        }) }}</span></dd>
                    </div>
                    <div class="settings-summary-item">
                        <dt>{{ __('Last successful backup') }}</dt>
                        <dd>{{ $automaticBackupStatus['last_successful_at'] ? \Illuminate\Support\Carbon::parse($automaticBackupStatus['last_successful_at'])->timezone($automaticBackupStatus['timezone'])->translatedFormat('M j, Y g:i A') : __('Not available') }}</dd>
                    </div>
                    <div class="settings-summary-item">
                        <dt>{{ __('Next scheduled backup') }}</dt>
                        <dd>{{ $automaticBackupStatus['next_daily_at']?->translatedFormat('M j, Y g:i A') ?? __('Disabled') }}</dd>
                    </div>
                    <div class="settings-summary-item">
                        <dt>{{ __('Backup size') }}</dt>
                        <dd>{{ $automaticBackupStatus['size'] ? number_format($automaticBackupStatus['size'] / 1048576, 2).' MB' : '—' }}</dd>
                    </div>
                    <div class="settings-summary-item">
                        <dt>{{ __('Verification') }}</dt>
                        <dd><span class="settings-status {{ $automaticBackupStatus['verification'] === 'verified' ? 'is-success' : '' }}">{{ __($automaticBackupStatus['verification'] === 'verified' ? 'Verified' : 'Unverified') }}</span></dd>
                    </div>
                    <div class="settings-summary-item">
                        <dt>{{ __('Annual safety archive') }}</dt>
                        <dd>{{ __('December 31 at 11:59 PM (:timezone)', ['timezone' => $automaticBackupStatus['timezone']]) }} · {{ __('Next: :date', ['date' => $automaticBackupStatus['next_annual_at']->translatedFormat('M j, Y g:i A')]) }}</dd>
                    </div>
                </dl>
                <p style="margin:12px 0 0;color:var(--text-muted);">{{ __('Weekly backups run on Monday at the selected time. Annual safety archives are separate and are never removed by retention cleanup.') }}</p>
                <form method="POST" action="{{ route('admin.backup.settings.update') }}" style="margin-top:16px;"
                      data-backup-settings-form
                      data-swal-confirm="{{ (bool) old('backup_enabled', $backupSettings->backup_enabled) ? __('Enable automatic backups with the selected schedule?') : __('Disable scheduled automatic backups?') }}"
                      data-confirm-enable="{{ __('Enable automatic backups with the selected schedule?') }}"
                      data-confirm-disable="{{ __('Disable scheduled automatic backups?') }}"
                      data-swal-title="{{ __('Confirm Backup Settings') }}"
                      data-swal-confirm-action="{{ __('Save Settings') }}"
                      data-swal-cancel-action="{{ __('Cancel') }}">
                    @csrf
                    @method('PUT')
                    <div class="settings-form-grid">
                        <div class="form-group">
                            <span style="display:block;margin-bottom:7px;color:var(--text);font-size:.78rem;font-weight:700;">{{ __('Automatic Backup') }}</span>
                            <input type="hidden" name="backup_enabled" value="0">
                            <label for="backup_enabled" style="display:flex;align-items:center;gap:8px;">
                                <input id="backup_enabled" type="checkbox" name="backup_enabled" value="1" @checked((bool) old('backup_enabled', $backupSettings->backup_enabled))>
                                {{ __('Enable automatic backups') }}
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="backup_frequency">{{ __('Backup frequency') }}</label>
                            <select id="backup_frequency" name="backup_frequency" required>
                                <option value="daily" @selected(old('backup_frequency', $backupSettings->backup_frequency ?: 'daily') === 'daily')>{{ __('Daily') }}</option>
                                <option value="weekly" @selected(old('backup_frequency', $backupSettings->backup_frequency) === 'weekly')>{{ __('Weekly (Monday)') }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="backup_time">{{ __('Preferred backup time') }} ({{ $automaticBackupStatus['timezone'] }})</label>
                            <input id="backup_time" name="backup_time" type="time" value="{{ old('backup_time', $backupSettings->backup_time ?: '02:00') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="backup_retention_days">{{ __('Retention period') }}</label>
                            <select id="backup_retention_days" name="backup_retention_days" required>
                                @foreach([7, 14, 30, 90] as $days)
                                    <option value="{{ $days }}" @selected((int) old('backup_retention_days', $backupSettings->backup_retention_days ?: 7) === $days)>{{ trans_choice(':count day|:count days', $days, ['count' => $days]) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary settings-save" type="submit"><x-icon class="fa-solid fa-floppy-disk" aria-hidden="true" /> {{ __('Save Settings') }}</button>
                </form>
                @if($failedBackupCount > 0)
                    <div class="alert alert-warning" role="alert" style="margin-top:12px;">
                        {{ trans_choice(':count backup operation failed in the last 7 days.|:count backup operations failed in the last 7 days.', $failedBackupCount, ['count' => $failedBackupCount]) }}
                    </div>
                    <ul style="margin:0 0 12px 20px;color:var(--text-muted);">
                        @foreach($recentBackupFailures as $failedOperation)
                            <li>{{ ucfirst($failedOperation->action) }} · {{ $failedOperation->updated_at->translatedFormat('M j, Y g:i A') }} · {{ $failedOperation->failure_message }}</li>
                        @endforeach
                    </ul>
                @endif
                @if($backupStorage['free_bytes'] !== null)
                    <p style="margin-top:10px;color:var(--text-muted);">{{ __('Free backup storage: :size', ['size' => number_format($backupStorage['free_bytes'] / 1073741824, 2).' GB']) }}</p>
                @endif
            </section>

            <section class="settings-card-subsection settings-backup-card" aria-labelledby="backup-history-heading">
                <h3 id="backup-history-heading">{{ __('Backup History') }}</h3>
                <div class="settings-table-wrap" role="region" aria-label="{{ __('Backup History') }}" tabindex="0">
                    <table class="settings-table settings-backup-history">
                        <thead><tr><th>{{ __('Backup name') }}</th><th>{{ __('Type') }}</th><th>{{ __('Created') }}</th><th>{{ __('Size') }}</th><th>{{ __('Verification') }}</th><th>{{ __('Status') }}</th><th>{{ __('Actions') }}</th></tr></thead>
                        <tbody>
                        @forelse($backupHistory as $historyItem)
                            @php
                                $historyStatusClass = match ($historyItem['status']) {
                                    'completed' => 'is-success',
                                    'failed' => 'is-danger',
                                    'expired' => 'is-warning',
                                    'queued', 'processing', 'verifying' => 'is-info',
                                    default => '',
                                };
                            @endphp
                            <tr>
                                <td>{{ $historyItem['name'] }}</td>
                                <td>{{ __(ucfirst($historyItem['type'])) }}</td>
                                <td>{{ $historyItem['created_at']->translatedFormat('M j, Y g:i A') }}</td>
                                <td>{{ $historyItem['size'] ? number_format($historyItem['size'] / 1048576, 2).' MB' : '—' }}</td>
                                <td><span class="settings-status {{ $historyItem['verified'] ? 'is-success' : '' }}">{{ __($historyItem['verified'] ? 'Verified' : 'Unverified') }}</span></td>
                                <td><span class="settings-status {{ $historyStatusClass }}">{{ __(match ($historyItem['status']) {
                                    'queued', 'processing', 'verifying' => 'Processing',
                                    'completed' => 'Completed',
                                    'failed' => 'Failed',
                                    'expired' => 'Expired',
                                    default => 'Unverified',
                                }) }}</span></td>
                                <td>
                                    @if($historyItem['download_url'])
                                        <a class="btn btn-outline btn-sm" href="{{ $historyItem['download_url'] }}">{{ __('Download') }}</a>
                                    @endif
                                    @if($historyItem['type'] === 'annual' && $historyItem['verified'] && $historyItem['year'])
                                        <form method="POST" action="{{ route('admin.backup.archive.restore', $historyItem['year']) }}" data-swal-confirm="{{ __('This adds missing records and files only; it does not replace existing data. A verified safety backup will be created first. This is not a full system replacement restore.') }}">
                                            @csrf
                                            <input type="hidden" name="_idempotency_token" value="{{ \Illuminate\Support\Str::random(40) }}">
                                            <label for="annual_restore_password_{{ $historyItem['year'] }}">{{ __('Current password') }}</label>
                                            <input id="annual_restore_password_{{ $historyItem['year'] }}" name="current_password" type="password" autocomplete="current-password" required>
                                            <button class="btn btn-outline btn-sm" type="submit">{{ __('Restore (merge)') }}</button>
                                        </form>
                                    @elseif(!$historyItem['download_url'])
                                        <span>—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7">{{ __('No backup operations are recorded yet.') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="settings-card-subsection settings-backup-card" aria-labelledby="restore-backup-heading">
                <h3 id="restore-backup-heading">{{ __('Restore from Backup') }}</h3>
                <div class="alert alert-warning" role="alert">
                    <strong>{{ __('Important:') }}</strong>
                    {{ __('This existing restore adds missing records and files only; it does not overwrite modified records or replace the live database. Full-system cutover is not available in this application. A verified safety backup is created before merging.') }}
                </div>
                <form method="POST" action="{{ route('admin.backup.restore') }}" enctype="multipart/form-data" data-swal-confirm="{{ __('Only missing records and files will be added; existing data will not be replaced. A verified safety backup will be created first. Continue?') }}">
                @csrf
                <input type="hidden" name="_idempotency_token" value="{{ \Illuminate\Support\Str::random(40) }}">
                <div class="settings-restore-fields">
                    <label for="backup">{{ __('Backup ZIP') }}<input id="backup" name="backup" type="file" accept=".zip,application/zip" required></label>
                    <label for="restore_current_password">{{ __('Current password') }}<input id="restore_current_password" name="current_password" type="password" autocomplete="current-password" required></label>
                </div>
                    <button class="btn btn-primary" type="submit"><x-icon class="fa-solid fa-rotate-left" aria-hidden="true" /> {{ __('Validate and Merge Backup') }}</button>
                </form>
            </section>
        </div>
    </section>
</main>
<script>
    const backupSettingsForm = document.querySelector('[data-backup-settings-form]');
    const backupEnabledInput = backupSettingsForm?.querySelector('[name="backup_enabled"][type="checkbox"]');
    if (backupSettingsForm && backupEnabledInput) {
        const updateBackupConfirmation = () => {
            backupSettingsForm.dataset.swalConfirm = backupEnabledInput.checked
                ? backupSettingsForm.dataset.confirmEnable
                : backupSettingsForm.dataset.confirmDisable;
        };
        backupEnabledInput.addEventListener('change', updateBackupConfirmation);
        updateBackupConfirmation();
    }

    (() => {
        const tablist = document.querySelector('[data-settings-tabs]');
        if (!tablist) return;

        const tabs = Array.from(tablist.querySelectorAll('[data-settings-tab]'));
        const panels = tabs
            .map((tab) => document.getElementById(tab.getAttribute('aria-controls')))
            .filter(Boolean);
        const sectionIds = new Set(panels.map((panel) => panel.id));

        const activate = (sectionId, focusTab = false) => {
            if (!sectionIds.has(sectionId)) return;

            tabs.forEach((tab) => {
                const active = tab.getAttribute('aria-controls') === sectionId;
                tab.setAttribute('aria-selected', String(active));
                tab.tabIndex = active ? 0 : -1;
                if (active && focusTab) tab.focus();
            });
            panels.forEach((panel) => {
                const active = panel.id === sectionId;
                panel.hidden = !active;
                panel.setAttribute('aria-hidden', String(!active));
            });
        };

        tablist.addEventListener('click', (event) => {
            const tab = event.target.closest('[data-settings-tab]');
            if (tab) activate(tab.getAttribute('aria-controls'));
        });
        tablist.addEventListener('keydown', (event) => {
            const currentIndex = tabs.indexOf(event.target.closest('[data-settings-tab]'));
            if (currentIndex < 0) return;

            let nextIndex = currentIndex;
            if (event.key === 'ArrowRight') nextIndex = (currentIndex + 1) % tabs.length;
            else if (event.key === 'ArrowLeft') nextIndex = (currentIndex - 1 + tabs.length) % tabs.length;
            else if (event.key === 'Home') nextIndex = 0;
            else if (event.key === 'End') nextIndex = tabs.length - 1;
            else return;

            event.preventDefault();
            tabs[nextIndex].focus();
            tabs[nextIndex].click();
        });

        const hashSection = window.location.hash.slice(1);
        activate(sectionIds.has(hashSection) ? hashSection : tablist.dataset.initialSection);
        window.addEventListener('hashchange', () => {
            const sectionId = window.location.hash.slice(1);
            if (sectionIds.has(sectionId)) activate(sectionId);
        });
    })();

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
    document.querySelector('[data-backup-create-form]')?.addEventListener('submit', function () {
        const progress = this.querySelector('[data-backup-submit-progress]');
        if (progress) progress.hidden = false;
        const button = this.querySelector('button[type="submit"]');
        if (button) button.disabled = true;
    });
</script>
@if($backupOperation && in_array($backupOperation->status, ['queued', 'processing', 'verifying'], true))
    <script>
        (() => {
            const panel = document.getElementById('backup-operation-status');
            if (!panel) return;

            const message = panel.querySelector('[data-backup-operation-message]');
            const progress = panel.querySelector('[data-backup-progress]');
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
                    if (progress && ['completed', 'failed'].includes(operation.status)) {
                        progress.hidden = true;
                    }

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
