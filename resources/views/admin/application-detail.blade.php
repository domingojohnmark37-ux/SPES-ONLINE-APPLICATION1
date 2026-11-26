@extends('layouts.admin')

@section('title', 'Application — '.$application->full_name)
@section('page-title', 'Application Detail')
@section('page-sub', $application->ref_id)

@section('styles')
<style>
    .feedback-suggestion-picker { position:relative; margin-bottom:12px; }
    .feedback-suggestion-picker summary { display:flex; align-items:center; justify-content:space-between; gap:12px; min-height:42px; padding:10px 12px; border:1px solid var(--border); border-radius:8px; background:var(--white); color:var(--text); cursor:pointer; font-size:.82rem; font-weight:650; list-style:none; }
    .feedback-suggestion-picker summary::-webkit-details-marker { display:none; }
    .feedback-suggestion-picker summary::after { content:""; width:8px; height:8px; flex:0 0 8px; border-right:2px solid currentColor; border-bottom:2px solid currentColor; transform:rotate(45deg) translateY(-2px); transition:transform .15s; }
    .feedback-suggestion-picker[open] summary { border-color:var(--primary); border-radius:8px 8px 0 0; }
    .feedback-suggestion-picker[open] summary::after { transform:rotate(225deg) translate(-2px,-1px); }
    .feedback-suggestion-list { position:absolute; top:100%; right:0; left:0; z-index:5; max-height:240px; overflow-y:auto; overscroll-behavior:contain; scrollbar-gutter:stable; border:1px solid var(--primary); border-top:0; border-radius:0 0 8px 8px; background:var(--white); box-shadow:0 10px 24px rgba(0,0,0,.14); }
    .feedback-suggestion-option { display:block; width:100%; padding:10px 12px; border:0; border-bottom:1px solid var(--border); background:var(--white); color:var(--text); cursor:pointer; font:inherit; font-size:.8rem; line-height:1.45; text-align:left; white-space:normal; overflow-wrap:anywhere; }
    .feedback-suggestion-option:last-child { border-bottom:0; }
    .feedback-suggestion-option:hover,.feedback-suggestion-option:focus-visible { background:var(--bg); color:var(--primary); outline:none; }
    .feedback-suggestion-help { margin:0 0 7px; color:var(--text-muted); font-size:.75rem; line-height:1.45; }
    .feedback-comment-input { display:block; width:100%; min-width:0; max-width:100%; }
    @media(max-width:600px) { .feedback-suggestion-list { max-height:200px; } }
</style>
@endsection

@section('content')
@php
    $feedbackSuggestions = [
        'Invalid email address',
        'Duplicate application',
        'Incorrect date of birth',
        'Unreachable contact number',
        'Required signature or school certification is missing',
        'Certificate belongs to a previous school year when a current one is required',
        'Text is blurry or unreadable',
        'Name or date of birth does not match the application',
        'Wrong document uploaded',
        'Cropped or incomplete document',
    ];
    $currentFeedback = old('admin_comment', $application->admin_comment);
    $selectedFeedbackSuggestion = in_array($currentFeedback, $feedbackSuggestions, true) ? $currentFeedback : null;
@endphp

