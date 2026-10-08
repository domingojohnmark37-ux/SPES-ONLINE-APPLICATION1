@props(['title', 'description', 'id' => null])

<header class="section-heading">
    <h2 @if($id) id="{{ $id }}" @endif class="text-section">{{ $title }}</h2>
    @if($description)
        <p class="text-secondary">{{ $description }}</p>
    @endif
</header>
