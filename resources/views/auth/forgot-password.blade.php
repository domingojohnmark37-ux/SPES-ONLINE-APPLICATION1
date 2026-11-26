<x-guest-layout>
    <section class="password-recovery-content" aria-labelledby="password-recovery-title">
        <a class="password-recovery-brand" href="{{ url('/') }}" aria-label="{{ __('SPES home') }}">
            <img src="{{ asset('images/peso_lallo.jpg') }}" alt="">
            <span><strong>SPES</strong><small>PESO LAL-LO</small></span>
        </a>

        <header class="password-recovery-heading">
            <h1 id="password-recovery-title">{{ __('Forgot Password?') }}</h1>
            <p>{{ __('Enter the email address for your account. You will continue to choose a new password, then confirm the change with a code sent to your email.') }}</p>
        </header>

        <x-auth-session-status class="password-recovery-status" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="password-recovery-form">
            @csrf
            <div class="password-recovery-field">
                <x-input-label for="email" :value="__('Email Address')" />
                <x-text-input
                    id="email"
                    class="password-recovery-input"
                    type="email"
                    name="email"
                    :value="old('email')"
                    :placeholder="__('Enter your email address')"
                    required
                    autofocus
                    autocomplete="email"
                />
                <x-input-error :messages="$errors->get('email')" class="password-recovery-error" />
            </div>

            <button type="submit" class="password-recovery-submit">
                {{ __('Continue') }}
            </button>
        </form>

        <a class="password-recovery-back" href="{{ route('login') }}">
            <x-icon class="fa-solid fa-arrow-left" aria-hidden="true" />
            {{ __('Back to Login') }}
        </a>
    </section>
</x-guest-layout>
