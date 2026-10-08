<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'PESO LAL-LO') }} - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
    <div class="auth-container">
        <div class="auth-wrapper">
            <div class="auth-left">
                <div class="logo-container">
                    <img src="{{ asset('images/peso_lallo.jpg') }}" alt="PESO LAL-LO Logo" class="logo">
                </div>
                <h1>PESO <span>Lal-lo</span></h1>
                <p class="subtitle">Special Program for Employment of Students (SPES)</p>
                <p>Apply online and stay connected with PESO Lal-lo.</p>
                <div class="auth-features">
                    <div><i aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M9 4h6M9 2h6v4H9zM6 4H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-1M7 12l2 2 4-4M7 17h8" /></svg></i><span>Easy<br>Application</span></div>
                    <div><i aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M4 20V10h4v10H4zm6 0V4h4v16h-4zm6 0v-7h4v7h-4z" /></svg></i><span>Program<br>Updates</span></div>
                    <div><i aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M12 3 4 6v5c0 5.2 3.4 8.8 8 10 4.6-1.2 8-4.8 8-10V6l-8-3zm0 5a2 2 0 0 1 2 2v1h1v5H9v-5h1v-1a2 2 0 0 1 2-2zm0 1a1 1 0 0 0-1 1v1h2v-1a1 1 0 0 0-1-1z" /></svg></i><span>Secure &amp;<br>Reliable</span></div>
                </div>
            </div>

            <div class="auth-right">
                <a href="{{ url('/') }}" class="auth-back-link">
                    <i class="fa-solid fa-angle-left" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="m15 5-7 7 7 7" /></svg></i>
                    Back to Welcome Page
                </a>
                <h2>Welcome <span>Back!</span></h2>
                <p class="subtitle">Please log in to your account.</p>

                <!-- Session Status -->
                @if ($errors->any())
                    <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                        <strong>Login Failed!</strong>
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                @if (session('status'))
                    <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- Email Address -->
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input 
                            id="email" 
                            type="email" 
                            name="email" 
                            value="{{ old('email') }}"
                            placeholder="Email Address"
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
                            placeholder="Password"
                            required 
                            autocomplete="current-password"
                        />
                        @error('password')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Remember Me -->
                    <div class="checkbox-group">
                        <input 
                            type="checkbox" 
                            id="remember_me" 
                            name="remember"
                        />
                        <label for="remember_me">Remember me</label>
                    </div>

                    <!-- Forgot Password Link -->
                    @if (Route::has('password.request'))
                        <div class="forgot-password">
                            <a href="{{ route('password.request') }}">Forgot Password?</a>
                        </div>
                    @endif

                    <!-- Login Button -->
                    <button type="submit" class="btn-submit">Login</button>

                    <!-- Sign Up Link -->
                    <div class="auth-footer">
                        Don't have an account? 
                        <a href="{{ route('register') }}">Sign Up here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/auth.js') }}"></script>
</body>
</html>
