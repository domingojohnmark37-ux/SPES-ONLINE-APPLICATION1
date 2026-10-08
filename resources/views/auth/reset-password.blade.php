<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        Choose a new password for <strong>{{ $email }}</strong>. We will email you a code to confirm the change.
    </div>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <x-input-error :messages="$errors->get('email')" class="mt-2" />

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Send confirmation code') }}
            </x-primary-button>
        </div>
    </form>

    <p class="mt-4 text-sm text-gray-600">
        <a href="{{ route('password.request') }}">{{ __('Use a different email') }}</a>
    </p>
</x-guest-layout>
