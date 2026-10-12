<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Recover account access') }} - {{ config('app.name', 'PESO LAL-LO') }}</title>
    <link rel="stylesheet" href="{{ asset('css/request-loading.css') }}?v={{ filemtime(public_path('css/request-loading.css')) }}">
    <style>
        :root { color-scheme: light dark; --maroon:#750016; --gold:#e4b63f; --surface:#fff; --text:#262626; --muted:#5b6470; --border:#d1d5db; --page:#f5f3ef; }
        @media (prefers-color-scheme: dark) { :root { --surface:#1f2937; --text:#f9fafb; --muted:#cbd5e1; --border:#4b5563; --page:#111827; } }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:20px; background:var(--page); color:var(--text); font:16px/1.5 system-ui,sans-serif; }
        main { width:min(100%,520px); padding:clamp(22px,5vw,36px); border:1px solid var(--border); border-top:5px solid var(--gold); border-radius:16px; background:var(--surface); box-shadow:0 14px 38px #0002; }
        h1 { margin:0 0 8px; color:var(--maroon); font-size:1.65rem; }
        h2 { margin:24px 0 8px; font-size:1.05rem; }
        p { color:var(--muted); }
        label { display:block; margin:14px 0 6px; font-weight:700; }
        input { width:100%; min-height:46px; padding:10px 12px; border:1px solid var(--border); border-radius:8px; background:var(--surface); color:var(--text); font:inherit; }
        input:focus,button:focus-visible,a:focus-visible { outline:3px solid var(--gold); outline-offset:2px; }
        button { min-height:44px; margin-top:12px; padding:10px 16px; border:0; border-radius:8px; background:var(--maroon); color:white; font:inherit; font-weight:700; cursor:pointer; }
        .error,.success { margin:14px 0; padding:12px; border:1px solid #dc7b85; border-radius:8px; background:#8f1523; color:white; }
        .success { border-color:#78b68c; background:#17633c; }
        a { color:var(--maroon); font-weight:650; }
        @media (prefers-color-scheme:dark) { a,h1 { color:#f2c94c; } }
    </style>
</head>
<body>
<main>
    <h1>{{ __('Recover account access') }}</h1>
    <p>{{ __('If you can’t answer your question, verify access to your registered email. You will need to set a new security question before using the portal.') }}</p>
    @if(session('status'))<div class="success" role="status">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="error" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('login.personal-security.recovery.send') }}" data-loading-label="{{ __('Sending...') }}">
        @csrf
        <button type="submit" data-loading-button>{{ __('Send recovery code to my email') }}</button>
    </form>
    <h2>{{ __('Enter recovery code') }}</h2>
    <p>{{ __('The six-digit code expires after 10 minutes. Never share it with anyone.') }}</p>
    <form method="POST" action="{{ route('login.personal-security.recovery.verify') }}" data-loading-label="{{ __('Verifying...') }}">
        @csrf
        <label for="recovery_code">{{ __('Recovery code') }}</label>
        <input id="recovery_code" name="recovery_code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required>
        <button type="submit" data-loading-button>{{ __('Verify code and continue') }}</button>
    </form>
    <p><a href="{{ route('login.personal-security.challenge') }}">{{ __('Return to the security question') }}</a></p>
</main>
<script src="{{ asset('js/request-loading.js') }}?v={{ filemtime(public_path('js/request-loading.js')) }}"></script>
</body>
</html>
