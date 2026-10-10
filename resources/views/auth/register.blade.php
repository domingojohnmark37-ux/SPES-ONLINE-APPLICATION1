<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'PESO LAL-LO') }} - Register</title>
    <link rel="stylesheet" href="{{ asset('css/request-loading.css') }}?v={{ filemtime(public_path('css/request-loading.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}">
</head>
<body>
    <div class="auth-container">
        <div class="auth-wrapper">
            <div class="auth-left">
                <div class="logo-container">
                    <img src="{{ asset('images/peso_lallo.jpg') }}" alt="PESO LAL-LO Logo" class="logo" />
                </div>
                <h1>PESO <span>Lal-lo</span></h1>
                <p class="subtitle">Special Program for Employment of Students (SPES)</p>
                <p>Apply online and stay connected with PESO Lal-lo.</p>
                <div class="auth-features">
                    <div><svg class="icon" aria-hidden="true" viewBox="0 0 24 24" focusable="false" width="1em" height="1em"><path d="M9 4h6M9 2h6v4H9zM6 4H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-1M7 12l2 2 4-4M7 17h8" /></svg><span>Easy<br>Application</span></div>
                    <div><svg class="icon" aria-hidden="true" viewBox="0 0 24 24" focusable="false" width="1em" height="1em"><path d="M4 20V10h4v10H4zm6 0V4h4v16h-4zm6 0v-7h4v7h-4z" /></svg><span>Program<br>Updates</span></div>
                    <div><svg class="icon" aria-hidden="true" viewBox="0 0 24 24" focusable="false" width="1em" height="1em"><path d="M12 3 4 6v5c0 5.2 3.4 8.8 8 10 4.6-1.2 8-4.8 8-10V6l-8-3zm0 5a2 2 0 0 1 2 2v1h1v5H9v-5h1v-1a2 2 0 0 1 2-2zm0 1a1 1 0 0 0-1 1v1h2v-1a1 1 0 0 0-1-1z" /></svg><span>Secure &amp;<br>Reliable</span></div>
                </div>
            </div>

            <div class="auth-right">
                <h2>Create <span>Account</span></h2>
                <p class="subtitle">Join us and start your employment journey</p>
                @if (session('status'))
                    <p class="success-message" role="status">{{ session('status') }}</p>
                @endif

                <form method="POST" action="{{ route('register') }}" data-auth-form="register" data-loading-label="Creating account..." data-loading-message="Creating your account request... Please wait while we prepare your email verification." data-network-error="We couldn't reach the server. Check your connection and try again. If your signup was received, we'll check for an existing verification request before suggesting another submission." data-server-error="We couldn't create your account request right now. Please try again." data-validation-error="Please review the information and try again." data-success-message="Your signup request was accepted. Follow the next steps to verify your email." data-recovery-url="{{ route('registration.verify') }}" data-recovery-success-url="{{ route('registration.verify') }}">
                    @csrf

                    <!-- Email Address -->
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input 
                            id="email" 
                            type="email" 
                            name="email" 
                            value="{{ old('email') }}"
                            placeholder="Enter your email address"
                            required 
                            autofocus
                            autocomplete="email"
                        />
                        @error('email')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input 
                            id="password" 
                            type="password" 
                            name="password" 
                            placeholder="Create a password"
                            required 
                            minlength="12"
                            aria-describedby="password-guidance"
                            autocomplete="new-password"
                        />
                        @error('password')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label for="password_confirmation">Confirm Password</label>
                        <input 
                            id="password_confirmation" 
                            type="password" 
                            name="password_confirmation" 
                            placeholder="Confirm your password"
                            required 
                            minlength="12"
                            autocomplete="new-password"
                        />
                        @error('password_confirmation')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="registration-terms">
                        <p>Read the <a href="{{ route('terms') }}" target="_blank" rel="noopener">Terms and Conditions</a> before continuing.</p>
                        <div class="checkbox-group agreement-group">
                            <input
                                type="checkbox"
                                id="terms_accepted"
                                name="terms_accepted"
                                value="1"
                                required
                                @checked(old('terms_accepted'))
                                aria-describedby="terms-hint terms-error"
                            />
                            <label for="terms_accepted">I have read, understood, and agree to the Terms and Conditions.</label>
                        </div>
                        <p id="terms-error" class="error-message" role="alert" @if (!$errors->has('terms_accepted')) hidden @endif>
                            @error('terms_accepted')
                                {{ $message }}
                            @else
                                Please agree to the Terms and Conditions before continuing.
                            @enderror
                        </p>
                    </div>

                    <!-- Register Button -->
                    <button type="submit" class="btn-submit" data-auth-submit @disabled(!old('terms_accepted'))>
                        <span class="auth-spinner" data-auth-spinner aria-hidden="true" hidden></span>
                        <span data-auth-button-label>Sign Up</span>
                    </button>
                    <p class="auth-request-status" data-auth-request-status role="status" aria-live="polite" hidden></p>

                    <!-- Login Link -->
                    <div class="auth-footer">
                        Already have an account? 
                        <a href="{{ route('login') }}">Log In here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/request-loading.js') }}?v={{ filemtime(public_path('js/request-loading.js')) }}"></script>
    <script src="{{ asset('js/auth.js') }}?v={{ filemtime(public_path('js/auth.js')) }}"></script>
</body>
</html>
