<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Secure your account') }} - {{ config('app.name', 'PESO LAL-LO') }}</title>
    <link rel="stylesheet" href="{{ asset('css/request-loading.css') }}?v={{ filemtime(public_path('css/request-loading.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/personal-security-question.css') }}?v={{ filemtime(public_path('css/personal-security-question.css')) }}">
    <style>
        :root { color-scheme: light dark; --maroon:#750016; --gold:#e4b63f; --surface:#fff; --text:#262626; --muted:#5b6470; --border:#d1d5db; --page:#f5f3ef; }
        @media (prefers-color-scheme: dark) { :root { --surface:#1f2937; --text:#f9fafb; --muted:#cbd5e1; --border:#4b5563; --page:#111827; } }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:20px; background:var(--page); color:var(--text); font:16px/1.5 system-ui,sans-serif; }
        main { width:min(100%,650px); padding:clamp(22px,5vw,36px); border:1px solid var(--border); border-top:5px solid var(--gold); border-radius:16px; background:var(--surface); box-shadow:0 14px 38px #0002; }
        h1 { margin:0 0 8px; color:var(--maroon); font-size:1.65rem; }
        p { color:var(--muted); }
        .grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
        label { display:block; margin:14px 0 6px; font-weight:700; }
        input { width:100%; min-height:46px; padding:10px 12px; border:1px solid var(--border); border-radius:8px; background:var(--surface); color:var(--text); font:inherit; }
        .security-question-picker { margin-bottom:14px; --security-question-border:var(--border); --security-question-surface:var(--surface); --security-question-text:var(--text); --security-question-focus:rgba(228,182,63,.45); }
        input:focus,button:focus-visible { outline:3px solid var(--gold); outline-offset:2px; }
        button { min-height:44px; margin-top:18px; padding:10px 16px; border:0; border-radius:8px; background:var(--maroon); color:white; font:inherit; font-weight:700; cursor:pointer; }
        .error { margin:14px 0; padding:12px; border:1px solid #dc7b85; border-radius:8px; background:#8f1523; color:white; }
        @media (max-width:600px) { .grid { grid-template-columns:1fr; } }
        @media (prefers-color-scheme:dark) { h1 { color:#f2c94c; } }
    </style>
</head>
<body>
<main>
    <h1>{{ __('Secure your account') }}</h1>
    <p>{{ __('Your previous security question was cleared after account recovery. Set a new one to continue. This knowledge-based check is not an independent second factor like a passkey or authenticator app.') }}</p>
    @if(session('status'))<p role="status">{{ session('status') }}</p>@endif
    @if($errors->any())<div class="error" role="alert">{{ $errors->first() }}</div>@endif
    @php($isAdmin = auth()->user()->role === 'admin')
    <form method="POST" action="{{ $isAdmin ? route('admin.personal-security.configuration.update') : route('personal-security.configuration.update') }}" data-loading-label="{{ __('Saving...') }}">
        @csrf
        @method('PUT')
        <x-security-question-select id="required_security_question" autofocus />
        <div class="grid">
            <div>
                <label for="required_security_answer">{{ __('Private answer') }}</label>
                <input id="required_security_answer" name="security_answer" type="password" minlength="8" maxlength="255" autocomplete="new-password" required>
            </div>
            <div>
                <label for="required_security_answer_confirmation">{{ __('Confirm private answer') }}</label>
                <input id="required_security_answer_confirmation" name="security_answer_confirmation" type="password" minlength="8" maxlength="255" autocomplete="new-password" required>
            </div>
        </div>
        <p>{{ __('Choose an answer that is memorable to you and difficult for others to guess. Answers are case-insensitive and repeated whitespace is ignored.') }}</p>
        <label for="required_security_password">{{ __('Current password') }}</label>
        <input id="required_security_password" name="current_password" type="password" autocomplete="current-password" required>
        <button type="submit" data-loading-button>{{ __('Save new security question and continue') }}</button>
    </form>
</main>
<script src="{{ asset('js/request-loading.js') }}?v={{ filemtime(public_path('js/request-loading.js')) }}"></script>
</body>
</html>
