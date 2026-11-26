@extends('layouts.admin')

@section('title', 'Audit Detail')
@section('page-title', 'Audit Detail')
@section('page-sub', 'Review the recorded action and values captured at the time it occurred.')

@section('styles')
<style>
    .audit-detail-page { width:min(1040px,100%); margin:0 auto; }
    .audit-detail-card { overflow:hidden; border:1px solid var(--border); border-radius:14px; box-shadow:var(--shadow); }
    .audit-detail-header { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; }
    .audit-detail-header h2 { display:flex; align-items:center; gap:9px; color:var(--primary); }
    .audit-detail-header p { margin-top:4px; color:var(--text-muted); font-size:.8rem; }
    .audit-detail-grid { gap:0; overflow:hidden; border:1px solid var(--border); border-radius:10px; }
    .audit-detail-grid .detail-item { min-width:0; padding:14px; border-bottom:1px solid var(--border); }
    .audit-detail-grid .detail-value { overflow-wrap:anywhere; }
    .audit-detail-section { margin-top:24px; }
    .audit-detail-section h2 { margin-bottom:10px; color:var(--primary); font-size:.95rem; }
    .audit-detail-section .table-wrap { overflow:auto; border:1px solid var(--border); border-radius:9px; }
    .audit-detail-section table { min-width:560px; }
    .audit-result-badge { display:inline-flex; align-items:center; padding:4px 9px; border:1px solid var(--border); border-radius:999px; background:var(--bg); color:var(--text-muted); font-size:.72rem; font-weight:750; }
    .audit-result-badge.is-success { border-color:rgba(46,125,50,.25); background:rgba(46,125,50,.09); color:var(--success); }
    .audit-result-badge.is-danger { border-color:rgba(198,40,40,.25); background:rgba(198,40,40,.08); color:var(--danger); }
    .audit-detail-description { margin-top:18px; padding:14px 16px; border:1px solid var(--border); border-radius:9px; background:var(--bg); }
    .audit-detail-description p { margin-top:5px; color:var(--text); line-height:1.55; overflow-wrap:anywhere; }
    @media(max-width:640px) {
        .audit-detail-header { align-items:flex-start; }
        .audit-detail-header .btn { width:100%; justify-content:center; }
        .audit-detail-grid .detail-item { padding:11px; }
    }
</style>
@endsection

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
    $resultBadgeClass = in_array(strtolower($auditLog->result), ['successful', 'success'], true)
        ? 'is-success'
        : (in_array(strtolower($auditLog->result), ['failed', 'failure'], true) ? 'is-danger' : '');
@endphp
<main class="audit-detail-page">
<div class="card audit-detail-card">
    <div class="card-header audit-detail-header">
        <div>
            <h2><x-icon class="fa-solid fa-clipboard-check" aria-hidden="true" /> Audit Detail</h2>
            <p>{{ __('Recorded administrator activity and captured changes.') }}</p>
        </div>
        <a href="{{ $auditLog->applicant_id ? route('admin.applicant-audit.show', ['applicant' => $auditLog->applicant_id, 'application_id' => $auditLog->application_id]) : route('admin.applicant-audit.index') }}" class="btn btn-outline btn-sm">Back</a>
    </div>
    <div class="card-body">
        @if(isset($queryError))
            <div class="alert alert-danger" role="alert">{{ $queryError }}</div>
        @endif
        <div class="detail-grid audit-detail-grid">
            <div class="detail-item"><div class="detail-label">Audit ID</div><div class="detail-value">{{ $auditLog->audit_code }}</div></div>
            <div class="detail-item"><div class="detail-label">Applicant</div><div class="detail-value">{{ $auditLog->applicant?->name ?? 'Unavailable' }}</div></div>
            <div class="detail-item"><div class="detail-label">Application ID</div><div class="detail-value">{{ $auditLog->application?->ref_id ?? ($auditLog->application_id ? '#'.$auditLog->application_id : '—') }}</div></div>
            <div class="detail-item"><div class="detail-label">Action</div><div class="detail-value">{{ $auditLog->action }}</div></div>
            <div class="detail-item"><div class="detail-label">Performed By</div><div class="detail-value">{{ $auditLog->actor_name }}</div></div>
            <div class="detail-item"><div class="detail-label">Role</div><div class="detail-value">{{ ucfirst($auditLog->actor_role) }} · {{ ucfirst($auditLog->actor_type) }}</div></div>
            <div class="detail-item"><div class="detail-label">Date &amp; Time</div><div class="detail-value">{{ $time }}</div></div>
            <div class="detail-item"><div class="detail-label">Module</div><div class="detail-value">{{ $auditLog->module }}</div></div>
            <div class="detail-item"><div class="detail-label">Result</div><div class="detail-value"><span class="audit-result-badge {{ $resultBadgeClass }}">{{ ucfirst($auditLog->result) }}</span></div></div>
            <div class="detail-item"><div class="detail-label">IP Address</div><div class="detail-value">{{ $auditLog->ip_address ?? 'Not recorded' }}</div></div>
        </div>
        @if($auditLog->description)
            <section class="audit-detail-description"><div class="detail-label">Description / Remarks</div><p>{{ $auditLog->description }}</p></section>
        @endif

        <section class="audit-detail-section">
            <h2>CHANGES MADE</h2>
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
        </section>

        <section class="audit-detail-section">
            <h2>Status History</h2>
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
        </section>
    </div>
</div>
</main>
@endsection