<div style="margin-bottom:16px;">
    <a href="{{ route('admin.applications.index') }}" class="btn btn-outline btn-sm">
        <x-icon class="fa-solid fa-arrow-left" /> Back to List
    </a>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">

    {{-- Left: Application Form Data --}}
    <div>
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <h2><x-icon class="fa-solid fa-id-card" /> Personal Information</h2>
                <span class="badge {{ $application->statusBadgeClass() }}" data-application-status-label>{{ $application->statusLabel() }}</span>
            </div>
            <div class="detail-grid">
                <div class="detail-item"><div class="detail-label">Full Name</div><div class="detail-value">{{ $application->full_name }}</div></div>
                <div class="detail-item"><div class="detail-label">Sex</div><div class="detail-value">{{ $application->sex }}</div></div>
                <div class="detail-item"><div class="detail-label">Birthday</div><div class="detail-value">@adminDate($application->birthday)</div></div>
                <div class="detail-item"><div class="detail-label">Age</div><div class="detail-value">{{ $application->age }} years old</div></div>
                <div class="detail-item"><div class="detail-label">Barangay</div><div class="detail-value">{{ $application->barangay }}</div></div>
                <div class="detail-item"><div class="detail-label">Civil Status</div><div class="detail-value">{{ $application->civil_status }}</div></div>
                <div class="detail-item"><div class="detail-label">Parent Status</div><div class="detail-value">{{ $application->parent_status }}</div></div>
                <div class="detail-item"><div class="detail-label">Educational Attainment</div><div class="detail-value">{{ $application->education }}</div></div>
                <div class="detail-item"><div class="detail-label">Grade/Year Level</div><div class="detail-value">{{ $application->grade_year_level ?? '—' }}</div></div>
                <div class="detail-item"><div class="detail-label">SPES Beneficiary Type</div>
                    <div class="detail-value">
                        <span class="badge {{ $application->spes_status === 'new' ? 'badge-new' : 'badge-baby' }}">
                            {{ $application->spes_status === 'new' ? 'New (1st time)' : 'SPES Baby (2nd/3rd time)' }}
                        </span>
                    </div>
                </div>
                <div class="detail-item"><div class="detail-label">Facebook Profile</div><div class="detail-value">{{ $application->facebook ?? '—' }}</div></div>
                <div class="detail-item"><div class="detail-label">Mother's Name</div><div class="detail-value">{{ $application->mother_name }}</div></div>
                <div class="detail-item"><div class="detail-label">Mother's Occupation</div><div class="detail-value">{{ $application->mother_occupation }}</div></div>
                <div class="detail-item"><div class="detail-label">Mother's Contact Number</div><div class="detail-value">{{ $application->mother_contact_no }}</div></div>
                <div class="detail-item"><div class="detail-label">Father / Guardian</div><div class="detail-value">{{ $application->father_guardian_name }}</div></div>
                <div class="detail-item"><div class="detail-label">Father's Occupation</div><div class="detail-value">{{ $application->father_occupation }}</div></div>
                <div class="detail-item"><div class="detail-label">Father's Contact Number</div><div class="detail-value">{{ $application->father_contact_no }}</div></div>
            </div>
        </div>

        {{-- Uploaded Documents --}}
        <div class="card" style="margin-bottom:20px;">
            @php
                $detailDocumentItems = collect([
                    ['label' => 'Birth Certificate', 'key' => 'birth_certificate'],
                    ['label' => 'Certificate of Enrollment', 'key' => 'certificate_enrollment'],
                    ['label' => 'Certificate of Grades', 'key' => 'certificate_grade'],
                    ['label' => 'Application Letter', 'key' => 'application_letter'],
                    ['label' => 'Certificate of Indigency', 'key' => 'indigency'],
                ])->map(function (array $doc) use ($application): array {
                    $path = $application->{$doc['key']};
                    $available = filled($path) && \Illuminate\Support\Facades\Storage::disk('local')->exists($path);

                    return [
                        'category' => __($doc['label']),
                        'filename' => $available ? ($application->document_original_names[$doc['key']] ?? basename($path)) : null,
                        'available' => $available,
                        'status_label' => $available ? __('On file') : (filled($path) ? __('File missing from private storage') : __('Not uploaded')),
                        'url' => $available ? route('applications.document.stream', ['application' => $application->id, 'document' => $doc['key']]) : null,
                        'download_url' => $available ? route('applications.document.stream', ['application' => $application->id, 'document' => $doc['key']]) : null,
                    ];
                })->concat($application->additionalRequirementSubmissions->sortBy('file_number')->map(function ($submission) use ($application): array {
                    $available = \Illuminate\Support\Facades\Storage::disk('local')->exists($submission->file_path);

                    return [
                        'category' => $submission->requirement->name.' — '.__('File :number', ['number' => $submission->file_number]),
                        'filename' => $submission->original_name,
                        'submitted_by' => $application->full_name,
                        'available' => $available,
                        'status_label' => $available ? __('On file') : __('File missing from private storage'),
                        'url' => $available ? route('applications.additional-requirements.submission', ['application' => $application->id, 'additionalRequirement' => $submission->additional_requirement_id, 'submission' => $submission->id]) : null,
                        'download_url' => $available ? route('applications.additional-requirements.submission', ['application' => $application->id, 'additionalRequirement' => $submission->additional_requirement_id, 'submission' => $submission->id]) : null,
                    ];
                }))->values();
                $availableDetailDocumentCount = $detailDocumentItems->where('available', true)->count();
            @endphp
            <div class="card-header"><h2>Uploaded Documents</h2></div>
            <div class="card-body">
                <div class="document-summary">
                    <div class="document-summary-chips">
                        @foreach($detailDocumentItems->where('available', true)->take(3) as $documentItem)
                            @php
                                $extension = strtolower(pathinfo($documentItem['filename'], PATHINFO_EXTENSION));
                            @endphp
                            <span class="document-chip" title="{{ $documentItem['category'] }} — {{ $documentItem['filename'] }}">
                                <span class="document-file-icon">{{ $extension === 'pdf' ? 'PDF' : 'FILE' }}</span>
                                <span class="document-chip-label">{{ $documentItem['category'] }}</span>
                            </span>
                        @endforeach
                        @if($availableDetailDocumentCount > 3)
                            <span class="document-chip">+{{ $availableDetailDocumentCount - 3 }} {{ __('more') }}</span>
                        @elseif($availableDetailDocumentCount === 0)
                            <span class="document-chip">{{ __('No files available') }}</span>
                        @endif
                    </div>
                    <button type="button" class="document-panel-trigger" data-document-panel data-no-request-loading
                            data-document-title="{{ $application->full_name }} — {{ __('Documents') }}"
                            data-document-applicant="{{ $application->full_name }}"
                            data-document-items="{{ json_encode($detailDocumentItems, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}">
                        {{ __('View Documents (:count)', ['count' => $availableDetailDocumentCount]) }}
                    </button>
                </div>
            </div>
        </div>
    </div>

