<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @php($isPasswordRecovery = request()->routeIs('password.request'))
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/alerts.js'])
        @if ($isPasswordRecovery)
            <link rel="stylesheet" href="{{ asset('css/password-recovery.css') }}?v={{ filemtime(public_path('css/password-recovery.css')) }}">
        @endif
    </head>
    <body class="font-sans text-gray-900 antialiased {{ $isPasswordRecovery ? 'password-recovery-page' : '' }}">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 {{ $isPasswordRecovery ? 'password-recovery-layout' : '' }}">
            <div class="{{ $isPasswordRecovery ? 'password-recovery-default-logo' : '' }}">
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg {{ $isPasswordRecovery ? 'password-recovery-card' : '' }}">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
