@php
    $highlightedIndex = collect($distribution['items'])
        ->filter(fn ($item) => $item['count'] > 0)
        ->sortByDesc('count')
        ->keys()
        ->first();
@endphp
@if($distribution['items'] === [])
    <p class="muted">No approved applicants are available to chart for this category.</p>
@else
    <table class="chart-table" aria-label="Applicant category share chart">
        <thead>
            <tr>
                <th class="category">Category</th>
                <th class="visual">Share of valid applicants</th>
                <th class="value">Applicants · share</th>
            </tr>
        </thead>
        <tbody>
            @foreach($distribution['items'] as $index => $item)
                <tr>
                    <td class="category">{{ $item['label'] }}</td>
                    <td class="visual">
                        <table class="bar-track" role="presentation">
                            <tr>
                                <td class="{{ $index === $highlightedIndex ? 'bar-fill-highlight' : 'bar-fill' }}" style="width: {{ min(100, max(0, $item['percentage'])) }}%"></td>
                                <td style="width: {{ 100 - min(100, max(0, $item['percentage'])) }}%"></td>
                            </tr>
                        </table>
                    </td>
                    <td class="value">{{ number_format($item['count']) }} · {{ number_format($item['percentage'], 1) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
