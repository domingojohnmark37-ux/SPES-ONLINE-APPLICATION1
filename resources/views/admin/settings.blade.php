@extends('layouts.admin')

@section('title', 'Settings')
@section('page-title', 'Settings')
@section('page-sub', 'Manage admin preferences and portal settings')

@section('styles')
<style>
    .application-period-page { display:grid; gap:20px; }
    .application-period-status {
        position:relative; display:flex; align-items:center; gap:18px; overflow:hidden;
        padding:22px 24px; border:1px solid var(--border); border-radius:14px;
        background:var(--white); box-shadow:var(--shadow);
    }
    .application-period-status::after {
        position:absolute; top:0; bottom:0; left:0; width:5px; background:var(--info); content:"";
    }
    .application-period-status[data-state="open"]::after { background:var(--success); }
    .application-period-status[data-state="closed"]::after { background:var(--danger); }
    .application-period-status-icon {
        display:grid; width:50px; height:50px; flex:0 0 50px; place-items:center;
        border-radius:14px; background:rgba(21,101,192,.1); color:var(--info); font-size:1.25rem;
    }
    .application-period-status[data-state="open"] .application-period-status-icon { background:rgba(46,125,50,.11); color:var(--success); }
    .application-period-status[data-state="closed"] .application-period-status-icon { background:rgba(198,40,40,.1); color:var(--danger); }
    .application-period-status-copy { min-width:0; }
    .application-period-status-copy small { display:block; margin-bottom:4px; color:var(--text-muted); font-size:.77rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; }
    .application-period-status-copy h2 { color:var(--text); font-size:1.12rem; font-weight:750; }
    .application-period-status-copy p { margin-top:5px; color:var(--text-muted); font-size:.87rem; line-height:1.5; }
    .application-period-card { overflow:hidden; border:1px solid var(--border); border-radius:14px; box-shadow:var(--shadow); }
    .application-period-card .card-header { padding:20px 24px; background:linear-gradient(110deg,rgba(139,0,0,.06),transparent 75%); }
    .application-period-card .card-header h2 { display:flex; align-items:center; gap:10px; color:var(--primary); }
    .application-period-card .card-body { padding:24px; }
    .application-period-form { display:grid; gap:20px; }
    .application-period-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px; }
    .application-period-field { min-width:0; }
    .application-period-field label { display:block; margin-bottom:8px; color:var(--text); font-size:.9rem; font-weight:700; }
    .application-period-field input {
        width:100%; min-height:46px; padding:10px 12px; border:1px solid var(--border);
        border-radius:9px; background:var(--white); color:var(--text); font:inherit; font-size:.92rem;
        transition:border-color .16s ease,box-shadow .16s ease;
    }
    .application-period-field input:focus { border-color:var(--primary); outline:0; box-shadow:0 0 0 3px rgba(139,0,0,.12); }
    .application-period-field small { display:block; margin-top:7px; color:var(--text-muted); font-size:.78rem; line-height:1.45; }
    .application-period-error { margin-top:6px; color:var(--danger); font-size:.8rem; }
    .application-period-notice { display:flex; align-items:center; gap:10px; padding:12px 14px; border:1px solid rgba(21,101,192,.18); border-radius:9px; background:rgba(21,101,192,.06); color:var(--text-muted); font-size:.83rem; }
    .application-period-notice > i { color:var(--info); }
    .application-period-notice strong { color:var(--text); }
    .application-period-actions { display:flex; flex-wrap:wrap; align-items:center; gap:10px; padding-top:18px; border-top:1px solid var(--border); }
    .application-period-actions .btn { display:inline-flex; min-height:42px; align-items:center; justify-content:center; gap:8px; padding:10px 16px; border-radius:8px; font-weight:700; }
    .application-period-quick-actions { display:flex; flex-wrap:wrap; gap:9px; }
    .application-period-quick-actions button {
        min-height:38px; padding:8px 11px; border:1px solid var(--border); border-radius:8px;
        background:var(--white); color:var(--text); font:inherit; font-size:.8rem; font-weight:650; cursor:pointer;
    }
    .application-period-quick-actions button:hover { border-color:var(--primary); color:var(--primary); }
    .application-period-footnote { margin-left:auto; color:var(--text-muted); font-size:.78rem; }
    .application-period-insights { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
    .application-period-insight { padding:20px; border:1px solid var(--border); border-radius:12px; background:var(--white); box-shadow:var(--shadow); }
    .application-period-insight-heading { display:flex; align-items:center; gap:10px; margin-bottom:16px; color:var(--text); font-size:.95rem; font-weight:750; }
    .application-period-insight-heading i { color:var(--primary); }
    .application-period-movement { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .application-period-movement-item { padding:12px; border-radius:9px; background:var(--bg); }
    .application-period-movement-item small { display:block; margin-bottom:6px; color:var(--text-muted); font-size:.75rem; }
    .application-period-movement-item strong { display:block; color:var(--text); font-size:.9rem; }
    .application-period-movement-item strong i { margin-right:4px; }
    .application-period-movement-item[data-direction="earlier"] strong { color:var(--info); }
    .application-period-movement-item[data-direction="later"] strong { color:var(--primary); }
    .application-period-insight-note { margin-top:12px; color:var(--text-muted); font-size:.76rem; line-height:1.45; }
    .application-period-audit { overflow:hidden; border:1px solid var(--border); border-radius:14px; background:var(--white); box-shadow:var(--shadow); }
    .application-period-audit-heading { padding:19px 22px; border-bottom:1px solid var(--border); }
    .application-period-audit-heading h2 { display:flex; align-items:center; gap:10px; color:var(--primary); font-size:1rem; }
    .application-period-audit-heading p { margin-top:5px; color:var(--text-muted); font-size:.8rem; }
    .application-period-audit-wrap { overflow-x:auto; }
    .application-period-audit table { width:100%; border-collapse:collapse; text-align:left; }
    .application-period-audit th,.application-period-audit td { padding:12px 16px; border-bottom:1px solid var(--border); font-size:.8rem; vertical-align:top; }
    .application-period-audit th { color:var(--text-muted); background:var(--bg); font-size:.72rem; letter-spacing:.04em; text-transform:uppercase; }
    .application-period-audit td { color:var(--text); }
    .application-period-audit td small { display:block; margin-top:3px; color:var(--text-muted); }
    .application-period-audit tr:last-child td { border-bottom:0; }
    .application-period-audit-empty { padding:28px 20px; color:var(--text-muted); font-size:.85rem; text-align:center; }
    .period-confirm-backdrop { position:fixed; inset:0; z-index:1500; display:grid; place-items:center; padding:20px; background:rgba(17,24,39,.58); backdrop-filter:blur(3px); }
    .period-confirm-backdrop[hidden] { display:none; }
    .period-confirm-dialog { width:min(460px,100%); overflow:hidden; border:1px solid var(--border); border-radius:16px; background:var(--white); color:var(--text); box-shadow:0 24px 70px rgba(0,0,0,.3); animation:period-dialog-in .18s ease-out; }
    .period-confirm-top { display:flex; align-items:center; gap:13px; padding:22px 24px 13px; }
    .period-confirm-icon { display:grid; width:44px; height:44px; flex:0 0 44px; place-items:center; border-radius:13px; background:rgba(21,101,192,.1); color:var(--info); font-size:1.1rem; }
    .period-confirm-dialog[data-kind="save"] .period-confirm-icon { background:rgba(46,125,50,.11); color:var(--success); }
    .period-confirm-dialog h2 { font-size:1.05rem; font-weight:750; }
    .period-confirm-copy { padding:0 24px 20px 81px; color:var(--text-muted); font-size:.88rem; line-height:1.55; }
    .period-confirm-actions { display:flex; justify-content:flex-end; gap:9px; padding:15px 20px; border-top:1px solid var(--border); background:var(--bg); }
    .period-confirm-actions button { min-height:40px; padding:9px 14px; border:1px solid var(--border); border-radius:8px; background:var(--white); color:var(--text); font:inherit; font-size:.82rem; font-weight:700; cursor:pointer; }
    .period-confirm-actions button:focus-visible { outline:3px solid rgba(21,101,192,.35); outline-offset:2px; }
    .period-confirm-actions [data-confirm-action] { border-color:var(--primary); background:var(--primary); color:#fff; }
    .period-confirm-dialog[data-kind="leave"] .period-confirm-actions [data-confirm-action] { border-color:var(--danger); background:var(--danger); }
    @keyframes period-dialog-in { from { opacity:0; transform:translateY(8px) scale(.98); } to { opacity:1; transform:translateY(0) scale(1); } }
    @media(prefers-reduced-motion:reduce) { .period-confirm-dialog { animation:none; } }
    @media(max-width:700px) {
        .application-period-status { align-items:flex-start; padding:18px; }
        .application-period-status-icon { width:42px; height:42px; flex-basis:42px; font-size:1.05rem; }
        .application-period-card .card-header,.application-period-card .card-body { padding:18px; }
        .application-period-fields { grid-template-columns:1fr; gap:15px; }
        .application-period-actions { align-items:stretch; }
        .application-period-actions > .btn { flex:1; }
        .application-period-footnote { width:100%; margin:3px 0 0; }
        .application-period-insights { grid-template-columns:1fr; }
        .application-period-audit th,.application-period-audit td { padding:10px 12px; }
    }
</style>
@endsection

@section('content')

@php
    $periodNow = now(config('app.timezone'));
    $periodStart = $settings->application_start_date?->copy()->setTimezone(config('app.timezone'));
    $periodEnd = $settings->application_end_date?->copy()->setTimezone(config('app.timezone'));
    $approvalCapacityFull = $approvalLimitReached;

    if ($approvalCapacityFull) {
        $periodState = 'closed';
        $periodTitle = 'Approval limit reached';
        $periodSummary = "The program has approved {$approvedApplicantCount} of {$settings->approved_applicant_limit} applicants. New applications and approvals are closed.";
    } elseif (! $periodStart && ! $periodEnd) {
        $periodState = 'open';
        $periodTitle = 'Applications are open';
        $periodSummary = 'No application schedule is set, so submissions remain open continuously.';
    } elseif (! $periodStart || ! $periodEnd) {
        $periodState = 'closed';
        $periodTitle = 'Applications are closed';
        $periodSummary = 'The schedule is incomplete. Set both an opening and closing date to accept submissions.';
    } elseif ($periodNow->lt($periodStart)) {
        $periodState = 'scheduled';
        $periodTitle = 'Applications are not open yet';
        $periodSummary = 'Submissions are scheduled to open '.$periodStart->format('M j, Y \a\t g:i A').'.';
    } elseif ($periodNow->gt($periodEnd)) {
        $periodState = 'closed';
        $periodTitle = 'Applications are closed';
        $periodSummary = 'The submission window ended '.$periodEnd->format('M j, Y \a\t g:i A').'.';
    } else {
        $periodState = 'open';
        $periodTitle = 'Applications are open';
        $periodSummary = 'Submissions are being accepted until '.$periodEnd->format('M j, Y \a\t g:i A').'.';
    }
    $formatMovementDays = fn (int $seconds): string => number_format($seconds / 86400, 1).' days';
@endphp
<div class="application-period-page">
    <section class="application-period-status" data-application-period-status data-state="{{ $periodState }}" data-current-approved="{{ $approvedApplicantCount }}" data-capacity-full="{{ $approvalCapacityFull ? 'true' : 'false' }}" data-capacity-summary="{{ $periodSummary }}" aria-live="polite">
        <span class="application-period-status-icon" aria-hidden="true">
            <i class="fa-solid {{ $periodState === 'open' ? 'fa-door-open' : ($periodState === 'closed' ? 'fa-lock' : 'fa-clock') }}" data-status-icon></i>
        </span>
        <div class="application-period-status-copy">
            <small>Current submission status</small>
            <h2 data-status-title>{{ $periodTitle }}</h2>
            <p data-status-summary>{{ $periodSummary }}</p>
        </div>
    </section>

    <section class="application-period-insights" aria-label="Application schedule change statistics">
        @foreach([
            'application_start_date' => ['title' => 'Opening date changes', 'icon' => 'fa-arrow-right-to-bracket'],
            'application_end_date' => ['title' => 'Closing date changes', 'icon' => 'fa-arrow-right-from-bracket'],
        ] as $periodField => $periodInsight)
            @php($movement = $applicationPeriodStatistics[$periodField])
            <article class="application-period-insight">
                <h2 class="application-period-insight-heading">
                    <i class="fa-solid {{ $periodInsight['icon'] }}" aria-hidden="true"></i>
                    {{ $periodInsight['title'] }}
                </h2>
                <div class="application-period-movement">
                    <div class="application-period-movement-item" data-direction="earlier">
                        <small>Moved earlier</small>
                        <strong><i class="fa-solid fa-arrow-up" aria-hidden="true"></i>{{ $movement['earlier_count'] }} {{ \Illuminate\Support\Str::plural('change', $movement['earlier_count']) }}</strong>
                        <small>{{ $formatMovementDays($movement['earlier_seconds']) }} total</small>
                    </div>
                    <div class="application-period-movement-item" data-direction="later">
                        <small>Moved later</small>
                        <strong><i class="fa-solid fa-arrow-down" aria-hidden="true"></i>{{ $movement['later_count'] }} {{ \Illuminate\Support\Str::plural('change', $movement['later_count']) }}</strong>
                        <small>{{ $formatMovementDays($movement['later_seconds']) }} total</small>
                    </div>
                </div>
                <p class="application-period-insight-note">Counts and total days compare each saved date with its previous value. First-time scheduling and clearing a date are audited but do not count as earlier or later moves.</p>
            </article>
        @endforeach
    </section>

    <section class="card application-period-card">
        <div class="card-header">
            <h2><i class="fa-solid fa-calendar-days" aria-hidden="true"></i> Application schedule</h2>
        </div>
        <div class="card-body">
            <form class="application-period-form" method="POST" action="{{ route('admin.settings.update') }}" id="application-period-form" data-unsaved-guard="off"
                  data-saved-start="{{ $settings->application_start_date?->format('Y-m-d\TH:i') ?? '' }}"
                  data-saved-end="{{ $settings->application_end_date?->format('Y-m-d\TH:i') ?? '' }}"
                  data-saved-limit="{{ $settings->approved_applicant_limit ?? '' }}">
                @csrf
                @method('PUT')

                <div class="application-period-fields">
                    <div class="application-period-field">
                        <label for="application_start_date">Start of submission</label>
                        <input id="application_start_date" type="datetime-local"
                               name="application_start_date"
                               value="{{ old('application_start_date', $settings->application_start_date?->format('Y-m-d\TH:i')) }}"
                               aria-describedby="application-start-help">
                        <small id="application-start-help">Applicants can submit starting at this date and time.</small>
                        @error('application_start_date')
                            <div class="application-period-error"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    <div class="application-period-field">
                        <label for="application_end_date">End of submission</label>
                        <input id="application_end_date" type="datetime-local"
                               name="application_end_date"
                               value="{{ old('application_end_date', $settings->application_end_date?->format('Y-m-d\TH:i')) }}"
                               aria-describedby="application-end-help">
                        <small id="application-end-help">Submissions close automatically at this date and time.</small>
                        @error('application_end_date')
                            <div class="application-period-error"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> {{ $message }}</div>
                        @enderror
                    </div>
                    <div class="application-period-field">
                        <label for="approved_applicant_limit">Maximum approved applicants</label>
                        <input id="approved_applicant_limit" type="number"
                               name="approved_applicant_limit" min="1" max="100000" step="1"
                               value="{{ old('approved_applicant_limit', $settings->approved_applicant_limit) }}"
                               placeholder="No limit">
                        <small>Optional. Leave blank for no approval cap. The system counts approved applicants in the current application season.</small>
                        @error('approved_applicant_limit')
                            <div class="application-period-error"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> {{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="application-period-notice">
                    <i class="fa-solid fa-chart-simple" aria-hidden="true"></i>
                    <span><strong>Approved this season: {{ $approvedApplicantCount }}</strong>
                        @if($settings->approved_applicant_limit)
                            of {{ $settings->approved_applicant_limit }} available approvals
                        @else
                            · No maximum has been set
                        @endif
                    </span>
                </div>

                <div class="application-period-quick-actions" aria-label="Quick application period actions">
                    <button type="button" data-period-action="open"><i class="fa-solid fa-door-open" aria-hidden="true"></i> Keep applications open</button>
                    <button type="button" data-period-action="close"><i class="fa-solid fa-lock" aria-hidden="true"></i> Close applications now</button>
                </div>

                <div class="application-period-actions">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save application schedule</button>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">Cancel</a>
                    <span class="application-period-footnote"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Changes take effect when saved.</span>
                </div>
            </form>
        </div>
    </section>

    <section class="application-period-audit" aria-labelledby="application-period-audit-heading">
        <div class="application-period-audit-heading">
            <h2 id="application-period-audit-heading"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Application schedule audit history</h2>
            <p>Recent changes show who changed each date, when they changed it, and the previous and new values.</p>
        </div>
        @if($applicationPeriodAudits->isNotEmpty())
            <div class="application-period-audit-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Changed</th>
                            <th>Administrator</th>
                            <th>Field</th>
                            <th>Previous value</th>
                            <th>New value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($applicationPeriodAudits as $audit)
                            <tr>
                                <td>
                                    {{ $audit->created_at->format('M j, Y') }}
                                    <small>{{ $audit->created_at->format('g:i A') }}</small>
                                </td>
                                <td>{{ $audit->actor_name }}</td>
                                <td>{{ match($audit->field_name) {
                                    'application_start_date' => 'Start of submission',
                                    'application_end_date' => 'End of submission',
                                    'approved_applicant_limit' => 'Maximum approved applicants',
                                    default => $audit->field_name,
                                } }}</td>
                                <td>
                                    @if($audit->field_name === 'approved_applicant_limit')
                                        {{ $audit->old_value ?: 'No limit' }}
                                    @else
                                        {{ $audit->old_value ? \Illuminate\Support\Carbon::parse($audit->old_value)->format('M j, Y · g:i A') : 'Not set' }}
                                    @endif
                                </td>
                                <td>
                                    @if($audit->field_name === 'approved_applicant_limit')
                                        {{ $audit->new_value ?: 'No limit' }}
                                    @else
                                        {{ $audit->new_value ? \Illuminate\Support\Carbon::parse($audit->new_value)->format('M j, Y · g:i A') : 'Not set' }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="application-period-audit-empty">
                <i class="fa-regular fa-clipboard" aria-hidden="true"></i>
                No schedule changes have been recorded yet. Changes will appear here after the dates are saved.
            </div>
        @endif
    </section>
</div>
<div class="period-confirm-backdrop" data-period-confirm-backdrop hidden>
    <section class="period-confirm-dialog" data-period-confirm-dialog data-kind="save" role="dialog" aria-modal="true" aria-labelledby="period-confirm-title" aria-describedby="period-confirm-copy" tabindex="-1">
        <div class="period-confirm-top">
            <span class="period-confirm-icon" aria-hidden="true"><i class="fa-solid fa-circle-question" data-confirm-icon></i></span>
            <h2 id="period-confirm-title" data-confirm-title>Confirm schedule changes</h2>
        </div>
        <p class="period-confirm-copy" id="period-confirm-copy" data-confirm-copy>Save these application schedule changes? The new dates will affect when applicants can submit.</p>
        <div class="period-confirm-actions">
            <button type="button" data-cancel-action>Review changes</button>
            <button type="button" data-confirm-action>Yes, save changes</button>
        </div>
    </section>
</div>
<script>
    (() => {
        const form = document.getElementById('application-period-form');
        const startInput = document.getElementById('application_start_date');
        const endInput = document.getElementById('application_end_date');
        const limitInput = document.getElementById('approved_applicant_limit');
        const status = document.querySelector('[data-application-period-status]');
        if (!form || !startInput || !endInput || !status) return;

        const title = status.querySelector('[data-status-title]');
        const summary = status.querySelector('[data-status-summary]');
        const icon = status.querySelector('[data-status-icon]');
        const backdrop = document.querySelector('[data-period-confirm-backdrop]');
        const dialog = document.querySelector('[data-period-confirm-dialog]');
        const dialogTitle = dialog.querySelector('[data-confirm-title]');
        const dialogCopy = dialog.querySelector('[data-confirm-copy]');
        const dialogIcon = dialog.querySelector('[data-confirm-icon]');
        const confirmButton = dialog.querySelector('[data-confirm-action]');
        const cancelButton = dialog.querySelector('[data-cancel-action]');
        const initialValues = {
            start: form.dataset.savedStart,
            end: form.dataset.savedEnd,
            limit: form.dataset.savedLimit,
        };
        let modalPurpose = null;
        let pendingDestination = null;
        let returnFocus = null;
        let allowUnload = false;
        let submittingAfterConfirmation = false;

        const hasUnsavedChanges = () => (
            startInput.value !== initialValues.start
            || endInput.value !== initialValues.end
            || limitInput.value !== initialValues.limit
        );

        const closeDialog = () => {
            backdrop.hidden = true;
            modalPurpose = null;
            returnFocus?.focus();
        };

        const openDialog = (purpose, trigger) => {
            modalPurpose = purpose;
            returnFocus = trigger ?? document.activeElement;
            dialog.dataset.kind = purpose;

            if (purpose === 'save') {
                dialogTitle.textContent = 'Confirm schedule changes';
                dialogCopy.textContent = 'Save these application schedule changes? The new dates will affect when applicants can submit.';
                dialogIcon.className = 'fa-solid fa-circle-question';
                confirmButton.textContent = 'Yes, save changes';
                cancelButton.textContent = 'Review changes';
            } else {
                dialogTitle.textContent = 'Leave without saving?';
                dialogCopy.textContent = 'Your application schedule edits have not been saved. If you leave now, those changes will be lost.';
                dialogIcon.className = 'fa-solid fa-triangle-exclamation';
                confirmButton.textContent = 'Leave without saving';
                cancelButton.textContent = 'Stay and keep editing';
            }

            backdrop.hidden = false;
            cancelButton.focus();
        };
        const dateFormatter = new Intl.DateTimeFormat(undefined, {
            dateStyle: 'medium',
            timeStyle: 'short',
        });

        const updateStatus = () => {
            const start = startInput.value ? new Date(startInput.value) : null;
            const end = endInput.value ? new Date(endInput.value) : null;
            const now = new Date();
            const limit = limitInput.value ? Number(limitInput.value) : null;
            const capacityFull = limit !== null && Number(status.dataset.currentApproved) >= limit;
            if (capacityFull) {
                title.textContent = 'Approval limit reached';
                summary.textContent = `The program has approved ${status.dataset.currentApproved} of ${limit} applicants. New applications and approvals are closed.`;
                icon.className = 'fa-solid fa-lock';
                return;
            }
            let state;
            let heading;
            let message;

            if (!start && !end) {
                state = 'open';
                heading = 'Applications are open';
                message = 'No application schedule is set, so submissions remain open continuously.';
            } else if (!start || !end) {
                state = 'closed';
                heading = 'Applications are closed';
                message = 'The schedule is incomplete. Set both an opening and closing date to accept submissions.';
            } else if (now < start) {
                state = 'scheduled';
                heading = 'Applications are not open yet';
                message = `Submissions are scheduled to open ${dateFormatter.format(start)}.`;
            } else if (now > end) {
                state = 'closed';
                heading = 'Applications are closed';
                message = `The submission window ended ${dateFormatter.format(end)}.`;
            } else {
                state = 'open';
                heading = 'Applications are open';
                message = `Submissions are being accepted until ${dateFormatter.format(end)}.`;
            }

            status.dataset.state = state;
            title.textContent = heading;
            summary.textContent = message;
            icon.className = `fa-solid ${state === 'open' ? 'fa-door-open' : (state === 'closed' ? 'fa-lock' : 'fa-clock')}`;
        };

        startInput.addEventListener('input', updateStatus);
        endInput.addEventListener('input', updateStatus);
        limitInput.addEventListener('input', updateStatus);

        form.addEventListener('submit', (event) => {
            if (submittingAfterConfirmation || !hasUnsavedChanges()) return;
            event.preventDefault();
            openDialog('save', event.submitter);
        });

        confirmButton.addEventListener('click', () => {
            if (modalPurpose === 'save') {
                submittingAfterConfirmation = true;
                allowUnload = true;
                form.submit();
                return;
            }

            if (modalPurpose === 'leave' && pendingDestination) {
                allowUnload = true;
                window.location.assign(pendingDestination);
            }
        });

        cancelButton.addEventListener('click', () => {
            pendingDestination = null;
            closeDialog();
        });

        backdrop.addEventListener('click', (event) => {
            if (event.target === backdrop) closeDialog();
        });

        document.addEventListener('keydown', (event) => {
            if (backdrop.hidden) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                pendingDestination = null;
                closeDialog();
            } else if (event.key === 'Tab') {
                event.preventDefault();
                (document.activeElement === cancelButton ? confirmButton : cancelButton).focus();
            }
        });

        document.addEventListener('click', (event) => {
            if (!hasUnsavedChanges() || event.defaultPrevented || event.button !== 0
                || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

            const link = event.target.closest('a[href]');
            if (!link || link.target && link.target !== '_self' || link.hasAttribute('download')) return;

            const destination = new URL(link.href, window.location.href);
            if (destination.href === window.location.href || destination.hash && destination.pathname === window.location.pathname
                && destination.search === window.location.search) return;

            event.preventDefault();
            pendingDestination = destination.href;
            openDialog('leave', link);
        }, true);

        window.addEventListener('beforeunload', (event) => {
            if (hasUnsavedChanges() && !allowUnload) {
                event.preventDefault();
                event.returnValue = '';
            }
        });

        form.querySelector('[data-period-action="open"]').addEventListener('click', () => {
            startInput.value = '';
            endInput.value = '';
            updateStatus();
        });

        form.querySelector('[data-period-action="close"]').addEventListener('click', () => {
            const now = new Date();
            const localDateTime = (date) => {
                const offset = date.getTimezoneOffset() * 60000;
                return new Date(date.getTime() - offset).toISOString().slice(0, 16);
            };

            startInput.value = localDateTime(new Date(now.getTime() - 120000));
            endInput.value = localDateTime(new Date(now.getTime() - 60000));
            updateStatus();
        });
    })();
</script>
@endsection
