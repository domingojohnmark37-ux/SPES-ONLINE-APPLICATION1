<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Verify your identity') }} - {{ config('app.name', 'PESO LAL-LO') }}</title>
    <link rel="stylesheet" href="{{ asset('css/request-loading.css') }}?v={{ filemtime(public_path('css/request-loading.css')) }}">
    <style>
        :root { color-scheme: light dark; --maroon:#750016; --gold:#e4b63f; --surface:#fff; --text:#262626; --muted:#5b6470; --border:#d1d5db; --page:#f5f3ef; }
        @media (prefers-color-scheme: dark) { :root { --surface:#1f2937; --text:#f9fafb; --muted:#cbd5e1; --border:#4b5563; --page:#111827; } }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:20px; background:var(--page); color:var(--text); font:16px/1.5 system-ui,sans-serif; }
        main { width:min(100%,500px); padding:clamp(22px,5vw,36px); border:1px solid var(--border); border-top:5px solid var(--gold); border-radius:16px; background:var(--surface); box-shadow:0 14px 38px #0002; }
        h1 { margin:0 0 8px; color:var(--maroon); font-size:1.65rem; }
        p { color:var(--muted); }
        label { display:block; margin:18px 0 6px; font-weight:700; }
        input { width:100%; min-height:46px; padding:10px 12px; border:1px solid var(--border); border-radius:8px; background:var(--surface); color:var(--text); font:inherit; }
        input:focus,button:focus-visible,a:focus-visible { outline:3px solid var(--gold); outline-offset:2px; }
        button { min-height:44px; margin-top:16px; padding:10px 16px; border:0; border-radius:8px; background:var(--maroon); color:white; font:inherit; font-weight:700; cursor:pointer; }
        .error { margin:14px 0; padding:12px; border:1px solid #dc7b85; border-radius:8px; background:#8f1523; color:white; }
        .links { display:flex; flex-wrap:wrap; gap:16px; margin-top:20px; }
        a { color:var(--maroon); font-weight:650; }
        @media (prefers-color-scheme:dark) { a,h1 { color:#f2c94c; } }
    </style>
</head>
<body>
<main>
    <h1>{{ __('Verify your identity') }}</h1>
    <p>{{ __('Answer your personal security question to continue signing in.') }}</p>
    @if($errors->any())
        <div class="error" role="alert">{{ __('The answer could not be verified. Check it and try again, or use account recovery.') }}</div>
    @endif
    <form method="POST" action="{{ route('login.personal-security.verify') }}" data-loading-label="{{ __('Verifying...') }}">
        @csrf
        <label for="security_answer">{{ $question }}</label>
        <input id="security_answer" name="security_answer" type="password" autocomplete="off" required autofocus>
        @error('security_answer')<p class="error">{{ $message }}</p>@enderror
        <label style="display:flex;align-items:center;gap:8px;font-weight:500;">
            <input type="checkbox" data-toggle-password="security_answer" style="width:18px;min-height:18px;">
            {{ __('Show answer') }}
        </label>
        <button type="submit" data-loading-button>{{ __('Verify and continue') }}</button>
    </form>
    <div class="links">
        <a href="{{ route('login.personal-security.recovery') }}">{{ __('Can’t answer? Recover your account') }}</a>
        <form method="POST" action="{{ route('login.personal-security.cancel') }}" style="margin:0;">@csrf<button type="submit" style="margin:0;padding:0;min-height:0;background:transparent;color:var(--maroon);text-decoration:underline;font-weight:650;">{{ __('Cancel and return to sign in') }}</button></form>
    </div>
</main>
<script>
    document.querySelectorAll('[data-toggle-password]').forEach((toggle) => {
        toggle.addEventListener('change', () => {
            const input = document.getElementById(toggle.dataset.togglePassword);
            if (input) input.type = toggle.checked ? 'text' : 'password';
        });
    });
</script>
<script src="{{ asset('js/request-loading.js') }}?v={{ filemtime(public_path('js/request-loading.js')) }}"></script>
</body>
</html>
