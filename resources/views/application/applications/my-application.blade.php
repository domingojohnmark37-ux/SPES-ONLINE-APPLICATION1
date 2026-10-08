<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('My Application') }} — SPES</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #8B0000;
            --primary-dark: #660000;
            --primary-light: #A52A2A;
            --accent: #FFD700;
            --accent-soft: #fff4bf;
            --bg: #f0f2f5;
            --white: #fff;
            --text: #212121;
            --text-muted: #6b7280;
            --border: #e0e0e0;
            --shadow: 0 2px 12px rgba(0,0,0,.08);
            --sidebar-w: 260px;
        }
        body { font-family: 'Segoe UI', Roboto, Arial, sans-serif; background: var(--bg); color: var(--text); }
        .sidebar { position:fixed; top:0; left:0; width:var(--sidebar-w); height:100vh;
            background:var(--primary-dark); display:flex; flex-direction:column; z-index:100; }
        .sidebar-brand { padding:22px 20px 18px; border-bottom:1px solid rgba(255,255,255,.1);
            display:flex; align-items:center; gap:12px; }
        .sidebar-brand img { width:40px; height:40px; border-radius:50%; object-fit:cover; }
        .sidebar-brand span { font-size:.95rem; font-weight:700; color:#fff; line-height:1.2; }
        .sidebar-brand small { display:block; font-size:.7rem; color:rgba(255,255,255,.5); }
        .sidebar-nav { padding:16px 12px; flex:1; }
        .nav-link { display:flex; align-items:center; gap:12px; color:rgba(255,255,255,.95);
            text-decoration:none; padding:14px 16px; border-radius:12px; font-size:1rem;
            transition:background .18s, transform .08s; margin-bottom:10px; }
        .nav-link i { width:16px; text-align:center; }
        .nav-link:hover { background:rgba(255,255,255,.04); transform:translateX(2px); }
        .nav-link.active { background:rgba(255,255,255,.12); box-shadow:none; font-weight:700; color:#fff; }
        .sidebar-footer { padding:14px 12px; border-top:1px solid rgba(255,255,255,.1); }
        .btn-logout { background:none; border:1.5px solid rgba(255,255,255,.3); color:rgba(255,255,255,.8);
            padding:9px 14px; border-radius:7px; cursor:pointer; font-size:.82rem;
            display:flex; align-items:center; gap:7px; width:100%; justify-content:center; transition:background .2s; }
        .btn-logout:hover { background:rgba(255,255,255,.1); color:#fff; }
        .topbar { position:fixed; top:0; left:var(--sidebar-w); right:0; height:62px;
            background:var(--white); box-shadow:var(--shadow); display:flex; align-items:center;
            justify-content:space-between; padding:0 24px; z-index:90; }
        .topbar h1 { font-size:var(--type-title); font-weight:700; color:var(--type-primary-color); }
        .topbar p  { font-size:var(--type-secondary); color:var(--type-secondary-color); }
        .page-wrapper { margin-left:var(--sidebar-w); padding-top:62px; }
        .page-content  { padding:24px; }
        .card { background:var(--white); border-radius:12px; box-shadow:var(--shadow); overflow:hidden; margin-bottom:20px; }
        .card-header { padding:16px 22px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
        .card-header h2 { font-size:1rem; font-weight:700; color:var(--primary); }
        .card-body { padding:22px; }
        .badge { display:inline-flex; align-items:center; gap:5px; padding:6px 14px; border-radius:20px; font-size:.82rem; font-weight:700; }
        .badge-pending  { background:#fff8e1; color:#e65100; }
        .badge-approved { background:#e8f5e9; color:#2e7d32; }
        .badge-denied   { background:#ffebee; color:#c62828; }
        .badge-done     { background:#e8f5e9; color:#1b5e20; }
        .badge-locked   { background:#f5f5f5; color:#9e9e9e; }
        .detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:0; }
        .detail-item { padding:12px 18px; border-bottom:1px solid var(--border); }
        .detail-item:nth-child(odd) { border-right:1px solid var(--border); }
        .detail-label { font-size:var(--type-caption); color:var(--type-caption-color); font-weight:400; margin-bottom:.3rem; }
        .detail-value { font-size:var(--type-body); color:var(--type-primary-color); font-weight:600; }
        .doc-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px; }
        .doc-item { border:1.5px solid var(--border); border-radius:10px; padding:16px; text-align:center; }
        .doc-item i { font-size:1.8rem; margin-bottom:8px; display:block; }
        .doc-item .label { font-size:.78rem; font-weight:600; margin-bottom:8px; }
        .doc-item a { display:inline-flex; align-items:center; gap:5px; padding:6px 12px; background:var(--primary); color:#fff; border-radius:6px; font-size:.75rem; text-decoration:none; }
        .comment-box { background:#f5f7fa; border-left:4px solid var(--primary); padding:14px 18px; border-radius:0 8px 8px 0; font-size:.875rem; line-height:1.6; }
        .apply-cta { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:48px 24px; text-align:center; }
        .apply-cta i { font-size:3.5rem; color:#c8e6c9; margin-bottom:16px; }
        .apply-cta h3 { font-size:1.25rem; color:var(--primary); margin-bottom:8px; }
        .apply-cta p  { color:var(--text-muted); font-size:.9rem; margin-bottom:20px; }
        .btn-apply { background:var(--primary); color:#fff; padding:13px 28px; border-radius:8px; font-size:.95rem; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
        .btn-apply:hover { opacity:.88; }

        /* ── Post-approval form steps ─────────────────────── */
        .steps-wrapper { padding:24px 22px 8px; }
        .steps-heading { font-size:.8rem; text-transform:uppercase; letter-spacing:.08em; color:var(--text-muted); font-weight:700; margin-bottom:18px; }
        .steps-track { display:flex; align-items:flex-start; gap:0; position:relative; }
        .step { flex:1; display:flex; flex-direction:column; align-items:center; text-align:center; position:relative; }
        .step:not(:last-child)::after { content:''; position:absolute; top:18px; left:50%; width:100%; height:2px; background:var(--border); z-index:0; }
        .step.done:not(:last-child)::after { background:#43a047; }
        .step-circle { width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.9rem; font-weight:700; border:2px solid var(--border); background:#fff; z-index:1; position:relative; }
        .step.done   .step-circle { background:#43a047; border-color:#43a047; color:#fff; }
        .step.active .step-circle { background:var(--accent); border-color:var(--accent); color:var(--primary-dark); }
        .step.locked .step-circle { background:#f5f5f5; border-color:#e0e0e0; color:#bbb; }
        .step-label { font-size:.75rem; font-weight:600; margin-top:8px; color:var(--text-muted); max-width:90px; line-height:1.3; }
        .step.done   .step-label { color:#2e7d32; }
        .step.active .step-label { color:var(--primary); }

        .forms-list { padding:0 22px 22px; display:flex; flex-direction:column; gap:12px; }
        .form-row { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-radius:10px; border:1.5px solid var(--border); background:#fafafa; gap:12px; }
        .form-row.done   { border-color:#a5d6a7; background:#f1f8f1; }
        .form-row.active { border-color:var(--accent); background:#fffde7; }
        .form-row.locked { opacity:.6; }
        .form-row-left { display:flex; align-items:center; gap:14px; }
        .form-row-icon { width:40px; height:40px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
        .done   .form-row-icon { background:#e8f5e9; color:#2e7d32; }
        .active .form-row-icon { background:#fff8e1; color:#e65100; }
        .locked .form-row-icon { background:#f5f5f5; color:#bdbdbd; }
        .form-row-title { font-size:.9rem; font-weight:700; }
        .form-row-desc  { font-size:.75rem; color:var(--text-muted); margin-top:2px; }
        .btn-form { padding:9px 18px; border-radius:7px; font-size:.82rem; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:6px; border:none; cursor:pointer; }
        .btn-form-fill   { background:var(--primary); color:#fff; }
        .btn-form-fill:hover { opacity:.88; }
        .btn-form-edit   { background:#e3f2fd; color:#1565c0; }
        .btn-form-locked { background:#f5f5f5; color:#bdbdbd; cursor:not-allowed; }

        .all-done-banner { margin:0 22px 22px; padding:16px 20px; background:#e8f5e9; border-left:5px solid #43a047; border-radius:0 10px 10px 0; display:flex; align-items:center; gap:14px; }
        .all-done-banner i { font-size:1.6rem; color:#2e7d32; }
        .all-done-banner h4 { font-size:.95rem; font-weight:700; color:#1b5e20; }
        .all-done-banner p  { font-size:.8rem; color:#388e3c; margin-top:2px; }
        .approved-next-steps { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:18px; margin-bottom:20px; padding:22px; border:1px solid #e7c85b; border-left:5px solid #c99400; border-radius:12px; background:linear-gradient(135deg,#fffbed,#fff); box-shadow:var(--shadow); }
        .approved-next-steps h2 { display:flex; align-items:center; gap:10px; margin-bottom:9px; color:var(--primary-dark); font-size:1.1rem; }
        .approved-next-steps h2 i { color:#a57900; }
        .approved-next-steps p { margin-top:7px; color:#514a36; font-size:.88rem; line-height:1.6; }
        .approved-next-steps .proposal-note { color:#695a2e; font-size:.8rem; }
        .approved-next-steps .btn-apply { justify-content:center; min-width:205px; text-align:center; }
        @media(prefers-color-scheme:dark) {
            html[data-theme="system"] .approved-next-steps { background:linear-gradient(135deg,#3b3422,#282a2e); border-color:#806d35; }
            html[data-theme="system"] .approved-next-steps h2 { color:#ffe18a; }
            html[data-theme="system"] .approved-next-steps p,
            html[data-theme="system"] .approved-next-steps .proposal-note { color:#e2d8b8; }
        }
        html[data-theme="dark"] .approved-next-steps { background:linear-gradient(135deg,#3b3422,#282a2e); border-color:#806d35; }
        html[data-theme="dark"] .approved-next-steps h2 { color:#ffe18a; }
        html[data-theme="dark"] .approved-next-steps p,
        html[data-theme="dark"] .approved-next-steps .proposal-note { color:#e2d8b8; }

        @media(max-width:768px) {
            .sidebar { transform:translateX(-100%); transition:transform .3s; }
            .sidebar.open { transform:translateX(0); }
            .topbar, .page-wrapper { margin-left:0; }
            .detail-grid { grid-template-columns:1fr; }
            .detail-item:nth-child(odd) { border-right:none; }
            .steps-track { flex-direction:column; gap:8px; }
            .step:not(:last-child)::after { display:none; }
        }
        .hamburger { display:none; background:none; border:none; font-size:1.2rem; cursor:pointer; color:var(--primary); margin-right:10px; }
        @media(max-width:768px) { .hamburger { display:block; } }
        @media(max-width:767.98px) {
            .topbar { left:0; padding:0 14px; }
            .topbar > div { min-width:0; }
            .topbar h1 { overflow:hidden; font-size:var(--type-section); text-overflow:ellipsis; white-space:nowrap; }
            .topbar p { display:none; }
            .page-content { padding:14px; }
            .page-content > div[style*="grid-template-columns"] {
                grid-template-columns:minmax(0, 1fr) !important;
                gap:14px !important;
            }
            .detail-value { overflow-wrap:anywhere; }
            .doc-grid { grid-template-columns:minmax(0, 1fr); }
            .steps-wrapper { padding:18px 16px 8px; }
            .forms-list { padding:0 16px 16px; }
            .form-row { align-items:stretch; flex-direction:column; }
            .form-row-left, .form-row-left > div:last-child { min-width:0; }
            .btn-form { justify-content:center; width:100%; }
            .all-done-banner { align-items:flex-start; margin:0 16px 16px; }
            .approved-next-steps { grid-template-columns:minmax(0,1fr); padding:18px; }
            .approved-next-steps .btn-apply { width:100%; }
            body > footer {
                margin-left:0 !important;
                padding:14px 16px !important;
                flex-direction:column;
                align-items:flex-start;
            }
            body > footer > span:last-child { display:flex; flex-wrap:wrap; gap:10px 14px; }
            body > footer a { overflow-wrap:anywhere; }
        }
    </style>
</head>
<body>

<x-applicant-sidebar />

<header class="topbar">
    <div style="display:flex;align-items:center;">
        <button class="hamburger" type="button" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false" aria-label="{{ __('Toggle applicant navigation') }}"><i class="fa-solid fa-bars" aria-hidden="true"></i></button>
        <div><h1>{{ __('My Application') }}</h1><p>{{ __('Review your submitted information and documents') }}</p></div>
    </div>
</header>

<div class="page-wrapper">
<div class="page-content">

    @if(session('success'))
        <div style="background:#e8f5e9;color:#2e7d32;border-left:4px solid #43a047;padding:13px 16px;border-radius:8px;margin-bottom:16px;display:flex;align-items:center;gap:10px;font-size:.875rem;">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('info'))
        <div style="background:#e3f2fd;color:#1565c0;border-left:4px solid #1e88e5;padding:13px 16px;border-radius:8px;margin-bottom:16px;display:flex;align-items:center;gap:10px;font-size:.875rem;">
            <i class="fa-solid fa-circle-info"></i> {{ session('info') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#ffebee;color:#c62828;border-left:4px solid #e53935;padding:13px 16px;border-radius:8px;margin-bottom:16px;display:flex;align-items:center;gap:10px;font-size:.875rem;">
            <i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}
        </div>
    @endif

    @if(!$application)
        <div class="card">
            <div class="apply-cta">
                <i class="fa-solid fa-file-circle-plus"></i>
                <h2 class="text-section">{{ __('No application yet') }}</h2>
                <p class="text-secondary">{{ __('You have not submitted a SPES application. Start below when you are ready.') }}</p>
                <a href="{{ route('applications.create') }}" class="btn-apply">
                    <i class="fa-solid fa-paper-plane"></i> {{ __('Apply Now') }}
                </a>
            </div>
        </div>
    @else

    @if($application->status === 'approved')
        <section class="approved-next-steps" aria-labelledby="approved-next-steps-heading">
            <div>
                <h2 id="approved-next-steps-heading"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> {{ __('You are a proposed SPES applicant') }}</h2>
                <p>{{ __('The LGU of Lal-lo has approved your application as a proposed SPES applicant. This is a preliminary step and is not yet final confirmation of program participation.') }}</p>
                <p>{{ __('To continue, download the forms requested for you, complete and sign them as instructed, then upload a clear photo or scanned copy through the requirements checklist as soon as possible. After submitting the copy online, bring the completed printed forms to the PESO office in Lal-lo and follow the staff’s instructions for the next step. Follow any specific due date or submission instructions posted by PESO.') }}</p>
                <p class="proposal-note"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> {{ __('PESO will review your submitted requirements and provide further updates about your application.') }}</p>
            </div>
            <a href="{{ route('applicant.requirements') }}" class="btn-apply">
                <i class="fa-solid fa-list-check" aria-hidden="true"></i> {{ __('View forms and next steps') }}
            </a>
        </section>
    @endif

    {{-- Application Info --}}
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
        <div>
            <div class="card">
                <div class="card-header"><h2 class="text-section"><i class="fa-solid fa-id-card" aria-hidden="true"></i> {{ __('Submitted Information') }}</h2></div>
                <div class="detail-grid">
                    <div class="detail-item"><div class="detail-label">{{ __('Full Name') }}</div><div class="detail-value">{{ $application->full_name }}</div></div>
                    <div class="detail-item"><div class="detail-label">{{ __('Sex') }}</div><div class="detail-value">{{ __($application->sex) }}</div></div>
                    <div class="detail-item"><div class="detail-label">{{ __('Birthday') }}</div><div class="detail-value">{{ $application->birthday->locale(app()->getLocale())->translatedFormat('F d, Y') }}</div></div>
                    <div class="detail-item"><div class="detail-label">{{ __('Age') }}</div><div class="detail-value">{{ $application->age }}</div></div>
                    <div class="detail-item"><div class="detail-label">{{ __('Barangay') }}</div><div class="detail-value">{{ $application->barangay }}</div></div>
                    <div class="detail-item"><div class="detail-label">{{ __('Civil Status') }}</div><div class="detail-value">{{ __($application->civil_status) }}</div></div>
                    <div class="detail-item"><div class="detail-label">{{ __('Parent Status') }}</div><div class="detail-value">{{ __($application->parent_status) }}</div></div>
                    <div class="detail-item"><div class="detail-label">{{ __('Education') }}</div><div class="detail-value">{{ __($application->education) }}</div></div>
                    <div class="detail-item"><div class="detail-label">{{ __('Grade/Year Level') }}</div><div class="detail-value">{{ __($application->grade_year_level ?? '—') }}</div></div>
                    <div class="detail-item"><div class="detail-label">{{ __('SPES Type') }}</div><div class="detail-value">{{ __($application->spes_status === 'new' ? 'New (1st time)' : 'SPES Baby (2nd/3rd time)') }}</div></div>
                    <div class="detail-item"><div class="detail-label">{{ __('Facebook Profile') }}</div><div class="detail-value">{{ $application->facebook }}</div></div>
                    <div class="detail-item"><div class="detail-label">{{ __('Mother\'s Name') }}</div><div class="detail-value">{{ $application->mother_name }}</div></div>
                    <div class="detail-item"><div class="detail-label">{{ __('Mother\'s Occupation') }}</div><div class="detail-value">{{ $application->mother_occupation }}</div></div>
                    <div class="detail-item"><div class="detail-label">{{ __('Mother\'s Contact Number') }}</div><div class="detail-value">{{ $application->mother_contact_no }}</div></div>
                    <div class="detail-item"><div class="detail-label">{{ __('Father / Guardian') }}</div><div class="detail-value">{{ $application->father_guardian_name }}</div></div>
                    <div class="detail-item"><div class="detail-label">{{ __('Father\'s Occupation') }}</div><div class="detail-value">{{ $application->father_occupation }}</div></div>
                    <div class="detail-item"><div class="detail-label">{{ __('Father\'s Contact Number') }}</div><div class="detail-value">{{ $application->father_contact_no }}</div></div>
                    @if($application->messenger)
                    <div class="detail-item" style="grid-column:1/-1;border-right:none;">
                        <div class="detail-label">{{ __('Messenger') }}</div><div class="detail-value">{{ $application->messenger }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="text-section">{{ __('Uploaded Documents') }}</h2></div>
                <div class="card-body">
                    <div class="doc-grid">
                        @foreach([
                            ['label'=>'Birth Certificate','key'=>'resume','color'=>'#1565c0'],
                            ['label'=>'Certificate of Enrollment','key'=>'certificate_enrollment','color'=>'#2e7d32'],
                        ] as $doc)
                        <div class="doc-item">
                            <div class="label">{{ $doc['label'] }}</div>
                            @if($application->{$doc['key']})
                                <a href="{{ route('applications.document.preview', ['application' => $application->id, 'document' => $doc['key']]) }}" target="_blank" rel="noopener" aria-label="{{ __('View') }} {{ __($doc['label']) }}" style="display:inline-flex;align-items:center;gap:6px;justify-content:center;min-width:110px;padding:8px 16px;border:0;border-radius:8px;background:#0d47a1;color:#fff;text-decoration:none;font-size:.72rem;font-weight:600;line-height:1.2;">
                                    {{ __('View') }}
                                </a>
                            @else
                                <a href="{{ route('applications.edit') }}" style="display:inline-flex;align-items:center;gap:6px;justify-content:center;min-width:110px;padding:8px 16px;border-radius:8px;background:#1976d2;color:#fff;text-decoration:none;font-size:.72rem;font-weight:600;line-height:1.2;">
                                    {{ __('Upload') }}
                                </a>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="card-header"><h2 class="text-section"><i class="fa-solid fa-info-circle" aria-hidden="true"></i> {{ __('Submission Details') }}</h2></div>
                <div class="card-body" style="font-size:.875rem;">
                    <p style="margin-bottom:10px;"><strong>{{ __('Reference ID') }}</strong><br><code style="color:var(--primary);">{{ $application->ref_id }}</code></p>
                    <p style="margin-bottom:10px;"><strong>{{ __('Date Submitted') }}</strong><br>{{ $application->created_at->locale(app()->getLocale())->translatedFormat('F d, Y') }}</p>
                </div>
            </div>

            @if($application->admin_comment)
            <div class="card">
                <div class="card-header"><h2 class="text-section"><i class="fa-solid fa-comment-dots" aria-hidden="true"></i> {{ __('Admin Feedback') }}</h2></div>
                <div class="card-body">
                    <div class="comment-box">{{ $application->admin_comment }}</div>
                </div>
            </div>
            @endif
        </div>
    </div>

    @endif

</div>
</div>

<footer style="margin-left:var(--sidebar-w);background:#fff;border-top:1px solid var(--border);padding:14px 24px;font-size:.78rem;color:var(--text-muted);display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;">
    <span>&copy; {{ date('Y') }} SPES Management System — PESO LAL-LO</span>
    <span>
        <a href="https://www.facebook.com" target="_blank" style="color:var(--primary);text-decoration:none;margin-right:14px;"><i class="fa-brands fa-facebook"></i> Facebook</a>
        <a href="mailto:lgulalloinformationoffice@gmail.com" style="color:var(--primary);text-decoration:none;"><i class="fa-solid fa-envelope"></i> lgulalloinformationoffice@gmail.com</a>
    </span>
</footer>
<x-portal-help-chat />
</body>
</html>
