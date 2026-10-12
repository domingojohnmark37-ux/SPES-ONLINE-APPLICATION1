<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 portrait; margin: 0.72in 0.72in 0.78in; }
        body { font-family: Arial, sans-serif; font-size: 10pt; line-height: 1.38; color: #252525; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #c9c9c9; padding: 6px 7px; vertical-align: top; overflow-wrap: break-word; }
        th { background: #f2f2f2; font-weight: bold; text-align: left; }
        .letterhead { border: 0; margin: 0 0 10px; }
        .letterhead td { border: 0; padding: 0; vertical-align: middle; }
        .letterhead .logo-cell { width: 13%; text-align: center; }
        .letterhead .office-copy { width: 74%; text-align: center; font-size: 8.5pt; line-height: 1.2; }
        .office-copy div { margin-bottom: 2px; }
        .spes-logo { width: 44px; height: 54px; }
        .peso-logo { width: 50px; height: 50px; }
        .letterhead-rule { border-top: 2px solid #6f0016; border-bottom: 1px solid #d4a72c; height: 2px; margin: 8px 0 12px; }
        h1 { margin: 0 0 4px; color: #6f0016; font-size: 17pt; line-height: 1.15; text-align: center; }
        .document-subtitle { margin: 0 0 14px; color: #555; font-size: 10pt; text-align: center; }
        h2 { margin: 15px 0 7px; padding-bottom: 4px; border-bottom: 1px solid #d4a72c; color: #6f0016; font-size: 11.5pt; page-break-after: avoid; }
        h3 { margin: 10px 0 5px; color: #6f0016; font-size: 10.5pt; page-break-after: avoid; }
        p { margin: 4px 0 7px; }
        .report-information { margin: 0 0 12px; }
        .report-information .label { width: 18%; background: #f5f5f5; font-weight: bold; }
        .report-information .value { width: 32%; }
        .summary-box { padding: 9px 11px; border-left: 4px solid #d4a72c; background: #f7f7f7; }
        .section-intro { color: #4d4d4d; }
        .dimension { margin: 0 0 13px; page-break-inside: avoid; }
        .chart-table { margin: 5px 0 7px; font-size: 8.5pt; }
        .chart-table th, .chart-table td { border: 0; padding: 3px 5px; background: transparent; vertical-align: middle; }
        .chart-table th { color: #555; font-size: 8pt; }
        .chart-table .category { width: 31%; }
        .chart-table .visual { width: 49%; }
        .chart-table .value { width: 20%; text-align: right; white-space: nowrap; }
        .bar-track { height: 9px; margin: 0; background: #e7e7e7; border: 0; }
        .bar-track td { height: 9px; padding: 0; border: 0; }
        .bar-fill { height: 9px; background: #780019; }
        .bar-fill-highlight { background: #c49a26; }
        .distribution-table { margin: 4px 0 3px; font-size: 8.5pt; }
        .distribution-table th:nth-child(1) { width: 27%; }
        .distribution-table th:nth-child(2) { width: 13%; }
        .distribution-table th:nth-child(3) { width: 15%; }
        .distribution-table th:nth-child(4) { width: 45%; }
        .distribution-table tr:nth-child(even) td { background: #f8f8f8; }
        .distribution-table td:nth-child(2), .distribution-table td:nth-child(3) { text-align: right; white-space: nowrap; }
        .distribution-table .total td { background: #ececec; font-weight: bold; }
        .rounding-note { margin: 3px 0 8px; color: #555; font-size: 8pt; font-style: italic; }
        .key-findings li, .recommendations li, .quality-notes li { margin: 0 0 5px; }
        .key-findings, .recommendations, .quality-notes { margin: 4px 0 8px; padding-left: 19px; }
        .signature-table { margin-top: 6px; page-break-inside: avoid; }
        .signature-table th { color: #6f0016; }
        .signature-space { height: 82px; }
        .muted { color: #555; font-size: 8.5pt; }
        .no-data { padding: 9px 11px; border: 1px solid #d4a72c; background: #fffaf0; }
        .source-note { margin-top: 5px; color: #555; font-size: 8pt; }
        .section-block { page-break-inside: auto; }
    </style>
</head>
<body>
    <table class="letterhead">
        <tr>
            <td class="logo-cell"><img class="spes-logo" src="{{ public_path('images/spes-demographic-report-right.jpeg') }}" alt="SPES logo"></td>
            <td class="office-copy">
                <div>REPUBLIC OF THE PHILIPPINES</div>
                <div><strong>DEPARTMENT OF LABOR AND EMPLOYMENT</strong></div>
                <div>REGIONAL OFFICE NO. 02</div>
                <div><strong>PUBLIC EMPLOYMENT SERVICE OFFICE</strong></div>
                <div><strong><em>Lal-lo, Cagayan</em></strong></div>
                <div>City/Municipality/Province</div>
                <div><strong>SPECIAL PROGRAM FOR EMPLOYMENT OF STUDENTS (SPES)</strong></div>
                <div>(RA 7323, as amended by RAs 9547 and 10917)</div>
            </td>
            <td class="logo-cell"><img class="peso-logo" src="{{ public_path('images/spes-demographic-report-left.gif') }}" alt="PESO logo"></td>
        </tr>
    </table>
    <div class="letterhead-rule"></div>

    <h1>APPLICANT DEMOGRAPHICS STATISTICAL REPORT</h1>
    <p class="document-subtitle">{{ $data['office'] }} &nbsp;|&nbsp; {{ $data['program'] }}</p>

    <section class="section-block">
        <h2>A. REPORT INFORMATION</h2>
        <table class="report-information">
            <tr>
                <td class="label">Reporting period</td>
                <td class="value">{{ \Carbon\Carbon::parse($data['start_date'])->format('F j, Y') }} – {{ \Carbon\Carbon::parse($data['end_date'])->format('F j, Y') }}</td>
                <td class="label">Program year</td>
                <td class="value">{{ $data['program_year'] }}</td>
            </tr>
            <tr>
                <td class="label">Valid applicants</td>
                <td class="value">{{ number_format($data['total_valid']) }} unique approved applicants</td>
                <td class="label">Generated</td>
                <td class="value">{{ $data['generated_at'] }}</td>
            </tr>
            <tr>
                <td class="label">Data source</td>
                <td class="value">{{ $data['source'] }}</td>
                <td class="label">Requested by</td>
                <td class="value">{{ $data['requested_by'] ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">Date basis</td>
                <td class="value">{{ $data['date_basis'] }}</td>
                <td class="label">Office</td>
                <td class="value">PESO Lal-lo, Cagayan</td>
            </tr>
        </table>
        <p class="source-note">{{ $data['program_year_note'] }}</p>
    </section>

    <section class="section-block">
        <h2>B. EXECUTIVE SUMMARY</h2>
        @if($data['total_valid'] > 0)
            <p class="summary-box">{{ $data['summary'] }}</p>
        @else
            <p class="no-data">{{ $data['summary'] }}</p>
        @endif
    </section>

    <section class="section-block">
        <h2>C. APPLICANT DEMOGRAPHIC DISTRIBUTION</h2>
        <p class="section-intro">Counts and percentages use {{ number_format($data['total_valid']) }} unique approved applicants as the denominator. Percentages are rounded to one decimal place.</p>
        @foreach([
            ['title' => 'Age group', 'indicator' => 'Age group', 'key' => 'age'],
            ['title' => 'Sex (as recorded)', 'indicator' => 'Sex category', 'key' => 'sex'],
            ['title' => 'Civil status', 'indicator' => 'Civil status', 'key' => 'civil_status'],
            ['title' => 'Barangay / residence', 'indicator' => 'Barangay / residence', 'key' => 'barangay'],
            ['title' => 'Educational level', 'indicator' => 'Educational level', 'key' => 'education'],
            ['title' => 'Year level', 'indicator' => 'Year level', 'key' => 'year_level'],
        ] as $dimension)
            <div class="dimension">
                <h3>{{ $dimension['title'] }}</h3>
                @include('admin.reports.demographic-chart', [
                    'distribution' => $data['demographics'][$dimension['key']],
                ])
                @include('admin.reports.demographic-table', [
                    'indicator' => $dimension['indicator'],
                    'distribution' => $data['demographics'][$dimension['key']],
                ])
            </div>
        @endforeach
    </section>

    <section class="section-block">
        <h2>D. KEY FINDINGS AND STATISTICAL INTERPRETATION</h2>
        <ul class="key-findings">
            @foreach($data['key_findings'] as $finding)
                <li>{{ $finding }}</li>
            @endforeach
        </ul>
        <p>Findings describe the approved applicant records available for this reporting period. They do not establish causes, eligibility patterns, unmet demand, or employment outcomes.</p>
        <p><strong>Comparable-period context:</strong>
            The equivalent preceding period was
            {{ \Carbon\Carbon::parse($data['comparison']['start_date'])->format('F j, Y') }} to
            {{ \Carbon\Carbon::parse($data['comparison']['end_date'])->format('F j, Y') }},
            with {{ number_format($data['comparison']['total_valid']) }} unique approved applicants.
            @if($data['comparison']['percentage_change'] !== null)
                This period differs by {{ $data['comparison']['difference'] > 0 ? '+' : '' }}{{ number_format($data['comparison']['difference']) }}
                applicants ({{ number_format($data['comparison']['percentage_change'], 1) }}%) using the same date and duplicate rules.
            @else
                A percentage change is not calculated because the preceding-period baseline is zero.
            @endif
        </p>
    </section>

    <section class="section-block">
        <h2>E. RECOMMENDATIONS AND PROGRAM IMPLICATIONS</h2>
        <ul class="recommendations">
            @foreach($data['recommendations'] as $recommendation)
                <li>{{ $recommendation }}</li>
            @endforeach
        </ul>
        <p>Recommendations are planning considerations based on the recorded distribution only. Validate outreach coverage and local context before making operational decisions.</p>
    </section>

    <section class="section-block">
        <h2>F. CONCLUSION</h2>
        <p>{{ $data['conclusion'] }}</p>
    </section>

    <section class="section-block">
        <h2>G. DATA QUALITY AND REPORTING NOTES</h2>
        @php
            $missingDescription = $data['missing'] === []
                ? 'None identified in the analyzed records.'
                : implode('; ', array_map(fn ($label, $count) => "{$label}: {$count}", array_keys($data['missing']), $data['missing']));
        @endphp
        <ul class="quality-notes">
            <li>Total valid applicants analyzed: {{ number_format($data['total_valid']) }} unique approved applications.</li>
            <li>Approved applications in period before duplicate removal: {{ number_format($data['approved_in_period']) }}.</li>
            <li>Excluded within the selected period: {{ number_format($data['not_approved']) }} not approved and {{ number_format($data['duplicates_removed']) }} duplicate approved records.</li>
            <li>Applications outside selected dates: {{ number_format($data['outside_date_range']) }}.</li>
            <li>Missing demographic values: {{ $missingDescription }}</li>
            <li>Duplicate check: normalized full name and date of birth; a duplicate is removed only when both values are present and match.</li>
            <li>Age group uses the age value stored in the application record; age is not recalculated from date of birth.</li>
            <li>Application date uses the system submission timestamp (applications.created_at). Program year is selected by the report requester because no program-year field is stored on applications.</li>
            <li>Percentages use the total valid approved applicant count and are rounded to one decimal place. When the denominator is zero, categories show 0.0%.</li>
            <li>Comparable period uses the same number of calendar days immediately before the selected reporting period; counts use the same approval and duplicate rules.</li>
        </ul>
    </section>

    <section class="section-block">
        <h2>H. PREPARED AND REVIEWED BY</h2>
        <table class="signature-table">
            <thead><tr><th>Prepared by:</th><th>Reviewed / noted by:</th></tr></thead>
            <tbody>
                <tr>
                    <td class="signature-space">Name and signature: ___________________________<br><br>Position: ___________________________<br><br>Date: ___________________________</td>
                    <td class="signature-space">Name and signature: ___________________________<br><br>Position: ___________________________<br><br>Date: ___________________________</td>
                </tr>
            </tbody>
        </table>
    </section>
</body>
</html>
