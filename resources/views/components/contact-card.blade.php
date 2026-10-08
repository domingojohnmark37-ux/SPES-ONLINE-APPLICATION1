@props(['title', 'actionLabel' => null, 'actionUrl' => null, 'external' => false])

<article class="contact-card">
    <div class="contact-card__icon" aria-hidden="true">{{ $icon }}</div>
    <div class="contact-card__content">
        <h3 class="text-primary-line">{{ $title }}</h3>
        <div class="contact-card__details">{{ $slot }}</div>
    </div>
    @if($actionLabel && $actionUrl)
        <a class="portal-button portal-button--secondary" href="{{ $actionUrl }}" @if($external) target="_blank" rel="noopener noreferrer" @endif>
            {{ $actionLabel }}
        </a>
    @elseif($actionLabel)
        <button class="portal-button portal-button--disabled" type="button" disabled>{{ $actionLabel }}</button>
    @endif
</article>
