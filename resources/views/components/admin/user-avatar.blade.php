@props(['user', 'class' => 'user-avatar'])

@php
    $initials = collect(explode(' ', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');
@endphp

@if($user->profile_photo_url)
    <img class="{{ $class }}" src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}">
@else
    <div class="{{ $class }}" aria-hidden="true">{{ $initials ?: '?' }}</div>
@endif