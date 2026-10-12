<table class="distribution-table">
    <colgroup>
        <col style="width: 26%">
        <col style="width: 13%">
        <col style="width: 15%">
        <col style="width: 46%">
    </colgroup>
    <thead>
        <tr>
            <th>{{ $indicator }}</th>
            <th>Applicants (n)</th>
            <th>Share (%)</th>
            <th>Interpretation</th>
        </tr>
    </thead>
    <tbody>
        @forelse($distribution['items'] as $item)
            <tr>
                <td>{{ $item['label'] }}</td>
                <td>{{ number_format($item['count']) }}</td>
                <td>{{ number_format($item['percentage'], 1) }}</td>
                <td>{{ $item['interpretation'] }}</td>
            </tr>
        @empty
            <tr><td colspan="4">No approved applicants are available for this category.</td></tr>
        @endforelse
        <tr class="total">
            <td>TOTAL</td>
            <td>{{ $distribution['total'] }}</td>
            <td>{{ $distribution['total'] > 0 ? '100.0*' : '0.0*' }}</td>
            <td>All valid applicants accounted for.</td>
        </tr>
    </tbody>
</table>
<p class="note">*Total may differ slightly from 100% because of rounding.</p>
@php
    $largest = collect($distribution['items'])
        ->reject(fn ($item) => in_array($item['label'], ['Not provided', 'Outside age groups'], true))
        ->sortByDesc('count')
        ->first();
@endphp
<p class="interpretation">
    @if($largest)
        {{ $largest['label'] }} had the highest count with {{ $largest['count'] }} applicants ({{ number_format($largest['percentage'], 1) }}%).
    @else
        No approved applicants were recorded in this category for the selected period.
    @endif
</p>
