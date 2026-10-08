<article class="upcoming-item">
    <time class="date-tile" datetime="{{ $appointment->starts_at->toIso8601String() }}">
        <span>{{ $appointment->starts_at->locale(app()->getLocale())->translatedFormat('M') }}</span>
        <strong>{{ $appointment->starts_at->format('d') }}</strong>
        <span>{{ $appointment->starts_at->format('Y') }}</span>
    </time>
    <div class="upcoming-copy">
        <div class="upcoming-title-row">
            <h3>{{ $appointment->title }}</h3>
            @if($isNext ?? false)
                <span class="next-badge">{{ __('Next up') }}</span>
            @endif
        </div>
        <p><i class="fa-regular fa-clock" aria-hidden="true"></i> {{ $appointment->starts_at->format('g:i A') }}</p>
        @if($appointment->location)
            <p><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ $appointment->location }}</p>
        @endif
        @if(($showDescription ?? false) && filled($appointment->description))
            <p class="appointment-description">{{ $appointment->description }}</p>
        @endif
    </div>
</article>
