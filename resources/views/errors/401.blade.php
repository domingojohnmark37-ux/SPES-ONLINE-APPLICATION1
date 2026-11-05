<x-guest-layout>
    <div class="text-center">
        <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">Error 401</p>
        <h1 class="mt-2 text-2xl font-semibold text-gray-900">Sign in required</h1>
        <p class="mt-3 text-sm text-gray-600">Please sign in to access this page.</p>
        <a href="{{ route('login') }}" class="mt-6 inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Go to sign in</a>
    </div>
</x-guest-layout>