<div id="documentModal" style="position:fixed; inset:0; background:rgba(0,0,0,.55); display:none; align-items:center; justify-content:center; z-index:2000; padding:24px;">
    <div style="width:min(1100px, 92vw); max-height:90vh; background:#fff; border-radius:14px; box-shadow:0 20px 60px rgba(0,0,0,.25); overflow:hidden; border:1px solid var(--border);">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-bottom:1px solid var(--border); background:#f8fafb;">
            <strong id="documentModalTitle" style="font-size:1rem; color:var(--primary);">Document Preview</strong>
            <button type="button" onclick="closeDocumentModal()" class="btn btn-outline btn-sm" aria-label="Close preview">
                <x-icon class="fa-solid fa-xmark" /> Close
            </button>
        </div>
        <div style="padding:12px; background:#f5f5f5; height:80vh;">
            <iframe id="documentFrame" title="Document Preview" style="width:100%; height:100%; border:none; background:#fff; border-radius:8px;"></iframe>
        </div>
    </div>
</div>

<script>
    function openDocumentModal(url, title, trigger) {
        const modal = document.getElementById('documentModal');
        const frame = document.getElementById('documentFrame');
        const titleEl = document.getElementById('documentModalTitle');
        const request = window.requestLoading.start(trigger);

        titleEl.textContent = title + ' Preview';
        modal.style.display = 'flex';

        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error(`Preview request failed (${response.status}).`);
                return response.blob();
            })
            .then(blob => {
                const objectUrl = URL.createObjectURL(blob);
                frame.src = objectUrl;
                request.success('Document preview loaded.');
            })
            .catch(error => {
                console.error('Document preview fetch failed; trying the document stream directly.', error);
                frame.onload = () => {
                    frame.onload = null;
                    frame.onerror = null;
                    request.success('Document preview loaded.');
                };
                frame.onerror = () => {
                    frame.onload = null;
                    frame.onerror = null;
                    request.error('The document preview could not be loaded. Please try again.');
                };
                frame.src = url;
            });
    }

    function closeDocumentModal() {
        const modal = document.getElementById('documentModal');
        const frame = document.getElementById('documentFrame');
        modal.style.display = 'none';
        if (frame.src && frame.src.startsWith('blob:')) {
            URL.revokeObjectURL(frame.src);
        }
        frame.src = 'about:blank';
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeDocumentModal();
        }
    });
