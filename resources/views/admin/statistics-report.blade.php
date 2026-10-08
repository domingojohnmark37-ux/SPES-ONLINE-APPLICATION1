@extends('layouts.admin')

@section('title', 'Statistics Report')
@section('page-title', 'Statistics Report')
@section('page-sub', 'View application statistics, applicant demographics, and application period performance.')

@section('styles')
<style>
    .report-shell { display:grid; gap:18px; }
    .report-heading { display:flex; align-items:flex-end; justify-content:space-between; gap:18px; }
    .report-heading h2 { color:var(--primary); font-size:1.35rem; }
    .report-heading p { margin-top:5px; color:var(--text-muted); font-size:.84rem; }
    .report-filters { display:grid; grid-template-columns:repeat(5,minmax(140px,1fr)) auto; gap:10px; padding:14px; border:1px solid var(--border); border-radius:12px; background:var(--white); box-shadow:var(--shadow); }
    .report-filter { display:grid; gap:5px; min-width:0; }
    .report-filter label { color:var(--text-muted); font-size:.7rem; font-weight:700; }
    .report-filter select,.report-filter input { width:100%; min-height:38px; padding:8px 10px; border:1px solid var(--border); border-radius:7px; background:#fff; color:var(--text); font:inherit; font-size:.8rem; }
    .report-filter-actions { display:flex; align-items:flex-end; gap:7px; }
    .date-filter-fields { display:contents; }
    [hidden] { display:none !important; }
    .filter-note { grid-column:1/-1; color:var(--text-muted); font-size:.73rem; }
    .report-error { margin:0; padding:10px 13px; border-radius:8px; background:#ffebee; color:#b42318; font-size:.82rem; }
    .report-grid { display:grid; gap:16px; }
    .summary-cards { grid-template-columns:repeat(6,minmax(0,1fr)); }
    .report-stat { display:flex; min-width:0; align-items:flex-start; gap:12px; padding:16px; border:1px solid var(--border); border-radius:12px; background:var(--white); box-shadow:var(--shadow); }
    .report-stat-icon { display:grid; width:40px; height:40px; flex:0 0 40px; place-items:center; border-radius:50%; background:#fde9e9; color:var(--primary); font-size:1rem; }
    .report-stat-copy { min-width:0; }
    .report-stat-label { color:var(--text-muted); font-size:.73rem; font-weight:650; line-height:1.35; }
    .report-stat-value { margin-top:4px; color:var(--primary); font-size:1.55rem; font-weight:800; line-height:1.1; }
    .report-stat-change { margin-top:7px; font-size:.69rem; line-height:1.4; }
    .change-up { color:#16834a; }
    .change-down { color:#bb3039; }
    .change-neutral { color:var(--text-muted); }
    .analytics-grid { grid-template-columns:minmax(0,1.9fr) minmax(235px,.85fr); align-items:stretch; }
    .report-card { min-width:0; overflow:hidden; border:1px solid var(--border); border-radius:12px; background:var(--white); box-shadow:var(--shadow); }
    .report-card-header { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:15px 18px; border-bottom:1px solid var(--border); }
    .report-card-header h2 { color:var(--primary); font-size:.96rem; font-weight:750; }
    .report-card-header p { margin-top:3px; color:var(--text-muted); font-size:.71rem; }
    .report-card-body { padding:16px 18px; }
    .chart-legend { display:flex; flex-wrap:wrap; gap:10px 16px; margin-bottom:10px; }
    .legend-item { display:inline-flex; align-items:center; gap:6px; color:#5f6670; font-size:.7rem; }
    .legend-dot { width:9px; height:9px; border-radius:50%; background:var(--legend-color); }
    .activity-chart { display:block; width:100%; height:auto; overflow:visible; }
    .activity-chart text { fill:#69717c; font-family:inherit; font-size:11px; }
    .activity-chart .grid-line { stroke:#e8eaed; stroke-width:1; }
    .activity-chart .axis-line { stroke:#d8dce1; stroke-width:1; }
    .chart-empty { display:grid; min-height:205px; place-items:center; color:var(--text-muted); font-size:.84rem; text-align:center; }
    .insights-list { display:grid; gap:0; }
    .insight-item { display:flex; gap:9px; padding:10px 0; border-bottom:1px solid #f0f1f3; }
    .insight-item:last-child { border-bottom:0; }
    .insight-item i { margin-top:2px; color:var(--primary); font-size:.8rem; }
    .insight-item strong { display:block; color:var(--text); font-size:.72rem; }
    .insight-item span { display:block; margin-top:3px; color:var(--text-muted); font-size:.72rem; line-height:1.4; }
    .insight-empty { color:var(--text-muted); font-size:.8rem; line-height:1.5; }
    .lower-grid { grid-template-columns:minmax(0,1fr) minmax(0,1.15fr); align-items:stretch; }
    .demographic-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); }
    .demographic-panel { min-width:0; padding:14px; border-right:1px solid #eceef0; border-bottom:1px solid #eceef0; }
    .demographic-panel:nth-child(2n) { border-right:0; }
    .demographic-panel:nth-last-child(-n+2) { border-bottom:0; }
    .demographic-panel h3 { margin-bottom:12px; color:var(--primary); font-size:.78rem; }
    .demographic-content { display:flex; align-items:center; gap:12px; }
    .donut { position:relative; display:grid; width:88px; height:88px; flex:0 0 88px; place-items:center; border-radius:50%; }
    .donut::after { position:absolute; width:52px; height:52px; border-radius:50%; background:var(--white); content:""; }
    .donut span { z-index:1; color:var(--primary); font-size:.75rem; font-weight:800; }
    .demographic-legend { display:grid; width:100%; gap:5px; min-width:0; }
    .demographic-legend-item { display:grid; grid-template-columns:8px minmax(0,1fr) auto; align-items:center; gap:5px; color:var(--text-muted); font-size:.66rem; }
    .demographic-legend-item i { width:7px; height:7px; border-radius:50%; background:var(--legend-color); }
    .demographic-legend-item span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .demographic-legend-item strong { color:var(--text); font-size:.65rem; white-space:nowrap; }
    .demographic-empty { color:var(--text-muted); font-size:.77rem; line-height:1.45; }
    .data-availability { margin-top:12px; padding:9px 11px; border-radius:7px; background:#f7f8fa; color:var(--text-muted); font-size:.7rem; line-height:1.45; }
    .comparison-chart { display:flex; height:188px; align-items:flex-end; gap:10px; overflow-x:auto; padding:0 2px 22px; border-bottom:1px solid var(--border); }
    .comparison-column { display:flex; width:54px; height:100%; flex:0 0 54px; flex-direction:column; justify-content:flex-end; align-items:center; gap:5px; }
    .comparison-bar { display:flex; width:33px; min-height:0; flex-direction:column-reverse; justify-content:flex-start; overflow:hidden; border-radius:5px 5px 0 0; background:#f1f2f4; }
    .comparison-segment { width:100%; min-height:0; background:var(--segment-color); }
    .comparison-year { color:var(--text-muted); font-size:.65rem; white-space:nowrap; }
    .comparison-legend { display:flex; flex-wrap:wrap; gap:8px 12px; margin:0 0 12px; }
    .comparison-table-wrap { overflow-x:auto; margin-top:13px; }
    .comparison-table { width:100%; border-collapse:collapse; font-size:.7rem; text-align:left; }
    .comparison-table th { padding:8px 6px; color:var(--text-muted); font-size:.63rem; white-space:nowrap; }
    .comparison-table td { padding:8px 6px; border-top:1px solid #eff0f2; white-space:nowrap; }
    .comparison-empty { display:grid; min-height:140px; place-items:center; color:var(--text-muted); font-size:.8rem; text-align:center; }
    .bottom-grid { grid-template-columns:minmax(0,1.3fr) minmax(0,1fr) minmax(0,1fr); align-items:stretch; }
    .info-copy { color:#535c67; font-size:.78rem; line-height:1.65; }
    .explanation-list { display:grid; gap:9px; }
    .explanation-row { display:grid; grid-template-columns:105px 1fr; gap:10px; color:var(--text-muted); font-size:.68rem; line-height:1.45; }
    .explanation-row strong { color:var(--text); font-size:.69rem; }
    .report-empty { padding:12px 14px; border-left:4px solid #d4a72c; border-radius:7px; background:#fff8e1; color:#66520c; font-size:.8rem; line-height:1.5; }
    @media(max-width:1250px) {
        .summary-cards { grid-template-columns:repeat(3,minmax(0,1fr)); }
        .report-filters { grid-template-columns:repeat(3,minmax(140px,1fr)); }
        .bottom-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .bottom-grid .report-card:first-child { grid-column:1/-1; }
    }
    @media(max-width:900px) {
        .analytics-grid,.lower-grid { grid-template-columns:minmax(0,1fr); }
        .report-heading { align-items:flex-start; flex-direction:column; }
    }
    @media(max-width:600px) {
        .summary-cards { grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
        .report-stat { gap:9px; padding:12px 10px; }
        .report-stat-icon { width:34px; height:34px; flex-basis:34px; font-size:.85rem; }
        .report-stat-value { font-size:1.3rem; }
        .report-filters { grid-template-columns:repeat(2,minmax(0,1fr)); padding:11px; }
        .report-filter:nth-child(1),.report-filter:nth-child(2) { grid-column:1/-1; }
        .report-filter-actions { grid-column:1/-1; }
        .date-filter-fields { display:contents; }
        .date-filter-fields .report-filter { grid-column:span 1; }
        .filter-note { grid-column:1/-1; }
        .report-card-body { padding:13px; }
        .demographic-grid { grid-template-columns:minmax(0,1fr); }
        .demographic-panel,.demographic-panel:nth-child(2n),.demographic-panel:nth-last-child(-n+2) { border-right:0; border-bottom:1px solid #eceef0; }
        .demographic-panel:last-child { border-bottom:0; }
        .bottom-grid { grid-template-columns:minmax(0,1fr); }
        .bottom-grid .report-card:first-child { grid-column:auto; }
        .explanation-row { grid-template-columns:95px 1fr; }
    }
</style>
@endsection

@section('content')
@php
    $changeText = function (string $key, bool $isRate = false) use ($comparison): string {
        if (!$comparison['available']) {
            return 'No previous-period baseline';
        }
        $change = $comparison['changes'][$key] ?? null;
        if (!$change || $change['difference'] === null) {
            return 'No previous-period baseline';
        }
        if ($isRate) {
            if (($comparison['counts']['total'] ?? 0) === 0) {
                return 'No previous-period baseline';
            }
            $direction = $change['difference'] > 0 ? '+' : '';
            return $direction.number_format($change['difference'], 1).' percentage points vs. previous period';
        }
        if ($change['baseline'] === 0) {
            return 'No previous-period baseline';
        }
        $direction = $change['percentage'] > 0 ? '+' : '';
        return $direction.number_format($change['percentage'], 1).'% vs. previous period';
    };
    $chartWidth = 1000;
    $chartHeight = 270;
    $chartLeft = 52;
    $chartRight = 986;
    $chartTop = 18;
    $chartBottom = 220;
    $chartValues = collect($chart['labels'])->pluck('total');
    $chartMaximum = max(1, (int) $chartValues->max());
    $chartSteps = 4;
    $chartCount = count($chart['labels']);
    $chartX = fn (int $index): float => $chartCount < 2
        ? ($chartLeft + $chartRight) / 2
        : $chartLeft + $index * (($chartRight - $chartLeft) / ($chartCount - 1));
    $chartY = fn (int $value): float => $chartBottom - ($value / $chartMaximum) * ($chartBottom - $chartTop);
    $seriesColors = ['approved' => '#b4232f', 'pending' => '#ec8f34', 'denied' => '#df7180'];
    $seriesPaths = [];
    foreach ($seriesColors as $seriesName => $seriesColor) {
        $points = [];
        foreach ($chart['labels'] as $index => $period) {
            $points[] = number_format($chartX($index), 1, '.', '').','.number_format($chartY($period[$seriesName]), 1, '.', '');
        }
        $seriesPaths[$seriesName] = implode(' ', $points);
    }
    $donutPalette = ['#8b0000', '#bb3039', '#e18a8e', '#d4a72c', '#6b7280', '#276749', '#5576a8', '#9365a8'];
    $periodMaximum = max(1, (int) $periods->max('total'));
@endphp

<main class="report-shell">
    <div class="report-heading">
        <div>
            <h2>Statistics Report</h2>
            <p>View application statistics, applicant demographics, and application period performance.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="report-error" role="alert">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form class="report-filters" method="GET" action="{{ route('admin.statistics-report') }}" aria-label="Statistics report filters">
        <div class="report-filter">
            <label for="filter-period">Application Period</label>
            <select id="filter-period" name="period">
                @foreach($periodOptions as $value => $label)
                    <option value="{{ $value }}" @selected($filters['period'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="report-filter">
            <label for="filter-date-range">Date Range</label>
            <select id="filter-date-range" name="date_range">
                <option value="all" @selected($filters['date_range'] === 'all')>All dates</option>
                <option value="last30" @selected($filters['date_range'] === 'last30')>Last 30 days</option>
                <option value="last90" @selected($filters['date_range'] === 'last90')>Last 90 days</option>
                <option value="last12" @selected($filters['date_range'] === 'last12')>Last 12 months</option>
                <option value="custom" @selected($filters['date_range'] === 'custom')>Custom range</option>
            </select>
        </div>
        <div class="date-filter-fields" id="custom-date-fields" @if($filters['date_range'] !== 'custom') hidden @endif>
            <div class="report-filter">
                <label for="filter-date-from">From</label>
                <input id="filter-date-from" name="date_from" type="date" value="{{ $filters['date_from'] }}">
            </div>
            <div class="report-filter">
                <label for="filter-date-to">To</label>
                <input id="filter-date-to" name="date_to" type="date" value="{{ $filters['date_to'] }}">
            </div>
        </div>
        <div class="report-filter">
            <label for="filter-barangay">Barangay</label>
            <select id="filter-barangay" name="barangay">
                <option value="">All barangays</option>
                @foreach($barangayOptions as $barangay)
                    <option value="{{ $barangay }}" @selected($filters['barangay'] === $barangay)>{{ $barangay }}</option>
                @endforeach
            </select>
        </div>
        <div class="report-filter">
            <label for="filter-status">Application Status</label>
            <select id="filter-status" name="status">
                <option value="">All statuses</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="report-filter-actions">
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i> Apply</button>
            <a class="btn btn-outline" href="{{ route('admin.statistics-report') }}">Reset</a>
        </div>
        @if(!$programYearAvailable)
            <p class="filter-note">Academic/program year filtering is unavailable because applications do not store a program-year field.</p>
        @endif
    </form>

    @if(!$hasData)
        <div class="report-empty" role="status">No application data is available for the selected filters.</div>
    @endif

    <section class="report-grid summary-cards" aria-label="Application summary statistics">
        @foreach([
            ['label' => 'Total Applications', 'value' => $total, 'icon' => 'fa-file-lines', 'key' => 'total', 'rate' => false, 'description' => ''],
            ['label' => 'Approved Applications', 'value' => $statusCounts['approved'], 'icon' => 'fa-circle-check', 'key' => 'approved', 'rate' => false, 'description' => ''],
            ['label' => 'Pending Review', 'value' => $statusCounts['pending'], 'icon' => 'fa-clock', 'key' => 'pending', 'rate' => false, 'description' => ''],
            ['label' => 'Rejected Applications', 'value' => $statusCounts['denied'], 'icon' => 'fa-circle-xmark', 'key' => 'denied', 'rate' => false, 'description' => ''],
            ['label' => 'Approval Rate', 'value' => $approvalRate, 'icon' => 'fa-chart-pie', 'key' => 'approval_rate', 'rate' => true, 'description' => ''],
            ['label' => 'Rejection Rate', 'value' => $rejectionRate, 'icon' => 'fa-chart-simple', 'key' => 'rejection_rate', 'rate' => true, 'description' => ''],
        ] as $stat)
            <article class="report-stat">
                <div class="report-stat-icon"><i class="fa-solid {{ $stat['icon'] }}" aria-hidden="true"></i></div>
                <div class="report-stat-copy">
                    <div class="report-stat-label">{{ $stat['label'] }}</div>
                    <div class="report-stat-value">
                        @if($stat['rate'])
                            {{ $stat['value'] === null ? '—' : number_format($stat['value'], 1).'%' }}
                        @else
                            {{ number_format($stat['value']) }}
                        @endif
                    </div>
                    <div class="report-stat-change {{ !$comparison['available'] || (($comparison['changes'][$stat['key']]['difference'] ?? 0) === 0) ? 'change-neutral' : ((($comparison['changes'][$stat['key']]['difference'] ?? 0) > 0) ? 'change-up' : 'change-down') }}">
                        {{ $changeText($stat['key'], $stat['rate']) }}
                    </div>
                </div>
            </article>
        @endforeach
    </section>

    <section class="report-grid analytics-grid" aria-label="Application activity and key insights">
        <article class="report-card">
            <header class="report-card-header">
                <div>
                    <h2><i class="fa-solid fa-chart-column" aria-hidden="true"></i> Application Statistics</h2>
                    <p>Applications grouped by actual submission date ({{ $chart['grouping'] === 'year' ? 'yearly' : 'monthly' }}).</p>
                </div>
            </header>
            <div class="report-card-body">
                @if(count($chart['labels']))
                    <div class="chart-legend" aria-label="Chart legend">
                        <span class="legend-item"><i class="legend-dot" style="--legend-color:#8b0000"></i>Total Applications</span>
                        <span class="legend-item"><i class="legend-dot" style="--legend-color:#b4232f"></i>Approved</span>
                        <span class="legend-item"><i class="legend-dot" style="--legend-color:#ec8f34"></i>Pending Review</span>
                        <span class="legend-item"><i class="legend-dot" style="--legend-color:#df7180"></i>Rejected</span>
                    </div>
                    <svg class="activity-chart" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" role="img" aria-label="Application counts by {{ $chart['grouping'] }}">
                        @for($step = 0; $step <= $chartSteps; $step++)
                            @php
                                $gridY = $chartBottom - ($step / $chartSteps) * ($chartBottom - $chartTop);
                                $gridValue = (int) round(($step / $chartSteps) * $chartMaximum);
                            @endphp
                            <line class="grid-line" x1="{{ $chartLeft }}" y1="{{ $gridY }}" x2="{{ $chartRight }}" y2="{{ $gridY }}"></line>
                            <text x="{{ $chartLeft - 9 }}" y="{{ $gridY + 4 }}" text-anchor="end">{{ $gridValue }}</text>
                        @endfor
                        <line class="axis-line" x1="{{ $chartLeft }}" y1="{{ $chartTop }}" x2="{{ $chartLeft }}" y2="{{ $chartBottom }}"></line>
                        <line class="axis-line" x1="{{ $chartLeft }}" y1="{{ $chartBottom }}" x2="{{ $chartRight }}" y2="{{ $chartBottom }}"></line>
                        @php
                            $barWidth = min(34, max(5, ($chartRight - $chartLeft) / max(1, $chartCount) * .45));
                        @endphp
                        @foreach($chart['labels'] as $index => $period)
                            @php
                                $x = $chartX($index);
                                $y = $chartY($period['total']);
                                $labelStep = max(1, (int) ceil($chartCount / 10));
                            @endphp
                            <rect x="{{ $x - $barWidth / 2 }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ max(0, $chartBottom - $y) }}" rx="3" fill="#8b0000" opacity=".82">
                                <title>{{ $period['label'] }}: {{ $period['total'] }} total, {{ $period['approved'] }} approved, {{ $period['pending'] }} pending, {{ $period['denied'] }} rejected</title>
                            </rect>
                            @if($index % $labelStep === 0 || $index === $chartCount - 1)
                                <text x="{{ $x }}" y="{{ $chartBottom + 20 }}" text-anchor="middle">{{ $chart['grouping'] === 'year' ? $period['label'] : \Carbon\Carbon::createFromFormat('M Y', $period['label'])->format('M y') }}</text>
                            @endif
                        @endforeach
                        @foreach($seriesColors as $seriesName => $seriesColor)
                            @if($chartCount > 1)
                                <polyline points="{{ $seriesPaths[$seriesName] }}" fill="none" stroke="{{ $seriesColor }}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"></polyline>
                            @endif
                            @foreach($chart['labels'] as $index => $period)
                                <circle cx="{{ $chartX($index) }}" cy="{{ $chartY($period[$seriesName]) }}" r="3.2" fill="{{ $seriesColor }}">
                                    <title>{{ $period['label'] }} {{ ucfirst($seriesName) }}: {{ $period[$seriesName] }}</title>
                                </circle>
                            @endforeach
                        @endforeach
                    </svg>
                @else
                    <div class="chart-empty">Application activity is not available for the selected filters.</div>
                @endif
            </div>
        </article>
        <article class="report-card">
            <header class="report-card-header"><h2><i class="fa-solid fa-lightbulb" aria-hidden="true"></i> Key Insights</h2></header>
            <div class="report-card-body">
                @if($insights)
                    <div class="insights-list">
                        @foreach($insights as $insight)
                            <div class="insight-item">
                                <i class="fa-solid fa-arrow-trend-up" aria-hidden="true"></i>
                                <div><strong>{{ $insight['label'] }}</strong><span>{{ $insight['value'] }}</span></div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="insight-empty">Insights will appear when application records are available for the selected filters.</p>
                @endif
                <p class="data-availability">Municipality is not stored on applications; geographic statistics use the recorded barangay field. School data is not stored.</p>
            </div>
        </article>
    </section>

    <section class="report-grid lower-grid" aria-label="Applicant demographics and annual application comparison">
        <article class="report-card">
            <header class="report-card-header"><div><h2><i class="fa-solid fa-users" aria-hidden="true"></i> Applicant Demographics</h2><p>Age reflects the application record; other values use the selected records.</p></div></header>
            <div class="demographic-grid">
                @foreach([
                    'age' => 'Age Groups',
                    'sex' => 'Sex',
                    'barangay' => 'Barangay',
                    'education' => 'Education',
                ] as $dimension => $title)
                    @php
                        $distribution = $demographics[$dimension];
                        $gradientStops = [];
                        $offset = 0;
                        foreach ($distribution['items'] as $itemIndex => $item) {
                            $color = $donutPalette[$itemIndex % count($donutPalette)];
                            $nextOffset = $offset + $item['percentage'];
                            $gradientStops[] = $color.' '.$offset.'% '.$nextOffset.'%';
                            $offset = $nextOffset;
                        }
                    @endphp
                    <section class="demographic-panel">
                        <h3>{{ $title }}</h3>
                        @if($distribution['available'])
                            <div class="demographic-content">
                                <div class="donut" role="img" aria-label="{{ $title }} distribution" style="background:conic-gradient({{ implode(', ', $gradientStops) }});">
                                    <span>{{ number_format(collect($distribution['items'])->sum('count')) }}</span>
                                </div>
                                <div class="demographic-legend">
                                    @foreach($distribution['items'] as $itemIndex => $item)
                                        <div class="demographic-legend-item">
                                            <i style="--legend-color:{{ $donutPalette[$itemIndex % count($donutPalette)] }}"></i>
                                            <span title="{{ $item['label'] }}">{{ $item['label'] }}</span>
                                            <strong>{{ number_format($item['percentage'], 1) }}% · {{ $item['count'] }}</strong>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <p class="demographic-empty">Data not available.</p>
                        @endif
                    </section>
                @endforeach
            </div>
        </article>

        <article class="report-card">
            <header class="report-card-header">
                <div><h2><i class="fa-solid fa-chart-bar" aria-hidden="true"></i> Application Period Comparison</h2><p>Grouped by the actual application submission year. No named application-period field is stored.</p></div>
            </header>
            <div class="report-card-body">
                @if($periods->isNotEmpty())
                    <div class="comparison-legend">
                        <span class="legend-item"><i class="legend-dot" style="--legend-color:#8b0000"></i>Approved</span>
                        <span class="legend-item"><i class="legend-dot" style="--legend-color:#e18a2c"></i>Pending</span>
                        <span class="legend-item"><i class="legend-dot" style="--legend-color:#df7180"></i>Rejected</span>
                        @if($periods->sum('other') > 0)
                            <span class="legend-item"><i class="legend-dot" style="--legend-color:#6b7280"></i>Other</span>
                        @endif
                    </div>
                    <div class="comparison-chart" role="img" aria-label="Annual application totals grouped by status">
                        @foreach($periods as $year)
                            @php
                                $barHeight = $year['total'] / $periodMaximum * 155;
                            @endphp
                            <div class="comparison-column" title="{{ $year['period'] }}: {{ $year['total'] }} applications ({{ $year['approved'] }} approved, {{ $year['pending'] }} pending, {{ $year['denied'] }} rejected)">
                                <span style="color:var(--text);font-size:.63rem;">{{ $year['total'] }}</span>
                                <div class="comparison-bar" style="height:{{ $barHeight }}px;max-height:155px;">
                                    @foreach([
                                        ['value' => $year['approved'], 'color' => '#8b0000'],
                                        ['value' => $year['pending'], 'color' => '#e18a2c'],
                                        ['value' => $year['denied'], 'color' => '#df7180'],
                                        ['value' => $year['other'], 'color' => '#6b7280'],
                                    ] as $segment)
                                        @if($segment['value'] > 0)
                                            <span class="comparison-segment" style="--segment-color:{{ $segment['color'] }};height:{{ $segment['value'] / $periodMaximum * 155 }}px;"></span>
                                        @endif
                                    @endforeach
                                </div>
                                <span class="comparison-year">{{ $year['period'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="comparison-table-wrap">
                        <table class="comparison-table">
                            <thead><tr><th>Year</th><th>Total</th><th>Approved</th><th>Pending</th><th>Rejected</th><th>Approval</th><th>Rejection</th></tr></thead>
                            <tbody>
                                @foreach($periods as $year)
                                    <tr>
                                        <td>{{ $year['period'] }}</td>
                                        <td>{{ number_format($year['total']) }}</td>
                                        <td>{{ number_format($year['approved']) }}</td>
                                        <td>{{ number_format($year['pending']) }}</td>
                                        <td>{{ number_format($year['denied']) }}</td>
                                        <td>{{ $year['approval_rate'] === null ? '—' : number_format($year['approval_rate'], 1).'%' }}</td>
                                        <td>{{ $year['rejection_rate'] === null ? '—' : number_format($year['rejection_rate'], 1).'%' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="comparison-empty">No historical application data is available for comparison.</div>
                @endif
            </div>
        </article>
    </section>

    <section class="report-grid bottom-grid" aria-label="Report summary, explanation, and conclusion">
        <article class="report-card">
            <header class="report-card-header"><h2><i class="fa-solid fa-align-left" aria-hidden="true"></i> Summary</h2></header>
            <div class="report-card-body"><p class="info-copy">{{ $summary }}</p>
                @if(!$comparison['available'])
                    <p class="data-availability">Insufficient historical data is available for period comparison.</p>
                @else
                    <p class="data-availability">Previous comparison window: {{ $comparison['start']->format('M j, Y') }} – {{ $comparison['end']->format('M j, Y') }}. Current-window change is calculated from these database records.</p>
                @endif
            </div>
        </article>
        <article class="report-card">
            <header class="report-card-header"><h2><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Statistics Explanation</h2></header>
            <div class="report-card-body">
                <div class="explanation-list">
                    <div class="explanation-row"><strong>Total Applications</strong><span>Application records matching the selected filters and submission date range.</span></div>
                    <div class="explanation-row"><strong>Approved</strong><span>Records with the database status “approved”.</span></div>
                    <div class="explanation-row"><strong>Pending Review</strong><span>Records with the database status “pending”.</span></div>
                    <div class="explanation-row"><strong>Rejected</strong><span>Records with the database status “denied”.</span></div>
                    <div class="explanation-row"><strong>Approval Rate</strong><span>Approved records divided by filtered total records, multiplied by 100.</span></div>
                    <div class="explanation-row"><strong>Rejection Rate</strong><span>Denied records divided by filtered total records, multiplied by 100.</span></div>
                    <div class="explanation-row"><strong>Pending Rate</strong><span>Pending records divided by filtered total records, multiplied by 100 ({{ $pendingRate === null ? 'Data not available' : number_format($pendingRate, 1).'%' }}).</span></div>
                    @if($otherStatusCount > 0)
                        <div class="explanation-row"><strong>Other status</strong><span>{{ number_format($otherStatusCount) }} records have a status outside the three statuses defined by the current application schema.</span></div>
                    @endif
                </div>
            </div>
        </article>
        <article class="report-card">
            <header class="report-card-header"><h2><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> Conclusion</h2></header>
            <div class="report-card-body"><p class="info-copy">{{ $conclusion }}</p></div>
        </article>
    </section>
</main>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const range = document.getElementById('filter-date-range');
        const dates = document.getElementById('custom-date-fields');
        range?.addEventListener('change', function () {
            dates.hidden = range.value !== 'custom';
        });
    });
</script>
@endsection
