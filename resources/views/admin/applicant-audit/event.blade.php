@extends('layouts.admin')

@section('title', 'Audit Detail')
@section('page-title', 'Audit Detail')
@section('page-sub', 'Review the recorded action and values captured at the time it occurred.')

@section('content')
@php
    $statusLabel = fn (?string $status) => match ($status) {
        null, '' => 'Submitted',
        'approved' => 'Approved',
        'pending' => 'Pending',
        'denied' => 'Rejected',
        default => ucfirst($status),
    };
    $time = app(\App\Support\AdminDateFormatter::class)->format($auditLog->created_at, true);
@endphp
<div class="card">
    <div class="card-header">
        <h2><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> Audit Detail</h2>
        <a href="{{ $auditLog->applicant_id ? route('admin.applicant-audit.show', ['applicant' => $auditLog->applicant_id, 'application_id' => $auditLog->application_id]) : route('admin.applicant-audit.index') }}" class="btn btn-outline btn-sm">Back</a>
    </div>
    <div class="card-body">
        @if(isset($queryError))
            <div class="alert alert-danger" role="alert">{{ $queryError }}</div>
        @endif
        <div class="detail-grid">
            <div class="detail-item"><div class="detail-label">Audit ID</div><div class="detail-value">{{ $auditLog->audit_code }}</div></div>
            <div class="detail-item"><div class="detail-label">Applicant</div><div class="detail-value">{{ $auditLog->applicant?->name ?? 'Unavailable' }}</div></div>
            <div class="detail-item"><div class="detail-label">Application ID</div><div class="detail-value">{{ $auditLog->application?->ref_id ?? ($auditLog->application_id ? '#'.$auditLog->application_id : '—') }}</div></div>
            <div class="detail-item"><div class="detail-label">Action</div><div class="detail-value">{{ $auditLog->action }}</div></div>
            <div class="detail-item"><div class="detail-label">Performed By</div><div class="detail-value">{{ $auditLog->actor_name }}</div></div>
            <div class="detail-item"><div class="detail-label">Role</div><div class="detail-value">{{ ucfirst($auditLog->actor_role) }} · {{ ucfirst($auditLog->actor_type) }}</div></div>
            <div class="detail-item"><div class="detail-label">Date &amp; Time</div><div class="detail-value">{{ $time }}</div></div>
            <div class="detail-item"><div class="detail-label">Module</div><div class="detail-value">{{ $auditLog->module }}</div></div>
            <div class="detail-item"><div class="detail-label">Result</div><div class="detail-value">{{ ucfirst($auditLog->result) }}</div></div>
            <div class="detail-item"><div class="detail-label">IP Address</div><div class="detail-value">{{ $auditLog->ip_address ?? 'Not recorded' }}</div></div>
        </div>
        @if($auditLog->description)
            <div style="margin-top:18px;"><div class="detail-label">Description / Remarks</div><p style="margin-top:4px;line-height:1.55;">{{ $auditLog->description }}</p></div>
        @endif

        <div style="margin-top:24px;">
            <h2 style="margin-bottom:10px;color:var(--primary);font-size:1rem;">CHANGES MADE</h2>
            @if($auditLog->field_name)
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Field</th><th>Previous Value</th><th>New Value</th></tr></thead>
                        <tbody><tr><td>{{ str_replace('_', ' ', ucfirst($auditLog->field_name)) }}</td><td>{{ $auditLog->field_name === 'status' ? $statusLabel($auditLog->old_value) : ($auditLog->old_value ?? '—') }}</td><td>{{ $auditLog->field_name === 'status' ? $statusLabel($auditLog->new_value) : ($auditLog->new_value ?? '—') }}</td></tr></tbody>
                    </table>
                </div>
            @else
                <p style="color:var(--text-muted);">This action did not change a stored field.</p>
            @endif
        </div>

        <div style="margin-top:24px;">
            <h2 style="margin-bottom:10px;color:var(--primary);font-size:1rem;">Status History</h2>
            @if(isset($queryError))
                <p style="color:var(--danger);">{{ $queryError }}</p>
            @elseif($statusHistory->isNotEmpty())
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Previous</th><th>New Status</th><th>Changed By</th><th>Date &amp; Time</th><th>Remarks</th></tr></thead>
                        <tbody>
                            @foreach($statusHistory as $transition)
                                <tr>
                                    <td>{{ $statusLabel($transition->old_value) }}</td>
                                    <td>{{ $statusLabel($transition->new_value) }}</td>
                                    <td>{{ $transition->actor_name }} ({{ ucfirst($transition->actor_role) }})</td>
                                    <td>@adminDate($transition->created_at, true)</td>
                                    <td>{{ $transition->description ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p style="color:var(--text-muted);">No status transitions are recorded for this application.</p>
            @endif
        </div>
    </div>
</div>
@endsection
