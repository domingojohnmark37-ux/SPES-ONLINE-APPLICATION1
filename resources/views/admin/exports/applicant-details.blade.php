<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color:#212121; font-size:10px; }
        h1 { margin:0 0 5px; color:#8b0000; font-size:19px; }
        h2 { margin:18px 0 7px; color:#8b0000; font-size:13px; }
        p { margin:3px 0; color:#555; }
        table { width:100%; border-collapse:collapse; table-layout:fixed; }
        th, td { padding:7px; border:1px solid #d5d8dc; text-align:left; vertical-align:top; overflow-wrap:anywhere; }
        th { width:31%; background:#f0f2f5; }
        .notice { margin-top:18px; color:#555; font-size:9px; }
    </style>
</head>
<body>
    <h1>SPES Applicant Information</h1>
    <p><strong>{{ $application->full_name }}</strong></p>
    <p>Reference: {{ $application->ref_id ?: '—' }} · Status: {{ ucfirst($application->status) }}</p>
    <h2>Application Information</h2>
    <table>
        <tbody>
            @forelse($fields as $field)
                <tr><th>{{ $field['label'] }}</th><td>{{ $field['value'] }}</td></tr>
            @empty
                <tr><td colspan="2">No application information was recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="notice">Submitted documents are included separately in this applicant's folder.</p>
</body>
</html>
