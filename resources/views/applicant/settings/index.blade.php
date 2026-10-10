<!DOCTYPE html>
<html lang="{{ ($settings->language ?? 'en') === 'fil' ? 'fil' : 'en' }}" data-theme="{{ $settings->appearance ?? $settings->theme_preference ?? 'system' }}" data-sidebar-behavior="{{ $settings->sidebar_behavior ?? 'auto' }}" data-font-size="{{ $settings->font_size ?? 'medium' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Settings') }} - {{ __('SPES Applicant Portal') }}</title>
    <style>
        :root {
            --primary:#8b0000;
            --primary-dark:#660000;
            --bg:#f3f4f6;
            --white:#ffffff;
            --text:#1f2937;
            --muted:#6b7280;
            --border:#e5e7eb;
            --shadow:0 10px 25px rgba(17,24,39,.08);
            --sidebar-w: 260px;
        }
        html[data-theme="dark"] {
            --bg:#111827;
            --white:#1f2937;
            --text:#f9fafb;
            --muted:#d1d5db;
            --border:#374151;
            --shadow:0 10px 25px rgba(0,0,0,.35);
        }
        body {
            margin:0;
            font-family:'Segoe UI', Arial, sans-serif;
            background:var(--bg);
            color:var(--text);
        }
        .settings-shell {
            max-width: calc(100% - var(--sidebar-w));
            margin-left: var(--sidebar-w);
            padding:90px 20px 40px;
        }
        .settings-header {
            margin-bottom:20px;
        }
        .settings-header h1 {
            margin:0;
            font-size:2rem;
            color:var(--primary);
        }
        .settings-header p {
            margin:8px 0 0;
            color:var(--muted);
        }
        .settings-grid {
            display:grid;
            gap:20px;
        }
        .settings-card {
            background:var(--white);
            border:1px solid var(--border);
            border-radius:16px;
            box-shadow:var(--shadow);
            padding:20px;
        }
        .settings-card h2 {
            margin:0 0 12px;
            font-size:1.2rem;
            color:var(--primary);
        }
        .settings-card p {
            color:var(--muted);
            margin:0 0 16px;
            line-height:1.5;
        }
        .settings-row {
            display:grid;
            grid-template-columns:repeat(2, minmax(0, 1fr));
            gap:12px;
        }
        .field {
            display:flex;
            flex-direction:column;
            gap:6px;
        }
        .field label {
            font-size:.82rem;
            font-weight:600;
            color:var(--text);
        }
        input, select {
            width:100%;
            min-height:42px;
            padding:9px 12px;
            border:1px solid var(--border);
            border-radius:10px;
            background:var(--white);
            color:var(--text);
        }
        select option {
            background:var(--white);
            color:var(--text);
        }
        .btn {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-height:42px;
            padding:10px 16px;
            border:0;
            border-radius:10px;
            background:var(--primary);
            color:#fff;
            font-weight:700;
            cursor:pointer;
            margin-top:12px;
        }
        .inline-checks {
            display:grid;
            gap:12px;
        }
        .check-item {
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:16px;
            padding:12px 14px;
            border:1px solid var(--border);
            border-radius:12px;
        }
        .check-item strong {
            display:block;
            margin-bottom:3px;
        }
        .check-item small {
            color:var(--muted);
        }
        .check-item input {
            width:18px;
            height:18px;
        }
        .message,
        .errors {
            border-radius:12px;
            padding:12px 14px;
            margin-bottom:16px;
        }
        .message {
            background:#e8f5e9;
            color:#1b5e20;
            border:1px solid #b7e4c7;
        }
        .errors {
            background:#fff1f2;
            color:#9f1239;
            border:1px solid #fecdd3;
        }
        .errors ul {
            margin:8px 0 0 18px;
            padding:0;
        }
        @media (max-width: 768px) {
            .settings-shell {
                max-width: 100%;
                margin-left: 0;
                padding-top: 70px;
            }
            .settings-row { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
    <x-applicant-sidebar />

    <main class="settings-shell">
        @if(session('settings_success'))
            <div class="message" role="status">{{ __(session('settings_success')) }}</div>
        @endif

        @if($errors->any())
            <div class="errors" role="alert">
                <strong>{{ __('Please review the fields below.') }}</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <header class="settings-header">
            <h1>{{ __('Settings') }}</h1>
            <p>{{ __('Manage your account, security, notifications, and display preferences.') }}</p>
        </header>

        <div class="settings-grid">
            <section class="settings-card">
                <h2><x-icon class="fa-solid fa-user" /> {{ __('Account') }}</h2>
                <p>{{ __('Keep your account details and login credentials secure.') }}</p>

                <div class="settings-row">
                    <div class="field">
                        <label>{{ __('Name') }}</label>
                        <input type="text" value="{{ $user->name }}" readonly>
                    </div>
                    <div class="field">
                        <label>{{ __('Email') }}</label>
                        <input type="text" value="{{ $user->email }}" readonly>
                    </div>
                </div>

                <form id="mobile-number-settings" method="POST" action="{{ route('settings.mobile-number.update') }}" style="margin-top: 16px;">
                    @csrf
                    @method('PUT')
                    <div class="field" style="max-width: 420px;">
                        <label for="mobile_number">{{ __('Mobile number') }}</label>
                        <input
                            id="mobile_number"
                            name="mobile_number"
                            type="tel"
                            inputmode="tel"
                            autocomplete="tel"
                            value="{{ old('mobile_number', $user->profile?->contact_number ?: $user->contact_number) }}"
                            pattern="(09[0-9]{9}|\+639[0-9]{9})"
                            placeholder="09XXXXXXXXX or +639XXXXXXXXX"
                            required
                        >
                        <small>{{ __('Enter a Philippine mobile number like 09XXXXXXXXX or +639XXXXXXXXX.') }}</small>
                    </div>
                    <button class="btn" type="submit">{{ __('Save Mobile Number') }}</button>
                </form>

                <form method="POST" action="{{ route('settings.password.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="settings-row" style="margin-top: 16px;">
                        <div class="field">
                            <label for="current_password">{{ __('Current password') }}</label>
                            <input id="current_password" name="current_password" type="password" required>
                        </div>
                        <div class="field">
                            <label for="password">{{ __('New password') }}</label>
                            <input id="password" name="password" type="password" required>
                        </div>
                    </div>
                    <div class="field" style="max-width: 420px; margin-top: 12px;">
                        <label for="password_confirmation">{{ __('Confirm new password') }}</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required>
                    </div>
                    <button class="btn" type="submit">{{ __('Change Password') }}</button>
                </form>

                <form method="POST" action="{{ route('settings.email.request') }}" style="margin-top: 24px;">
                    @csrf
                    <div class="settings-row">
                        <div class="field">
                            <label for="email_address">{{ __('New email address') }}</label>
                            <input id="email_address" name="email" type="email" value="{{ old('email') }}" required>
                        </div>
                        <div class="field">
                            <label for="change_email_password">{{ __('Current password') }}</label>
                            <input id="change_email_password" name="current_password" type="password" required>
                        </div>
                    </div>
                    <button class="btn" type="submit">{{ __('Verify New Email') }}</button>
                </form>
            </section>

            <section class="settings-card">
                <h2><x-icon class="fa-solid fa-shield-halved" /> {{ __('Security') }}</h2>
                <p>{{ __('Protect your account with reminder and verification choices.') }}</p>
                <form method="POST" action="{{ route('settings.preferences.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="inline-checks">
                        <label class="check-item" for="two_factor_authentication">
                            <div>
                                <strong>{{ __('Two-Factor Authentication') }}</strong>
                                <small>{{ __('Require a second verification step.') }}</small>
                            </div>
                            <input id="two_factor_authentication" type="checkbox" name="two_factor_authentication" value="1" @checked(old('two_factor_authentication', $settings->two_factor_authentication ?? false))>
                        </label>
                        <label class="check-item" for="login_notifications">
                            <div>
                                <strong>{{ __('Login Notifications') }}</strong>
                                <small>{{ __('Notify me when a new device signs in.') }}</small>
                            </div>
                            <input id="login_notifications" type="checkbox" name="login_notifications" value="1" @checked(old('login_notifications', $settings->login_notifications ?? true))>
                        </label>
                    </div>
                    <button class="btn" type="submit">{{ __('Save Security Settings') }}</button>
                </form>
            </section>

            <section class="settings-card">
                <h2><x-icon class="fa-solid fa-bell" /> {{ __('Notifications') }}</h2>
                <p>{{ __('Choose the system and email alerts you want to receive.') }}</p>
                <form method="POST" action="{{ route('settings.notifications.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="inline-checks">
                        <label class="check-item" for="email_notifications">
                            <div>
                                <strong>{{ __('Email Notifications') }}</strong>
                                <small>{{ __('Important updates delivered to email.') }}</small>
                            </div>
                            <input id="email_notifications" type="checkbox" name="email_notifications" value="1" @checked(old('email_notifications', $settings->email_notifications ?? true))>
                        </label>
                        <label class="check-item" for="system_notifications">
                            <div>
                                <strong>{{ __('System Notifications') }}</strong>
                                <small>{{ __('Portal updates and announcements.') }}</small>
                            </div>
                            <input id="system_notifications" type="checkbox" name="system_notifications" value="1" @checked(old('system_notifications', $settings->system_notifications ?? true))>
                        </label>
                        <label class="check-item" for="application_updates">
                            <div>
                                <strong>{{ __('Application Updates') }}</strong>
                                <small>{{ __('Changes to your application status.') }}</small>
                            </div>
                            <input id="application_updates" type="checkbox" name="application_updates" value="1" @checked(old('application_updates', $settings->application_updates ?? true))>
                        </label>
                    </div>
                    <button class="btn" type="submit">{{ __('Save Notifications') }}</button>
                </form>
            </section>

            <section class="settings-card">
                <h2><x-icon class="fa-solid fa-palette" /> {{ __('Appearance & Language') }}</h2>
                <p>{{ __('Adjust your display and local preferences for the dashboard.') }}</p>
                <form method="POST" action="{{ route('settings.preferences.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="settings-row">
                        <div class="field">
                            <label for="appearance">{{ __('Appearance') }}</label>
                            <select id="appearance" name="appearance">
                                <option value="light" @selected(old('appearance', $settings->appearance ?? $settings->theme_preference ?? 'system') === 'light')>{{ __('Light') }}</option>
                                <option value="dark" @selected(old('appearance', $settings->appearance ?? $settings->theme_preference ?? 'system') === 'dark')>{{ __('Dark') }}</option>
                                <option value="system" @selected(old('appearance', $settings->appearance ?? $settings->theme_preference ?? 'system') === 'system')>{{ __('System Default') }}</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="language">{{ __('Language') }}</label>
                            <select id="language" name="language">
                                <option value="en" @selected(old('language', $settings->language ?? 'en') === 'en')>{{ __('English') }}</option>
                                <option value="fil" @selected(old('language', $settings->language ?? 'en') === 'fil')>{{ __('Filipino') }}</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="sidebar_behavior">{{ __('Sidebar Behavior') }}</label>
                            <select id="sidebar_behavior" name="sidebar_behavior">
                                <option value="auto" @selected(old('sidebar_behavior', $settings->sidebar_behavior ?? 'auto') === 'auto')>{{ __('Automatic (responsive)') }}</option>
                                <option value="expanded" @selected(old('sidebar_behavior', $settings->sidebar_behavior ?? 'auto') === 'expanded')>{{ __('Expanded') }}</option>
                                <option value="collapsed" @selected(old('sidebar_behavior', $settings->sidebar_behavior ?? 'auto') === 'collapsed')>{{ __('Collapsed (icons only)') }}</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="font_size">{{ __('Font / Display Size') }}</label>
                            <select id="font_size" name="font_size">
                                <option value="small" @selected(old('font_size', $settings->font_size ?? 'medium') === 'small')>{{ __('Small') }}</option>
                                <option value="medium" @selected(old('font_size', $settings->font_size ?? 'medium') === 'medium')>{{ __('Medium') }}</option>
                                <option value="large" @selected(old('font_size', $settings->font_size ?? 'medium') === 'large')>{{ __('Large') }}</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn" type="submit">{{ __('Save Preferences') }}</button>
                </form>
            </section>
        </div>
    </main>
</body>
</html>
