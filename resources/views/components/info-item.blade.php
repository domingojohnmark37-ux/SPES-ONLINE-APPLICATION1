@props(['description' => null])

<div {{ $attributes->class(['info-item']) }}>
    <div class="text-primary-line">{{ $slot }}</div>
    @if(filled($description))
        <p class="text-secondary">{{ $description }}</p>
    @endif
</div>
