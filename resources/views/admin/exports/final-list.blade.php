<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color:#212121; font-size:10px; }
        h1 { margin:0 0 5px; color:#8b0000; font-size:20px; }
        p { margin:3px 0 14px; color:#555; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:7px 6px; border:1px solid #d5d8dc; text-align:left; }
        th { background:#f0f2f5; color:#333; font-size:9px; text-transform:uppercase; }
        .count { margin:12px 0; font-weight:bold; }
    </style>
</head>
<body>
    <h1>{{ $name }}</h1>
    <p>SPES Batch {{ $batchYear }} · Generated {{ $generatedAt->format('M j, Y g:i A') }}</p>
    <p>
        Barangay: {{ $filters['barangay'] ?: 'All' }} ·
        SPES Type: {{ ($filters['spes_status'] ?? null) === 'new' ? 'New' : ((($filters['spes_status'] ?? null) === 'baby') ? 'SPES Baby' : 'All') }}
    </p>
    <div class="count">Approved applicants: {{ count($applicants) }}</div>
    <table>
        <thead>
            <tr>
                <th>#</th><th>Reference</th><th>Full Name</th><th>Barangay</th><th>SPES Type</th><th>Age</th><th>Contact</th>
            </tr>
        </thead>
        <tbody>
            @forelse($applicants as $index => $applicant)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $applicant['reference'] ?: '—' }}</td>
                    <td>{{ $applicant['name'] ?: '—' }}</td>
                    <td>{{ $applicant['barangay'] ?: '—' }}</td>
                    <td>{{ $applicant['spes_type'] }}</td>
                    <td>{{ $applicant['age'] ?: '—' }}</td>
                    <td>{{ $applicant['contact'] ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No approved applicants matched the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
