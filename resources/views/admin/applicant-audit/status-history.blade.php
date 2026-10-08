@extends('layouts.admin')

@section('title', 'Applicant Status History')
@section('page-title', 'Applicant Status History')
@section('page-sub', 'Recorded application status transitions.')

@section('content')
@php
    $statusLabel = fn (?string $status) => match ($status) {
        null, '' => 'Submitted',
        'approved' => 'Approved',
        'pending' => 'Pending',
        'denied' => 'Rejected',
        default => ucfirst($status),
    };
@endphp
<div class="card">
    <div class="card-header">
        <h2>{{ $applicant->name }}</h2>
        <a href="{{ route('admin.applicant-audit.show', ['applicant' => $applicant->id, 'application_id' => request('application_id')]) }}" class="btn btn-outline btn-sm">Back to Audit</a>
    </div>
    <div class="card-body">
        @if(isset($queryError))
            <div class="alert alert-danger" role="alert">{{ $queryError }}</div>
        @elseif($history->isEmpty())
            <p style="padding:25px;color:var(--text-muted);text-align:center;">No status transitions are recorded.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Previous Status</th><th>New Status</th><th>Changed By</th><th>Date &amp; Time</th><th>Remarks</th><th>Event</th></tr></thead>
                    <tbody>
                        @foreach($history as $event)
                            <tr>
                                <td>{{ $statusLabel($event->old_value) }}</td>
                                <td>{{ $statusLabel($event->new_value) }}</td>
                                <td>{{ $event->actor_name }} ({{ ucfirst($event->actor_role) }})</td>
                                <td>@adminDate($event->created_at, true)</td>
                                <td>{{ $event->description ?? '—' }}</td>
                                <td><a href="{{ route('admin.applicant-audit.event', $event) }}" class="btn btn-outline btn-sm">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $history->links() }}
        @endif
    </div>
</div>
@endsection
