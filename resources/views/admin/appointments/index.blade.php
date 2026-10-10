@extends('layouts.admin')

@section('title', 'Appointments')
@section('page-title', 'Appointments')
@section('page-sub', 'Create and publish shared SPES schedules for applicants')

@section('content')
<div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <h2><x-icon class="fa-solid fa-calendar-check" /> Shared Schedule</h2>
        <a href="{{ route('admin.appointments.create') }}" class="btn btn-primary btn-sm">
            <x-icon class="fa-solid fa-plus" /> New Appointment
        </a>
    </div>
    <div class="card-body">
        @if($appointments->isEmpty())
            <p style="padding:30px 0;color:var(--text-muted);text-align:center;">No appointments have been created.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Appointment</th>
                            <th>Date and time</th>
                            <th>Location</th>
                            <th>Audience</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($appointments as $appointment)
                            <tr>
                                <td>
                                    <strong>{{ $appointment->title }}</strong>
                                    @if($appointment->description)
                                        <div style="margin-top:3px;color:var(--text-muted);font-size:.78rem;">{{ \Illuminate\Support\Str::limit($appointment->description, 90) }}</div>
                                    @endif
                                </td>
                                <td>@adminDate($appointment->starts_at, true)</td>
                                <td>{{ $appointment->location ?: '—' }}</td>
                                <td>
                                    @if($appointment->target_audience === 'specific_applicant')
                                        {{ $appointment->targetUser?->name ?? 'Selected applicant removed' }}
                                    @elseif($appointment->target_audience === 'multiple_applicants')
                                        {{ $appointment->targetApplicants->count() }} selected applicants
                                    @else
                                        {{ match($appointment->target_audience) {
                                            'approved_applicants' => 'Approved applicants',
                                            'pending_applicants' => 'Pending applicants',
                                            'denied_applicants' => 'Denied applicants',
                                            default => 'All applicants',
                                        } }}
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $appointment->is_published ? 'badge-approved' : 'badge-pending' }}">
                                        {{ $appointment->is_published ? 'Published' : 'Draft' }}
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex;flex-wrap:wrap;gap:6px;">
                                        <a href="{{ route('admin.appointments.edit', $appointment) }}" class="btn btn-info btn-sm">Edit</a>
                                        <form method="POST" action="{{ route('admin.appointments.toggle', $appointment) }}">
                                            @csrf
                                            <button class="btn {{ $appointment->is_published ? 'btn-danger' : 'btn-success' }} btn-sm" type="submit">
                                                {{ $appointment->is_published ? 'Unpublish' : 'Publish' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.appointments.destroy', $appointment) }}" onsubmit="return confirm('Delete this appointment?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="margin-top:20px;display:flex;justify-content:center;">{{ $appointments->links() }}</div>
        @endif
    </div>
</div>
@endsection
