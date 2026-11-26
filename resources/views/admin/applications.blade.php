@extends('layouts.admin')

@section('title', 'Applicants Management')
@section('page-title', 'Applicants Management')
@section('page-sub', 'Review, approve or deny SPES applications')

@section('content')

@if(session('success'))
    <div class="alert alert-success" role="status" data-spes-alert="success"><x-icon class="fa-solid fa-circle-check" /> {{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" role="alert" data-spes-alert="error"><x-icon class="fa-solid fa-triangle-exclamation" /> {{ session('error') }}</div>
@endif

{{-- Stats mini row --}}
<div class="stats-grid" style="margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-icon teal"><x-icon class="fa-solid fa-file-lines" /></div>
        <div><div class="stat-num" data-application-stat="total">{{ $stats['total'] }}</div><div class="stat-label">Total</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><x-icon class="fa-solid fa-clock" /></div>
        <div><div class="stat-num" data-application-stat="pending">{{ $stats['pending'] }}</div><div class="stat-label">Pending</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><x-icon class="fa-solid fa-circle-check" /></div>
        <div><div class="stat-num" data-application-stat="approved">{{ $stats['approved'] }}</div><div class="stat-label">Approved</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><x-icon class="fa-solid fa-circle-xmark" /></div>
        <div><div class="stat-num" data-application-stat="denied">{{ $stats['denied'] }}</div><div class="stat-label">Denied</div></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><x-icon class="fa-solid fa-list" /> All Applications</h2>
    </div>
    <div class="card-body">
        @if($approvalCapacity['limit'])
            <div class="alert {{ $approvalCapacity['full'] ? 'alert-danger' : 'alert-info' }}" role="status">
                <x-icon class="fa-solid {{ $approvalCapacity['full'] ? 'fa-lock' : 'fa-chart-simple' }}" aria-hidden="true" />
                <span>
                    <strong>{{ $approvalCapacity['approved'] }} of {{ $approvalCapacity['limit'] }} approved this season.</strong>
                    @if($approvalCapacity['full'])
                        The approval limit is full. Further approvals and new application submissions are disabled.
                    @else
                        {{ $approvalCapacity['limit'] - $approvalCapacity['approved'] }} approval {{ \Illuminate\Support\Str::plural('slot', $approvalCapacity['limit'] - $approvalCapacity['approved']) }} remaining.
                    @endif
                    <a href="{{ route('admin.settings') }}" style="color:inherit;font-weight:700;">Manage limit</a>
                </span>
            </div>
        @endif
        {{-- Filters --}}
        <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 20px; flex-wrap: wrap;">
            <form method="GET" class="search-bar" style="margin-bottom: 0; flex: 1; min-width: 300px;">
                <input type="text" name="search" placeholder="Search by name…" value="{{ request('search') }}" style="min-width:220px;">
                <select name="barangay">
                    <option value="">All Barangay</option>
                    @foreach(['Abagao','Alaguia','Bagumbayan','Bangag','Bical','Bicud','Binag','Cabayabasan (Capacuan)','Cagoran','Cambong','Catayauan','Catugan','Centro (Poblacion)','Cullit','Dagupan','Dalaya','Fabrica','Fusina','Jurisdiction','Lalafugan','Logac','Magallungon (Santa Teresa)','Magapit','Malanao','Maxingal','Naguilian','Paranum','Rosario','San Antonio (Lafu)','San Jose','San Juan','San Lorenzo','San Mariano','Santa Maria','Tucalana'] as $b)
                        <option value="{{ $b }}" {{ request('barangay')===$b ? 'selected':'' }}>{{ $b }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary btn-sm"><x-icon class="fa-solid fa-magnifying-glass" /> Filter</button>
                <a href="{{ route('admin.applications.index') }}" class="btn btn-outline btn-sm">Clear</a>
            </form>
        </div>

        @if($applications->isEmpty())
            <p style="color:var(--text-muted);text-align:center;padding:40px 0;">No applications found.</p>
        @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Ref ID</th>
                        <th>Full Name</th>
                        <th>Barangay</th>
                        <th>SPES Type</th>
                        <th>Status</th>
                        <th>Documents</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($applications as $i => $app)
                    @php
                        $applicationDocumentLabels = [
                            'birth_certificate' => 'Birth Certificate',
                            'certificate_enrollment' => 'Certificate of Enrollment',
                            'certificate_grade' => 'Certificate of Grades',
                            'application_letter' => 'Application Letter',
                            'indigency' => 'Certificate of Indigency',
                        ];
                        $availableDenialDocuments = collect($applicationDocumentLabels)
                            ->filter(fn ($label, $key) => filled($app->{$key}) && \Illuminate\Support\Facades\Storage::disk('local')->exists($app->{$key}));
                        $documentPanelItems = [];
                        foreach ($applicationDocumentLabels as $documentKey => $documentLabel) {
                            $path = $app->{$documentKey};
                            $available = $path && \Illuminate\Support\Facades\Storage::disk('local')->exists($path);
                            $required = in_array($documentKey, ['birth_certificate', 'certificate_enrollment'], true);
                            if ($available || $path || $required) {
                                $documentPanelItems[] = [
                                    'category' => __($documentLabel),
                                    'filename' => $available
                                        ? ($app->document_original_names[$documentKey] ?? basename($path))
                                        : ($path ? basename($path) : ''),
                                    'available' => (bool) $available,
                                    'status_label' => $available ? __('On file') : ($path ? __('File missing from private storage') : __('Required document not uploaded')),
                                    'url' => $available ? route('applications.document.stream', ['application' => $app, 'document' => $documentKey]) : null,
                                    'download_url' => $available ? route('applications.document.stream', ['application' => $app, 'document' => $documentKey]) : null,
                                ];
                            }
                        }
                        foreach ($app->additionalRequirementSubmissions as $submission) {
                            $available = \Illuminate\Support\Facades\Storage::disk('local')->exists($submission->file_path);
                            $documentPanelItems[] = [
                                'category' => $submission->requirement->name.' — '.__('File :number', ['number' => $submission->file_number]),
                                'filename' => $submission->original_name,
                                'available' => $available,
                                'status_label' => $available ? __('On file') : __('File missing from private storage'),
                                'url' => $available ? route('applications.additional-requirements.submission', [
                                    'application' => $app,
                                    'additionalRequirement' => $submission->additional_requirement_id,
                                    'submission' => $submission->id,
                                ]) : null,
                                'download_url' => $available ? route('applications.additional-requirements.submission', [
                                    'application' => $app,
                                    'additionalRequirement' => $submission->additional_requirement_id,
                                    'submission' => $submission->id,
                                ]) : null,
                            ];
                        }
                        $availableDocumentItems = collect($documentPanelItems)->where('available', true)->values();
                    @endphp
                    <tr data-application-id="{{ $app->id }}">
                        <td style="color:var(--text-muted)">{{ $applications->firstItem() + $i }}</td>
                        <td><code style="font-size:.78rem;color:var(--primary)">{{ $app->ref_id }}</code></td>
                        <td><strong>{{ $app->full_name }}</strong>
                            <div style="font-size:.75rem;color:var(--text-muted)">{{ $app->sex }}, {{ $app->age }} yrs</div>
                        </td>
                        <td>{{ $app->barangay }}</td>
                        <td>
                            <span class="badge {{ $app->spes_status === 'new' ? 'badge-new' : 'badge-baby' }}">
                                {{ $app->spes_status === 'new' ? 'New' : 'SPES Baby' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $app->statusBadgeClass() }}" data-application-status>
                                <x-icon class="fa-solid {{ $app->status === 'approved' ? 'fa-circle-check' : ($app->status === 'denied' ? 'fa-circle-xmark' : 'fa-clock') }}" />
                                {{ $app->statusLabel() }}
                            </span>
                        </td>
                        <td style="min-width:175px;">
                            <div class="document-summary">
                                <div class="document-summary-chips">
                                    @foreach($availableDocumentItems->take(2) as $documentItem)
                                        @php
                                            $extension = strtolower(pathinfo($documentItem['filename'], PATHINFO_EXTENSION));
                                        @endphp
                                        <span class="document-chip" title="{{ $documentItem['category'] }} — {{ $documentItem['filename'] }}">
                                            <span class="document-file-icon">{{ $extension === 'pdf' ? 'PDF' : 'FILE' }}</span>
                                            <span class="document-chip-label">{{ $documentItem['category'] }}</span>
                                        </span>
                                    @endforeach
                                    @if($availableDocumentItems->count() > 2)
                                        <span class="document-chip">+{{ $availableDocumentItems->count() - 2 }} {{ __('more') }}</span>
                                    @elseif($availableDocumentItems->isEmpty())
                                        <span class="document-chip">{{ __('No files available') }}</span>
                                    @endif
                                </div>
                                <button type="button" class="document-panel-trigger" data-document-panel data-no-request-loading
                                        data-document-title="{{ $app->full_name }} — {{ __('Documents') }}"
                                        data-document-applicant="{{ $app->full_name }}"
                                        data-document-items="{{ json_encode($documentPanelItems, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}">
                                    {{ __('View Documents (:count)', ['count' => $availableDocumentItems->count()]) }}
                                </button>
                            </div>
                        </td>
                        <td style="font-size:.78rem;color:var(--text-muted)">@adminDate($app->created_at)</td>
                        <td>
                            <a href="{{ route('admin.applications.show', $app) }}" class="btn btn-info btn-sm" style="margin-bottom:4px;">
                                <x-icon class="fa-solid fa-eye" /> View
                            </a>
                            @if($app->status === 'pending' && !$approvalCapacity['full'])
                                <form method="POST" action="{{ route('admin.applications.approve', $app) }}" style="display:inline;"
                                      data-application-decision data-no-request-loading data-unsaved-guard="off">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm" data-swal-confirm="{{ __('Approve this application?') }}" style="margin-bottom:4px;">
                                        <x-icon class="fa-solid fa-check" /> Approve
                                        <span data-decision-spinner hidden class="request-loading-spinner" aria-hidden="true"></span>
                                    </button>
                                    <div data-decision-feedback role="alert" hidden></div>
                                </form>
                            @elseif($app->status === 'pending' && $approvalCapacity['full'])
                                <button type="button" class="btn btn-success btn-sm" disabled aria-disabled="true" title="Approval limit reached">
                                    <x-icon class="fa-solid fa-lock" aria-hidden="true" /> Approval limit reached
                                </button>
                            @endif
                            @if($app->status === 'pending')
                                <button type="button" class="btn btn-danger btn-sm js-deny-application"
                                    data-no-request-loading
                                    data-deny-url="{{ route('admin.applications.deny', $app) }}"
                                    data-application-id="{{ $app->id }}"
                                    data-applicant-name="{{ $app->full_name }}"
                                    data-documents="{{ json_encode($availableDenialDocuments, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}">
                                    <x-icon class="fa-solid fa-xmark" /> Deny
                                </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @php
            $denialApplication = $applications->getCollection()->firstWhere('id', (int) old('denial_application_id'));
            $showDenialModal = $denialApplication && ($errors->has('admin_comment') || $errors->has('admin_comment_document'));
        @endphp
        <div id="denyApplicationModal" role="dialog" aria-modal="true" aria-labelledby="denyApplicationTitle"
             @if(!$showDenialModal) hidden @endif
             style="position:fixed;inset:0;z-index:3000;background:rgba(0,0,0,.55);display:{{ $showDenialModal ? 'flex' : 'none' }};align-items:center;justify-content:center;padding:20px;">
            <section style="width:min(520px,100%);background:#fff;border-radius:12px;padding:22px;box-shadow:0 16px 48px rgba(0,0,0,.25);">
                <h2 id="denyApplicationTitle" style="margin:0 0 8px;">{{ __('Deny Application') }}</h2>
                <p style="margin:0 0 16px;">{{ __('Applicant') }}: <strong id="denyApplicantName">{{ $denialApplication?->full_name }}</strong></p>
                <form id="denyApplicationForm" method="POST" action="{{ $denialApplication ? route('admin.applications.deny', $denialApplication) : '#' }}"
                      data-application-decision data-no-request-loading data-unsaved-guard="off"
                      data-swal-confirm="{{ __('Deny this application?') }}">
                    @csrf
                    <input type="hidden" name="denial_application_id" id="denialApplicationId" value="{{ old('denial_application_id', $denialApplication?->id) }}">
                    <label for="denyReason" style="display:block;font-weight:600;margin-bottom:6px;">{{ __('Required reason for denial') }}</label>
                    <textarea id="denyReason" name="admin_comment" rows="4" maxlength="2000" required
                              style="width:100%;padding:10px;border:1px solid var(--border);border-radius:7px;font:inherit;resize:vertical;">{{ old('admin_comment') }}</textarea>
                    @error('admin_comment')
                        <div role="alert" style="color:var(--danger);font-size:.8rem;margin-top:5px;">{{ $message }}</div>
                    @enderror
                    <label for="denyRelatedDocument" style="display:block;font-weight:600;margin:12px 0 6px;">{{ __('Related document (optional)') }}</label>
                    <select id="denyRelatedDocument" name="admin_comment_document" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:7px;">
                        <option value="">{{ __('Not document-specific') }}</option>
                        @if($denialApplication)
                            @foreach($applicationDocumentLabels as $documentKey => $documentLabel)
                                @if(filled($denialApplication->{$documentKey}) && \Illuminate\Support\Facades\Storage::disk('local')->exists($denialApplication->{$documentKey}))
                                    <option value="{{ $documentKey }}" @selected(old('admin_comment_document') === $documentKey)>{{ __($documentLabel) }}</option>
                                @endif
                            @endforeach
                        @endif
                    </select>
                    @error('admin_comment_document')
                        <div role="alert" style="color:var(--danger);font-size:.8rem;margin-top:5px;">{{ $message }}</div>
                    @enderror
                    <p data-decision-feedback role="alert" hidden style="margin-top:8px;color:var(--danger);font-size:.8rem;"></p>
                    <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:18px;">
                        <button type="button" class="btn btn-outline" id="cancelDenial" data-denial-cancel>{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-danger">{{ __('Confirm Denial') }} <span data-decision-spinner hidden class="request-loading-spinner" aria-hidden="true"></span></button>
                    </div>
                </form>
            </section>
        </div>

        {{-- Pagination --}}
        <div class="pagination">
            {{ $applications->appends(request()->query())->links('pagination::simple-bootstrap-4') }}
        </div>
        @endif
    </div>
</div>
<script>
    (() => {
        const modal = document.getElementById('denyApplicationModal');
        const form = document.getElementById('denyApplicationForm');
        const applicantName = document.getElementById('denyApplicantName');
        const applicationId = document.getElementById('denialApplicationId');
        const reason = document.getElementById('denyReason');
        const documentSelect = document.getElementById('denyRelatedDocument');
        let lastOpenedApplicationId = applicationId.value;
        const closeModal = () => {
            if (form.dataset.decisionState === 'submitting') return;
            modal.style.display = 'none';
            modal.hidden = true;
        };
        const openModal = (button) => {
            const documents = JSON.parse(button.dataset.documents || '{}');
            if (lastOpenedApplicationId && lastOpenedApplicationId !== button.dataset.applicationId) {
                reason.value = '';
                documentSelect.value = '';
            }
            form.action = button.dataset.denyUrl;
            applicantName.textContent = button.dataset.applicantName;
            applicationId.value = button.dataset.applicationId;
            lastOpenedApplicationId = button.dataset.applicationId;
            form.querySelector('[data-decision-feedback]').hidden = true;
            form.querySelector('[data-decision-feedback]').textContent = '';
            documentSelect.replaceChildren(new Option(@json(__('Not document-specific')), ''));
            Object.entries(documents).forEach(([key, label]) => {
                documentSelect.add(new Option(label, key));
            });
            modal.hidden = false;
            modal.style.display = 'flex';
            reason.focus();
        };
        document.querySelectorAll('.js-deny-application').forEach((button) => {
            button.addEventListener('click', () => openModal(button));
        });
        document.getElementById('cancelDenial').addEventListener('click', closeModal);
        modal.addEventListener('click', (event) => {
            if (event.target === modal && form.dataset.decisionState !== 'submitting') closeModal();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden) {
                if (form.dataset.decisionState === 'submitting') {
                    event.preventDefault();
                    return;
                }
                closeModal();
            }
        });
        @if($showDenialModal)
            const retryButton = document.querySelector(`.js-deny-application[data-application-id="{{ $denialApplication->id }}"]`);
            if (retryButton) {
                openModal(retryButton);
                documentSelect.value = @json(old('admin_comment_document'));
            }
        @endif
    })();
</script>
@endsection
