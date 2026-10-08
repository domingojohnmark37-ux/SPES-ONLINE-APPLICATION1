<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Additional Requirements') }} — {{ __('SPES Portal') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <x-applicant-text-styles />
    <style>
        :root {
            --primary:#8B0000;
            --primary-dark:#660000;
            --accent:#FFD700;
            --bg:#f0f2f5;
            --white:#fff;
            --text:#212121;
            --text-muted:#6b7280;
            --border:#e0e0e0;
            --shadow:0 2px 12px rgba(0,0,0,.08);
            --sidebar-w:260px;
        }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bg); color:var(--text); font-family:Inter,"Plus Jakarta Sans",system-ui,sans-serif; }
        .topbar { position:fixed; z-index:90; top:0; right:0; left:var(--sidebar-w); display:flex; align-items:center; height:62px; padding:0 24px; border-bottom:1px solid var(--border); background:var(--white); }
        .topbar h1 { margin:0; color:var(--type-primary-color); font-size:var(--type-title); font-weight:700; }
        .topbar p { margin:3px 0 0; color:var(--type-secondary-color); font-size:var(--type-caption); }
        .hamburger { display:none; margin-right:12px; border:0; background:transparent; color:var(--primary); font-size:1.1rem; cursor:pointer; }
        .page-wrapper { min-height:100vh; margin-left:var(--sidebar-w); padding:78px 20px 24px; }
        .page-content { display:grid; gap:16px; max-width:1180px; margin:0 auto; }
        .intro { margin:0; color:var(--type-secondary-color); font-size:var(--type-secondary); line-height:1.6; }
        .panel { min-width:0; overflow:hidden; border:1px solid var(--border); border-radius:12px; background:var(--white); box-shadow:var(--shadow); }
        .panel-header { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:18px 20px; border-bottom:1px solid var(--border); }
        .panel-header h2 { margin:0; color:var(--type-primary-color); font-size:var(--type-section); }
        .panel-header p { margin:4px 0 0; color:var(--type-secondary-color); font-size:var(--type-caption); }
        .progress-summary { display:flex; align-items:center; gap:16px; padding:18px 20px; border-bottom:1px solid var(--border); }
        .progress-icon { display:grid; width:46px; height:46px; flex:0 0 46px; place-items:center; border-radius:12px; background:rgba(139,0,0,.08); color:var(--primary); font-size:1.2rem; }
        .progress-copy { flex:1; min-width:0; }
        .progress-copy strong { display:block; color:var(--type-primary-color); font-size:var(--type-secondary); }
        .progress-copy span { display:block; margin-top:3px; color:var(--type-secondary-color); font-size:var(--type-caption); }
        .progress-count { color:var(--type-primary-color); font-size:var(--type-secondary); font-weight:700; white-space:nowrap; }
        .progress-track { height:8px; overflow:hidden; margin:0 20px 20px; border-radius:99px; background:rgba(107,114,128,.16); }
        .progress-fill { height:100%; border-radius:inherit; background:var(--primary); transition:width .2s ease; }
        .table-wrap { overflow-x:auto; }
        .requirements-table { width:100%; border-collapse:collapse; text-align:left; }
        .requirements-table th { padding:12px 20px; background:rgba(107,114,128,.06); color:var(--type-caption-color); font-size:var(--type-caption); font-weight:700; }
        .requirements-table td { padding:14px 20px; border-top:1px solid var(--border); color:var(--type-secondary-color); font-size:var(--type-caption); vertical-align:middle; }
        .document-name { display:flex; align-items:center; gap:11px; color:var(--type-primary-color); font-size:var(--type-secondary); font-weight:650; }
        .document-description { margin:4px 0 0 45px; color:var(--type-caption-color); font-size:var(--type-caption); line-height:1.4; }
        .document-deadline { margin:7px 0 0 45px; color:#806000; font-size:var(--type-caption); font-weight:650; line-height:1.45; }
        .document-deadline i { margin-right:4px; }
        .download-link { display:inline-flex; min-height:34px; align-items:center; justify-content:center; gap:7px; margin:0 0 7px; padding:7px 10px; border:1px solid var(--primary); border-radius:7px; background:transparent; color:var(--primary); font-size:var(--type-caption); font-weight:700; text-decoration:none; white-space:nowrap; }
        .download-link:hover,.download-link:focus-visible { background:rgba(139,0,0,.08); }
        .template-locked { display:block; margin-bottom:7px; color:var(--type-caption-color); font-size:var(--type-caption); }
        .approved-next-steps { display:flex; align-items:flex-start; gap:12px; padding:16px 18px; border:1px solid #e7c85b; border-left:5px solid #c99400; border-radius:11px; background:#fffbed; color:#514a36; font-size:var(--type-secondary); line-height:1.6; }
        .approved-next-steps > i { margin-top:3px; color:#a57900; font-size:1.1rem; }
        .approved-next-steps strong { color:#5d4700; }
        html[data-theme="dark"] .approved-next-steps { border-color:#806d35; background:#3b3422; color:#e2d8b8; }
        html[data-theme="dark"] .approved-next-steps strong { color:#ffe18a; }
        .application-capacity-popup { position:fixed; right:22px; bottom:22px; z-index:1300; display:flex; width:min(460px,calc(100vw - 28px)); align-items:flex-start; gap:13px; padding:17px 18px; border:1px solid rgba(198,40,40,.2); border-left:5px solid #c62828; border-radius:12px; background:var(--white); color:var(--text); box-shadow:0 16px 45px rgba(17,24,39,.22); }
        .application-capacity-popup[hidden] { display:none; }
        .application-capacity-popup > i { margin-top:2px; color:#c62828; font-size:1.1rem; }
        .application-capacity-copy { flex:1; min-width:0; }
        .application-capacity-copy strong { display:block; margin-bottom:5px; }
        .application-capacity-copy p { margin:0; color:var(--text-muted); font-size:var(--type-caption); line-height:1.5; }
        .application-capacity-popup button { display:grid; width:30px; height:30px; flex:0 0 30px; place-items:center; border:0; border-radius:7px; background:transparent; color:var(--text-muted); cursor:pointer; }
        @media(prefers-color-scheme:dark) {
            html[data-theme="system"] .approved-next-steps { border-color:#806d35; background:#3b3422; color:#e2d8b8; }
            html[data-theme="system"] .approved-next-steps strong { color:#ffe18a; }
        }
        .document-icon { display:grid; width:34px; height:34px; flex:0 0 34px; place-items:center; border-radius:9px; background:rgba(139,0,0,.08); color:var(--primary); }
        .status { display:inline-flex; align-items:center; gap:6px; padding:5px 9px; border-radius:99px; font-size:var(--type-caption); font-weight:650; white-space:nowrap; }
        .status-submitted { background:#e8f5e9; color:#256c32; }
        .status-required { background:#fff4bf; color:#725600; }
        .status-optional { background:rgba(107,114,128,.1); color:var(--type-secondary-color); }
        .action { display:inline-flex; min-height:34px; align-items:center; justify-content:center; gap:7px; padding:7px 12px; border:1px solid var(--primary); border-radius:7px; background:var(--primary); color:#fff; font:inherit; font-size:var(--type-caption); font-weight:650; text-decoration:none; white-space:nowrap; }
        .action:hover,.action:focus-visible { background:var(--primary-dark); }
        .action-view { background:transparent; color:var(--primary); }
        .action-view:hover,.action-view:focus-visible { background:rgba(139,0,0,.08); color:var(--primary-dark); }
        .empty-state { display:flex; align-items:flex-start; gap:10px; margin:0 20px 18px; padding:12px 14px; border-radius:8px; background:rgba(212,167,44,.14); color:var(--type-secondary-color); font-size:var(--type-caption); line-height:1.5; }
        .empty-state i { margin-top:2px; color:#8a6800; }
        @media(max-width:768px) {
            .topbar { left:0; padding:0 14px; }
            .hamburger { display:block; }
            .page-wrapper { margin-left:0; padding:74px 12px 18px; }
        }
        @media(max-width:600px) {
            .topbar { height:56px; }
            .topbar h1 { font-size:var(--type-section); }
            .topbar p { display:none; }
            .page-wrapper { padding-top:68px; }
            .panel-header,.progress-summary { padding:14px; }
            .progress-track { margin-right:14px; margin-left:14px; }
            .requirements-table { min-width:640px; }
        }
    </style>
</head>
<body>
    <x-applicant-sidebar />
    <header class="topbar">
        <button class="hamburger" type="button" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false" aria-label="Toggle applicant navigation">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>
        <div>
            <h1>My Application</h1>
            <p>View and manage your application requirements</p>
        </div>
    </header>
    <main class="page-wrapper">
        <div class="page-content">
            <p class="intro">{{ __('Submit the documents requested by PESO. For each requirement listed here, upload a PDF, Word document, or image (JPG or PNG).') }}</p>
            @if($application?->status === 'approved')
                <aside class="approved-next-steps" aria-label="{{ __('Next steps for your proposed SPES application') }}">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    <div>
                        <strong>{{ __('Your application is approved as a proposed SPES applicant—not yet final program confirmation.') }}</strong>
                        <p>{{ __('Download each requested form, complete and sign it as instructed, then upload a clear photo or scanned copy here as soon as possible. After uploading, bring the completed printed forms to the PESO office in Lal-lo and follow the staff’s instructions for the next step. Check the due date shown with each requirement and follow any additional instructions from LGU Lal-lo PESO.') }}</p>
                        <p><strong>{{ __('Important:') }}</strong> {{ __('E-signatures and signatures printed or copied onto the form are not accepted. Sign each printed form by hand in ink before uploading a clear copy and bringing the signed printed form to the PESO office.') }}</p>
                    </div>
                </aside>
            @elseif($hasLockedRequirements)
                <aside class="approved-next-steps" aria-label="{{ __('Additional requirements access information') }}">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    <div>{{ __('Second-phase requirements will be available here after your application is approved.') }}</div>
                </aside>
            @endif
            @if(session('success'))
                <p class="empty-state" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>{{ session('success') }}</span></p>
            @endif
            <section class="panel" aria-labelledby="requirements-heading">
                <div class="panel-header">
                    <div>
                        <h2 id="requirements-heading"><i class="fa-solid fa-folder-open" aria-hidden="true"></i> {{ __('Additional Requirements') }}</h2>
                        <p>{{ __('Only documents requested by PESO are listed here.') }}</p>
                    </div>
                    @if(!$application)
                        <a class="action" href="{{ route('applications.create') }}"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> {{ __('Apply Now') }}</a>
                    @elseif($application->status === 'denied')
                        <a class="action" href="{{ route('applications.edit') }}"><i class="fa-solid fa-rotate-right" aria-hidden="true"></i> {{ __('Update Application') }}</a>
                    @endif
                </div>
                <div class="progress-summary" aria-label="{{ $submittedRequirements }} of {{ $totalRequirements }} documents submitted">
                    <div class="progress-icon" aria-hidden="true"><i class="fa-solid fa-file-circle-check"></i></div>
                    <div class="progress-copy">
                        <strong>{{ __('Admin-requested documents') }}</strong>
                            <span>{{ __(':submitted of :total requested documents submitted', ['submitted' => $submittedRequirements, 'total' => $totalRequirements]) }}</span>
                    </div>
                    <div class="progress-count">{{ $submittedRequirements }}/{{ $totalRequirements }}</div>
                </div>
                <div class="progress-track" role="progressbar" aria-label="{{ __('Documents submitted') }}" aria-valuemin="0" aria-valuemax="{{ $totalRequirements }}" aria-valuenow="{{ $submittedRequirements }}">
                    <div class="progress-fill" style="width:{{ $totalRequirements ? $submittedRequirements / $totalRequirements * 100 : 0 }}%"></div>
                </div>
                <div class="table-wrap">
                    <table class="requirements-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Document') }}</th>
                                <th scope="col">{{ __('Status') }}</th>
                                <th scope="col">{{ __('Submitted') }}</th>
                                <th scope="col">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($additionalRequirements as $requirement)
                                @php($submission = $requirement->submissions->first())
                                <tr>
                                    <td>
                                        <div class="document-name">
                                            <span class="document-icon" aria-hidden="true"><i class="fa-regular fa-file-lines"></i></span>
                                            {{ $requirement->name }}
                                            @if($requirement->is_required)
                                                <span aria-label="{{ __('Required') }}" title="{{ __('Required') }}">*</span>
                                            @endif
                                        </div>
                                        @if($requirement->description)
                                            <p class="document-description">{{ __($requirement->description) }}</p>
                                        @endif
                                        @if($requirement->due_at)
                                            <p class="document-deadline"><i class="fa-regular fa-clock" aria-hidden="true"></i> {{ __('Due') }}: <time datetime="{{ $requirement->due_at->toIso8601String() }}">{{ $requirement->due_at->locale(app()->getLocale())->translatedFormat('F j, Y g:i A') }}</time></p>
                                        @else
                                            <p class="document-deadline">{{ __('Submit as soon as possible; follow any deadline announced by PESO.') }}</p>
                                        @endif
                                        @if($submission)
                                            <p class="document-description">{{ __('File') }}: {{ $submission->original_name }}</p>
                                        @endif
                                    </td>
                                    <td>
                                        @if($submission)
                                            <span class="status status-submitted"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> {{ __('Submitted') }}</span>
                                        @elseif($requirement->is_required)
                                            <span class="status status-required"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> {{ __('Required') }}</span>
                                        @else
                                            <span class="status status-optional"><i class="fa-solid fa-minus" aria-hidden="true"></i> {{ __('Optional') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $submission?->created_at?->format('M j, Y') ?? '—' }}</td>
                                    <td>
                                        @if($requirement->template_path)
                                            @if($requirement->isAvailableTo($application))
                                                <a class="download-link" href="{{ route('applicant.requirements.template', $requirement) }}">
                                                    <i class="fa-solid fa-download" aria-hidden="true"></i> {{ __('Download form') }}
                                                </a>
                                            @else
                                                <span class="template-locked"><i class="fa-solid fa-lock" aria-hidden="true"></i> {{ __('Available after approval') }}</span>
                                            @endif
                                        @endif
                                        @foreach($requirement->templates as $template)
                                            @if($requirement->isAvailableTo($application))
                                                <a class="download-link" href="{{ route('applicant.requirements.template-file', ['additionalRequirement' => $requirement, 'template' => $template]) }}">
                                                    <i class="fa-solid fa-download" aria-hidden="true"></i> {{ $template->original_name }}
                                                </a>
                                            @else
                                                <span class="template-locked"><i class="fa-solid fa-lock" aria-hidden="true"></i> {{ __('Available after approval') }}</span>
                                            @endif
                                        @endforeach
                                        @if($submission)
                                            <a class="action action-view" href="{{ route('applications.additional-requirements.document', ['application' => $application->id, 'additionalRequirement' => $requirement->id]) }}" target="_blank" rel="noopener">
                                                <i class="fa-solid fa-eye" aria-hidden="true"></i> {{ __('View') }}
                                            </a>
                                        @elseif($application && $approvalCapacityClosed && $application->status !== 'approved')
                                            <span class="template-locked"><i class="fa-solid fa-lock" aria-hidden="true"></i> {{ __('Uploads closed for this SPES season') }}</span>
                                        @elseif($application && $requirement->is_active && $requirement->isAvailableTo($application))
                                            <form method="POST" action="{{ route('applicant.requirements.upload', $requirement) }}" enctype="multipart/form-data">
                                                @csrf
                                                <input type="file" name="document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required aria-label="{{ __('Choose :document file', ['document' => $requirement->name]) }}" style="max-width:190px;font:inherit;font-size:var(--type-caption);">
                                                <button class="action" type="submit"><i class="fa-solid fa-upload" aria-hidden="true"></i> {{ __('Upload') }}</button>
                                                @error('document')
                                                    <span role="alert" style="display:block;color:#b42318;font-size:var(--type-caption);">{{ $message }}</span>
                                                @enderror
                                            </form>
                                        @elseif(!$application)
                                            <a class="action" href="{{ route('applications.create') }}"><i class="fa-solid fa-upload" aria-hidden="true"></i> {{ __('Apply First') }}</a>
                                        @else
                                            <span>{{ __('Not submitted') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            @if($additionalRequirements->isEmpty())
                                <tr>
                                    <td colspan="4">
                                        <p class="empty-state" style="margin:0;">
                                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                            <span>{{ __('No additional documents have been requested by PESO.') }}</span>
                                        </p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
    @if($approvalCapacityClosed)
        <aside class="application-capacity-popup" role="alert" aria-live="assertive" data-application-capacity-popup>
            <i class="fa-solid fa-lock" aria-hidden="true"></i>
            <div class="application-capacity-copy">
                <strong>{{ __('SPES applications are closed') }}</strong>
                <p>{{ __('The approved-applicant limit has been reached. Additional requirement uploads are closed for this season. This does not affect applicants who have already been approved.') }}</p>
            </div>
            <button type="button" aria-label="{{ __('Dismiss notice') }}" data-dismiss-capacity-popup><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
        </aside>
        <script>
            document.querySelector('[data-dismiss-capacity-popup]')?.addEventListener('click', function () {
                document.querySelector('[data-application-capacity-popup]')?.remove();
            });
        </script>
    @endif
</body>
</html>
