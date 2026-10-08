@extends('layouts.admin')

@section('title', 'Applicant Audit')
@section('page-title', 'Applicant Audit')
@section('page-sub', 'View and track the history of applicant activities and changes made to their records.')

@section('styles')
<style>
    .audit-page { display:grid; gap:16px; }
    .audit-filter-card { display:grid; grid-template-columns:minmax(190px,1.4fr) repeat(5,minmax(125px,1fr)) auto; gap:10px; align-items:end; padding:14px; border:1px solid var(--border); border-radius:12px; background:var(--white); box-shadow:var(--shadow); }
    .audit-filter { display:grid; min-width:0; gap:5px; }
    .audit-filter label { color:var(--text-muted); font-size:.7rem; font-weight:700; }
    .audit-filter input,.audit-filter select { width:100%; min-height:38px; padding:8px 10px; border:1px solid var(--border); border-radius:7px; background:#fff; color:var(--text); font:inherit; font-size:.8rem; }
    .audit-filter-actions { display:flex; align-items:center; gap:7px; }
    .audit-custom-dates { display:contents; }
    [hidden] { display:none !important; }
    .audit-filter-error { grid-column:1/-1; color:var(--danger); font-size:.78rem; }
    .audit-cards { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:12px; }
    .audit-stat { display:flex; min-width:0; gap:11px; padding:15px; border:1px solid var(--border); border-radius:12px; background:#fff; box-shadow:var(--shadow); }
    .audit-stat-icon { display:grid; width:38px; height:38px; flex:0 0 38px; place-items:center; border-radius:50%; background:#fde9e9; color:var(--primary); }
    .audit-stat strong { display:block; color:var(--primary); font-size:1.35rem; line-height:1.15; }
    .audit-stat span { display:block; margin-top:3px; color:var(--text-muted); font-size:.69rem; line-height:1.35; }
    .audit-content { display:grid; grid-template-columns:minmax(0,1.45fr) minmax(300px,.9fr); gap:16px; align-items:start; }
    .audit-card { min-width:0; overflow:hidden; border:1px solid var(--border); border-radius:12px; background:#fff; box-shadow:var(--shadow); }
    .audit-card-header { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:15px 17px; border-bottom:1px solid var(--border); }
    .audit-card-header h2 { color:var(--primary); font-size:.96rem; }
    .audit-card-body { padding:0; }
    .audit-table-wrap { overflow-x:auto; }
    .audit-table { width:100%; border-collapse:collapse; font-size:.77rem; text-align:left; }
    .audit-table th { padding:11px 12px; background:#f7f8fa; color:var(--text-muted); font-size:.66rem; text-transform:uppercase; white-space:nowrap; }
    .audit-table td { padding:12px; border-top:1px solid #eef0f2; color:#515966; vertical-align:middle; }
    .audit-table tbody tr.selected td { background:#fff8e1; }
    .audit-applicant { color:var(--text); font-weight:700; text-decoration:none; }
    .audit-applicant:hover { color:var(--primary); text-decoration:underline; }
    .audit-subtext { display:block; margin-top:3px; color:var(--text-muted); font-size:.68rem; }
    .audit-badge { display:inline-flex; align-items:center; padding:4px 8px; border-radius:999px; font-size:.67rem; font-weight:700; white-space:nowrap; }
    .audit-badge.approved { background:#e8f5e9; color:#256c32; }
    .audit-badge.pending { background:#fff4bf; color:#725600; }
    .audit-badge.rejected { background:#ffebee; color:#b4232f; }
    .audit-badge.neutral { background:#eef0f3; color:#59636f; }
    .audit-table-footer { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:12px 15px; border-top:1px solid var(--border); color:var(--text-muted); font-size:.72rem; }
    .audit-pagination { display:flex; gap:5px; align-items:center; }
    .audit-pagination a,.audit-pagination span { padding:5px 8px; border:1px solid var(--border); border-radius:5px; color:var(--text); text-decoration:none; }
    .audit-pagination .active { border-color:var(--primary); background:var(--primary); color:white; }
    .audit-empty { padding:30px 18px; color:var(--text-muted); font-size:.82rem; text-align:center; }
    .audit-details { min-height:330px; }
    .audit-details .audit-card-body { padding:16px; }
    .detail-top { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
    .detail-top h3 { color:var(--text); font-size:1rem; }
    .detail-meta { display:grid; grid-template-columns:1fr 1fr; gap:9px; margin-top:15px; }
    .detail-meta div { padding:9px; border-radius:7px; background:#f7f8fa; }
    .detail-meta span { display:block; color:var(--text-muted); font-size:.65rem; }
    .detail-meta strong { display:block; margin-top:3px; overflow-wrap:anywhere; color:var(--text); font-size:.74rem; }
    .audit-timeline { position:relative; display:grid; gap:0; margin-top:17px; }
    .timeline-entry { position:relative; display:grid; grid-template-columns:18px 1fr; gap:9px; padding:0 0 17px; }
    .timeline-entry:not(:last-child)::before { position:absolute; top:15px; bottom:0; left:6px; width:1px; background:#e4e7eb; content:""; }
    .timeline-dot { z-index:1; width:13px; height:13px; margin-top:2px; border:3px solid #f4d9dc; border-radius:50%; background:var(--primary); }
    .timeline-copy a { color:var(--primary); font-size:.76rem; font-weight:700; text-decoration:none; }
    .timeline-copy time,.timeline-copy span { display:block; margin-top:3px; color:var(--text-muted); font-size:.68rem; line-height:1.4; }
    .timeline-copy p { margin-top:4px; color:#59636f; font-size:.7rem; line-height:1.45; }
    .detail-links { display:flex; flex-wrap:wrap; gap:8px; margin-top:13px; }
    .audit-query-error { padding:14px; border-left:4px solid var(--danger); border-radius:7px; background:#ffebee; color:#a5222a; font-size:.82rem; }
    .audit-info { margin:12px 15px; color:var(--text-muted); font-size:.7rem; line-height:1.5; }
    @media(max-width:1250px) {
        .audit-filter-card { grid-template-columns:repeat(3,minmax(140px,1fr)); }
        .audit-cards { grid-template-columns:repeat(3,minmax(0,1fr)); }
    }
    @media(max-width:950px) { .audit-content { grid-template-columns:minmax(0,1fr); } }
    @media(max-width:600px) {
        .audit-filter-card { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .audit-filter:first-child,.audit-filter:nth-child(2) { grid-column:1/-1; }
        .audit-custom-dates { display:contents; }
        .audit-custom-dates .audit-filter { grid-column:span 1; }
        .audit-filter-actions { grid-column:1/-1; }
        .audit-cards { grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
        .audit-stat { padding:11px 9px; gap:8px; }
        .audit-stat-icon { width:32px; height:32px; flex-basis:32px; }
        .audit-table { min-width:760px; }
        .audit-table-footer { align-items:flex-start; flex-direction:column; }
    }
</style>
@endsection

@section('content')
@php
    $statusClass = fn (?string $status) => match ($status) {
        'approved' => 'approved',
        'pending' => 'pending',
        'denied' => 'rejected',
        default => 'neutral',
    };
    $statusLabel = fn (?string $status) => match ($status) {
        'approved' => 'Approved',
        'pending' => 'Pending',
        'denied' => 'Rejected',
        default => 'No application',
    };
    $auditTime = fn ($date) => $date
        ? app(\App\Support\AdminDateFormatter::class)->format($date, true)
        : '—';
    $periodSettings = $periodSettings ?? null;
    $periodForApplication = function ($createdAt) use ($periodSettings): string {
        if (!$createdAt) return 'No application period';
        if (!$periodSettings?->application_start_date || !$periodSettings?->application_end_date) return 'Period not configured';
        return \Carbon\Carbon::parse($createdAt)->betweenIncluded(
            $periodSettings->application_start_date->copy()->startOfDay(),
            $periodSettings->application_end_date->copy()->endOfDay()
        )
            ? 'Configured application window' : 'Outside configured window';
    };
    $firstItem = $rows?->firstItem() ?? 0;
    $lastItem = $rows?->lastItem() ?? 0;
@endphp
<main class="audit-page">
    @if($queryError)
        <div class="audit-query-error" role="alert">{{ $queryError }}</div>
    @else
        <form class="audit-filter-card" method="GET" action="{{ route('admin.applicant-audit.index') }}">
            <div class="audit-filter">
                <label for="audit-search">Search Applicant</label>
                <input id="audit-search" type="search" name="search" value="{{ $filters['search'] }}" maxlength="120" placeholder="Search by name, application ID, or applicant ID...">
            </div>
            <div class="audit-filter">
                <label for="audit-user">Applicant</label>
                <select id="audit-user" name="user_id">
                    <option value="">All applicants</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="audit-filter">
                <label for="audit-period">Application Period</label>
                <select id="audit-period" name="period">
                    @foreach($options['periods'] as $value => $label)
                        <option value="{{ $value }}" @selected($filters['period'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="audit-filter">
                <label for="audit-date-range">Date Range</label>
                <select id="audit-date-range" name="date_range">
                    @foreach(['all' => 'All Time', 'today' => 'Today', 'last7' => 'Last 7 Days', 'last30' => 'Last 30 Days', 'this_month' => 'This Month', 'custom' => 'Custom'] as $value => $label)
                        <option value="{{ $value }}" @selected($filters['date_range'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="audit-custom-dates" id="audit-custom-dates" @if($filters['date_range'] !== 'custom') hidden @endif>
                <div class="audit-filter">
                    <label for="audit-date-from">From</label>
                    <input id="audit-date-from" name="date_from" type="date" value="{{ $filters['date_from'] }}">
                </div>
                <div class="audit-filter">
                    <label for="audit-date-to">To</label>
                    <input id="audit-date-to" name="date_to" type="date" value="{{ $filters['date_to'] }}">
                </div>
            </div>
            <div class="audit-filter">
                <label for="audit-action">Action</label>
                <select id="audit-action" name="action">
                    <option value="">All Actions</option>
                    @foreach($options['actions'] as $value => $label)
                        <option value="{{ $value }}" @selected($filters['action'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="audit-filter">
                <label for="audit-status">Status</label>
                <select id="audit-status" name="status">
                    <option value="">All Statuses</option>
                    @foreach($options['statuses'] as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $statusLabel($label) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="audit-filter-actions">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter" aria-hidden="true"></i> Apply</button>
                <a href="{{ route('admin.applicant-audit.index') }}" class="btn btn-outline">Reset</a>
            </div>
            @error('date_from')<div class="audit-filter-error" role="alert">{{ $message }}</div>@enderror
            @error('date_to')<div class="audit-filter-error" role="alert">{{ $message }}</div>@enderror
        </form>

        <section class="audit-cards" aria-label="Audit summary">
            @foreach([
                ['label' => 'Total Applicants Audited', 'description' => 'Distinct applicants with matching audit records', 'value' => $summary['totalApplicants'], 'icon' => 'fa-users'],
                ['label' => "Today's Activity", 'description' => 'Matching audit records created today', 'value' => $summary['today'], 'icon' => 'fa-clock'],
                ['label' => 'Information Changes', 'description' => 'Changed applicant/application information fields', 'value' => $summary['information'], 'icon' => 'fa-pen-to-square'],
                ['label' => 'Status Changes', 'description' => 'Application status transition records', 'value' => $summary['status'], 'icon' => 'fa-arrows-rotate'],
                ['label' => 'Security Events', 'description' => 'Login, logout, and password events', 'value' => $summary['security'], 'icon' => 'fa-shield-halved'],
            ] as $card)
                <article class="audit-stat">
                    <div class="audit-stat-icon"><i class="fa-solid {{ $card['icon'] }}" aria-hidden="true"></i></div>
                    <div><strong>{{ number_format($card['value']) }}</strong><span>{{ $card['label'] }}<br>{{ $card['description'] }}</span></div>
                </article>
            @endforeach
        </section>

        @if(!$hasAuditRecords)
            <div class="audit-empty">No applicant audit records found.</div>
        @elseif($rows->isEmpty())
            <div class="audit-empty">No audit activity matches the selected filters.</div>
        @endif

        <article class="audit-card">
            <header class="audit-card-header">
                <h2><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Recent Applicant Activity</h2>
            </header>
            <div class="audit-card-body">
                @if($activities && $activities->isNotEmpty())
                    <div class="audit-table-wrap">
                        <table class="audit-table">
                            <thead><tr><th>Date &amp; Time</th><th>Action</th><th>Applicant</th><th>Application</th><th>Result</th><th>Details</th></tr></thead>
                            <tbody>
                                @foreach($activities as $event)
                                    <tr>
                                        <td>{{ $auditTime($event->created_at) }}</td>
                                        <td><strong>{{ $event->action }}</strong><span class="audit-subtext">{{ $event->actor_name }}</span></td>
                                        <td>{{ $event->applicant?->name ?? $event->actor_name }}</td>
                                        <td>{{ $event->application?->ref_id ?? ($event->application_id ? '#'.$event->application_id : '—') }}</td>
                                        <td>{{ ucfirst($event->result) }}</td>
                                        <td><a href="{{ route('admin.applicant-audit.index', array_merge(request()->query(), ['event_id' => $event->id])) }}">Details</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <footer class="audit-table-footer">
                        @if($showAllActivities)
                            <span>Showing {{ $activities->firstItem() }}–{{ $activities->lastItem() }} of {{ $activities->total() }} applicant activities</span>
                            {{ $activities->links() }}
                            <a class="btn btn-outline btn-sm" href="{{ route('admin.applicant-audit.index', array_merge(request()->query(), ['show_all' => null, 'activity_page' => null])) }}">Show recent 5</a>
                        @else
                            <span>Showing {{ $activities->count() }} recent of {{ $activities->total() }} applicant activities</span>
                            @if($activities->total() > 5)
                                <a class="btn btn-outline btn-sm" href="{{ route('admin.applicant-audit.index', array_merge(request()->query(), ['show_all' => 1, 'activity_page' => 1])) }}">Show more activities</a>
                            @endif
                        @endif
                    </footer>
                @else
                    <p class="audit-empty">No applicant activity matches the selected filters.</p>
                @endif
            </div>
        </article>

        <section class="audit-content">
            <article class="audit-card">
                <header class="audit-card-header">
                    <h2><i class="fa-solid fa-list-check" aria-hidden="true"></i> Applicant Audit Logs</h2>
                </header>
                <div class="audit-card-body">
                    @if($rows && $rows->isNotEmpty())
                        <div class="audit-table-wrap">
                            <table class="audit-table">
                                <thead><tr><th>Applicant Name</th><th>Application ID</th><th>Application Period</th><th>Current Status</th><th>Last Activity</th><th>Action</th></tr></thead>
                                <tbody>
                                    @foreach($rows as $row)
                                        @php($isSelected = $selectedApplicant && $selectedApplicant->id === $row->applicant_id && (!$selectedApplication || $selectedApplication->id === $row->application_id))
                                        <tr class="{{ $isSelected ? 'selected' : '' }}">
                                            <td><a class="audit-applicant" href="{{ route('admin.applicant-audit.show', ['applicant' => $row->applicant_id, 'application_id' => $row->application_id] + request()->query()) }}">{{ $row->applicant_name ?? 'Deleted applicant' }}</a><span class="audit-subtext">Applicant #{{ $row->applicant_id }}</span></td>
                                            <td>{{ $row->application_ref ?? ($row->application_id ? '#'.$row->application_id : '—') }}</td>
                                            <td>{{ $periodForApplication($row->application_created_at) }}</td>
                                            <td><span class="audit-badge {{ $statusClass($row->application_status) }}">{{ $statusLabel($row->application_status) }}</span></td>
                                            <td>{{ $auditTime($row->last_activity) }}</td>
                                            <td><a class="btn btn-primary btn-sm" href="{{ route('admin.applicant-audit.show', ['applicant' => $row->applicant_id, 'application_id' => $row->application_id] + request()->query()) }}">View</a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <footer class="audit-table-footer">
                            @if($showAllAuditLogs)
                                <span>Showing {{ $firstItem }}–{{ $lastItem }} of {{ $rows->total() }} applicants</span>
                                <nav class="audit-pagination" aria-label="Applicant audit pagination">
                                    @if($rows->onFirstPage())<span aria-disabled="true">Previous</span>@else<a href="{{ $rows->previousPageUrl() }}">Previous</a>@endif
                                    @foreach($rows->getUrlRange(max(1, $rows->currentPage() - 2), min($rows->lastPage(), $rows->currentPage() + 2)) as $page => $url)
                                        @if($page === $rows->currentPage())<span class="active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                                    @endforeach
                                    @if($rows->hasMorePages())<a href="{{ $rows->nextPageUrl() }}">Next</a>@else<span aria-disabled="true">Next</span>@endif
                                </nav>
                                <a class="btn btn-outline btn-sm" href="{{ route('admin.applicant-audit.index', array_merge(request()->query(), ['show_all_audit_logs' => null, 'page' => null])) }}">Show recent 5</a>
                            @else
                                <span>Showing {{ $rows->count() }} recent of {{ $rows->total() }} applicants</span>
                                @if($rows->total() > 5)
                                    <a class="btn btn-outline btn-sm" href="{{ route('admin.applicant-audit.index', array_merge(request()->query(), ['show_all_audit_logs' => 1, 'page' => 1])) }}">Show more audit logs</a>
                                @endif
                            @endif
                        </footer>
                    @else
                        <div class="audit-empty">{{ $hasAuditRecords ? 'No audit activity matches the selected filters.' : 'No applicant audit records found.' }}</div>
                    @endif
                </div>
            </article>

            <aside class="audit-card audit-details" aria-labelledby="audit-details-heading">
                <header class="audit-card-header">
                    <h2 id="audit-details-heading">Applicant Audit Details</h2>
                    @if($selectedApplicant)
                        <a class="btn btn-outline btn-sm" href="{{ route('admin.applicant-audit.index', request()->query()) }}">Back</a>
                    @endif
                </header>
                @if($selectedActivity || ($selectedApplicant && $history))
                    <div class="audit-card-body">
                        @if($selectedActivity)
                            <section aria-labelledby="selected-activity-heading" style="margin-bottom:18px;padding:13px;border:1px solid var(--border);border-radius:9px;background:#fafafa;">
                                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                                    <h3 id="selected-activity-heading" style="color:var(--primary);font-size:.85rem;">Activity Details</h3>
                                    <a class="btn btn-outline btn-sm" href="{{ route('admin.applicant-audit.index', array_merge(request()->query(), ['event_id' => null])) }}">Close</a>
                                </div>
                                <div class="detail-meta">
                                    <div><span>Audit ID</span><strong>{{ $selectedActivity->audit_code }}</strong></div>
                                    <div><span>Action</span><strong>{{ $selectedActivity->action }}</strong></div>
                                    <div><span>Performed By</span><strong>{{ $selectedActivity->actor_name }}</strong></div>
                                    <div><span>Role</span><strong>{{ ucfirst($selectedActivity->actor_role) }} · {{ ucfirst($selectedActivity->actor_type) }}</strong></div>
                                    <div><span>Date &amp; Time</span><strong>{{ $auditTime($selectedActivity->created_at) }}</strong></div>
                                    <div><span>Module</span><strong>{{ $selectedActivity->module }}</strong></div>
                                    <div><span>Result</span><strong>{{ ucfirst($selectedActivity->result) }}</strong></div>
                                    <div><span>IP Address</span><strong>{{ $selectedActivity->ip_address ?? 'Not recorded' }}</strong></div>
                                    @if($selectedActivity->field_name)
                                        <div><span>Field</span><strong>{{ str_replace('_', ' ', ucfirst($selectedActivity->field_name)) }}</strong></div>
                                        <div><span>Previous Value</span><strong>{{ $selectedActivity->old_value ?? '—' }}</strong></div>
                                        <div><span>New Value</span><strong>{{ $selectedActivity->new_value ?? '—' }}</strong></div>
                                    @endif
                                </div>
                                @if($selectedActivity->description)
                                    <p style="margin-top:10px;color:var(--text-muted);font-size:.72rem;line-height:1.5;">{{ $selectedActivity->description }}</p>
                                @endif
                            </section>
                        @endif
                        @if($selectedApplicant && $history)
                            <div class="detail-top">
                                <div>
                                    <h3>{{ $selectedApplicant->name }}</h3>
                                    <span class="audit-subtext">{{ $selectedApplication ? 'Application '.$selectedApplication->ref_id : 'No application submitted' }}</span>
                                </div>
                                <span class="audit-badge {{ $statusClass($selectedApplication?->status) }}">{{ $statusLabel($selectedApplication?->status) }}</span>
                            </div>
                        <div class="detail-meta">
                            <div><span>Applicant ID</span><strong>{{ $selectedApplicant->id }}</strong></div>
                            <div><span>Application Period</span><strong>{{ $periodForApplication($selectedApplication?->created_at) }}</strong></div>
                        </div>
                        @if($selectedApplication)
                            <div class="detail-links">
                                <a class="btn btn-outline btn-sm" href="{{ route('admin.applicant-audit.status-history', ['applicant' => $selectedApplicant->id, 'application_id' => $selectedApplication->id]) }}">Status History</a>
                            </div>
                        @endif
                        <h3 style="margin:17px 0 5px;color:var(--primary);font-size:.83rem;">Audit History</h3>
                        @if($history->isNotEmpty())
                            <div class="audit-timeline">
                                @foreach($history as $event)
                                    <article class="timeline-entry">
                                        <span class="timeline-dot" aria-hidden="true"></span>
                                        <div class="timeline-copy">
                                            <a href="{{ route('admin.applicant-audit.show', array_merge(request()->query(), ['applicant' => $selectedApplicant->id, 'application_id' => $selectedApplication?->id, 'event_id' => $event->id])) }}">{{ $event->action }}@if($event->field_name) · {{ str_replace('_', ' ', ucfirst($event->field_name)) }}@endif</a>
                                            <time>{{ $auditTime($event->created_at) }}</time>
                                            <span>{{ $event->actor_type === 'admin' ? 'Changed by:' : (in_array($event->action, [\App\Models\AuditAction::ACCOUNT_CREATED, \App\Models\AuditAction::APPLICATION_SUBMITTED], true) ? 'Created / submitted by:' : 'Recorded by:') }} {{ $event->actor_name }} ({{ ucfirst($event->actor_role) }})</span>
                                            @if($event->description)<p>{{ $event->description }}</p>@endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                            {{ $history->links() }}
                        @else
                            <p class="audit-empty">No audit activity matches the selected filters.</p>
                        @endif
                        @if($statusHistory->isNotEmpty())
                            <p class="audit-info">Status transitions are listed newest first. Open Status History for the complete transition list.</p>
                        @endif
                        @endif
                    </div>
                @else
                    <div class="audit-card-body"><p class="audit-empty">Select an applicant’s View action to see their audit history.</p></div>
                @endif
            </aside>
        </section>
    @endif
</main>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const range = document.getElementById('audit-date-range');
        const customDates = document.getElementById('audit-custom-dates');
        range?.addEventListener('change', function () {
            customDates.hidden = range.value !== 'custom';
        });
    });
</script>
@endsection