</script>

    {{-- Right: Actions & Comments --}}
    <div>
        {{-- Submission info --}}
        <div class="card" style="margin-bottom:16px;" data-application-decision-actions>
            <div class="card-header"><h2><x-icon class="fa-solid fa-info-circle" /> Submission Info</h2></div>
            <div class="card-body" style="font-size:.875rem;">
                <p><strong>Reference ID:</strong><br><code>{{ $application->ref_id }}</code></p>
                <p style="margin-top:10px;"><strong>Submitted:</strong><br>@adminDate($application->created_at, true)</p>
                <p style="margin-top:10px;"><strong>Applicant Email:</strong><br>{{ $application->user->email ?? '—' }}</p>
            </div>
        </div>

        {{-- Actions --}}
        @if($application->status === 'pending')
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header"><h2><x-icon class="fa-solid fa-gavel" /> Decision</h2></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:10px;">
                @if(!$application->review_started_at)
                    <form method="POST" action="{{ route('admin.applications.review', $application) }}">
                        @csrf
                        <button type="submit" class="btn btn-outline" style="width:100%;">
                            <x-icon class="fa-solid fa-magnifying-glass" aria-hidden="true" /> {{ __('Mark as Under Review') }}
                        </button>
                    </form>
                @endif
                @if($approvalCapacity['limit'])
                    <div class="alert {{ $approvalCapacity['full'] ? 'alert-danger' : 'alert-info' }}" role="status">
                        <x-icon class="fa-solid {{ $approvalCapacity['full'] ? 'fa-lock' : 'fa-chart-simple' }}" aria-hidden="true" />
                        <span>{{ $approvalCapacity['approved'] }} of {{ $approvalCapacity['limit'] }} approved this season.
                            @if($approvalCapacity['full'])
                                The limit is full; approval is disabled.
                            @else
                                {{ $approvalCapacity['limit'] - $approvalCapacity['approved'] }} approval slot(s) remaining.
                            @endif
                        </span>
                    </div>
                @endif
                @if(!$approvalCapacity['full'])
                    <form method="POST" action="{{ route('admin.applications.approve', $application) }}"
                          data-application-decision data-no-request-loading data-unsaved-guard="off">
                        @csrf
                        <button type="submit" class="btn btn-success" style="width:100%;" data-swal-confirm="{{ __('Approve this application?') }}">
                            <x-icon class="fa-solid fa-circle-check" /> Approve Application
                            <span data-decision-spinner hidden class="request-loading-spinner" aria-hidden="true"></span>
                        </button>
                        <div data-decision-feedback role="alert" hidden></div>
                    </form>
                @else
                    <button type="button" class="btn btn-success" style="width:100%;" disabled aria-disabled="true">
                        <x-icon class="fa-solid fa-lock" aria-hidden="true" /> Approval limit reached
                    </button>
                @endif
                <form method="POST" action="{{ route('admin.applications.deny', $application) }}"
                      data-application-decision data-no-request-loading data-unsaved-guard="off"
                      data-swal-confirm="{{ __('Deny this application?') }}">
                    @csrf
                    <input type="hidden" name="denial_application_id" value="{{ $application->id }}">
                    <label for="decline_reason" style="display:block;margin-bottom:6px;font-size:.82rem;font-weight:600;">{{ __('Specific reason for declining') }}</label>
                    <textarea id="decline_reason" name="admin_comment" rows="3" maxlength="2000" required
                        placeholder="{{ __('Explain why this application is being declined.') }}"
                        style="width:100%;padding:10px;border:1px solid var(--border);border-radius:7px;font:inherit;resize:vertical;">{{ old('admin_comment') }}</textarea>
                    @error('admin_comment')
                        <div style="color:var(--danger);font-size:.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                    <label for="decline_document" style="display:block;margin:10px 0 6px;font-size:.82rem;font-weight:600;">{{ __('Related uploaded document (optional)') }}</label>
                    <select id="decline_document" name="admin_comment_document" style="width:100%;padding:10px;border:1px solid var(--border);border-radius:7px;">
                        <option value="">{{ __('Not document-specific') }}</option>
                        @foreach([
                            'birth_certificate' => 'Birth Certificate',
                            'certificate_enrollment' => 'Certificate of Enrollment',
                            'certificate_grade' => 'Certificate of Grades',
                            'application_letter' => 'Application Letter',
                            'indigency' => 'Certificate of Indigency',
                        ] as $documentKey => $documentLabel)
                            @if(filled($application->{$documentKey}) && \Illuminate\Support\Facades\Storage::disk('local')->exists($application->{$documentKey}))
                                <option value="{{ $documentKey }}" @selected(old('admin_comment_document') === $documentKey)>{{ __($documentLabel) }}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('admin_comment_document')
                        <div style="color:var(--danger);font-size:.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                    <button type="submit" class="btn btn-danger" style="width:100%;">
                        <x-icon class="fa-solid fa-circle-xmark" /> {{ __('Decline Application') }}
                        <span data-decision-spinner hidden class="request-loading-spinner" aria-hidden="true"></span>
                    </button>
                    <div data-decision-feedback role="alert" hidden></div>
                </form>
            </div>
        </div>
        @endif

        {{-- Admin Comment --}}
        <div class="card">
            <div class="card-header"><h2><x-icon class="fa-solid fa-comment-dots" /> Admin Comment / Feedback</h2></div>
            <div class="card-body">
                @if($application->admin_comment)
                    <div style="background:#f5f7fa;border-left:4px solid var(--primary);padding:12px;border-radius:6px;margin-bottom:14px;font-size:.875rem;">
                        {{ $application->admin_comment }}
                    </div>
                @endif
                <form method="POST" action="{{ route('admin.applications.comment', $application) }}">
                    @csrf
                    <p class="feedback-suggestion-help">Choose a common issue to add it to the feedback, or write a custom comment below.</p>
                    <details class="feedback-suggestion-picker" data-feedback-picker>
                        <summary>
                            <span data-feedback-selected>{{ $selectedFeedbackSuggestion ?? 'Choose an applicant issue' }}</span>
                        </summary>
                        <div class="feedback-suggestion-list" role="group" aria-label="Applicant issues">
                            @foreach($feedbackSuggestions as $suggestion)
                                <button class="feedback-suggestion-option" type="button" data-feedback-option="{{ $suggestion }}">{{ $suggestion }}</button>
                            @endforeach
                        </div>
                    </details>
                    <textarea class="feedback-comment-input" name="admin_comment" rows="4" placeholder="Write feedback or notes for the applicant…"
                        style="padding:10px;border:1.5px solid var(--border);border-radius:7px;font-size:.875rem;resize:vertical;font-family:inherit;outline:none;"
                    >{{ $currentFeedback }}</textarea>
                    @error('admin_comment')
                        <div style="color:var(--danger);font-size:.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                    <button type="submit" class="btn btn-primary" style="margin-top:10px;width:100%;">
                        <x-icon class="fa-solid fa-floppy-disk" /> Save Comment
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    (() => {
        const picker = document.querySelector('[data-feedback-picker]');
        const comment = document.querySelector('.feedback-comment-input');
        const selectedLabel = picker?.querySelector('[data-feedback-selected]');
        if (!picker || !comment || !selectedLabel) return;

        picker.querySelectorAll('[data-feedback-option]').forEach((option) => {
            option.addEventListener('click', () => {
                const suggestion = option.dataset.feedbackOption;
                const existingLines = comment.value.split(/\r?\n/).map((line) => line.trim());
                if (!existingLines.includes(suggestion)) {
                    comment.value = comment.value.trim()
                        ? `${comment.value.trim()}\n${suggestion}`
                        : suggestion;
                }
                selectedLabel.textContent = option.dataset.feedbackOption;
                picker.open = false;
                comment.dispatchEvent(new Event('input', { bubbles: true }));
                comment.focus();
            });
        });
    })();
</script>
@endsection
