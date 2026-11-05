<x-guest-layout>
    <div class="text-center">
        <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">Error 503</p>
        <h1 class="mt-2 text-2xl font-semibold text-gray-900">Temporarily unavailable</h1>
        <p class="mt-3 text-sm text-gray-600">The service is temporarily unavailable. Please try again shortly.</p>
        <a href="{{ route('login') }}" class="mt-6 inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Return to sign in</a>
    </div>
</x-guest-layout>
