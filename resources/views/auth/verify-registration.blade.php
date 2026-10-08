<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verify your email — {{ config('app.name', 'SPES') }}</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
    <main class="auth-container">
        <section class="auth-right" style="max-width:480px;margin:40px auto;">
            <h1>Enter verification code</h1>
            <p class="subtitle">If this email is eligible, a 6-digit code has been sent to <strong>{{ $email }}</strong>. The code expires in 10 minutes.</p>

            @if (session('status'))
                <p class="success-message" role="status">{{ session('status') }}</p>
            @endif

            <form method="POST" action="{{ route('registration.verify.submit') }}">
                @csrf
                <div class="form-group">
                    <label for="pin">6-digit verification code</label>
                    <input
                        id="pin"
                        name="pin"
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        required
                        autofocus
                        aria-describedby="pin-error"
                    >
                    @error('pin')
                        <span id="pin-error" class="error-message" role="alert">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit" class="btn-submit">Verify email</button>
            </form>

            <form method="POST" action="{{ route('registration.verify.resend') }}" style="margin-top:18px;">
                @csrf
                <button id="resend-code" type="submit" class="btn-submit" @disabled($resendsRemaining === 0 || $resendAvailableAt > now()->timestamp)>
                    Resend code
                </button>
                <p id="resend-help" class="subtitle" aria-live="polite">
                    @if($resendsRemaining === 0)
                        No resends remaining.
                    @else
                        {{ $resendsRemaining }} resend{{ $resendsRemaining === 1 ? '' : 's' }} remaining.
                    @endif
                </p>
            </form>

            <p class="auth-footer"><a href="{{ route('register') }}">Return to sign up</a></p>
        </section>
    </main>
    @if($resendsRemaining > 0 && $resendAvailableAt > now()->timestamp)
        <script>
            const resendButton = document.getElementById('resend-code');
            const resendHelp = document.getElementById('resend-help');
            const remainingResends = @json($resendsRemaining);
            const resendAvailableAt = @json($resendAvailableAt);
            const updateResendButton = () => {
                const seconds = Math.max(0, resendAvailableAt - Math.floor(Date.now() / 1000));
                resendButton.disabled = seconds > 0;
                resendHelp.textContent = seconds > 0
                    ? `Please wait ${seconds} seconds before resending. ${remainingResends} resend${remainingResends === 1 ? '' : 's'} remaining.`
                    : `${remainingResends} resend${remainingResends === 1 ? '' : 's'} remaining.`;
                if (seconds > 0) window.setTimeout(updateResendButton, 1000);
            };
            updateResendButton();
        </script>
    @endif
</body>
</html>
