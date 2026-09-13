<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Application — SPES</title>
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
        .topbar h1 { font-size:1.15rem; font-weight:700; color:var(--primary); }
        .topbar p  { font-size:.78rem; color:var(--text-muted); }
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
        .detail-label { font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted); font-weight:600; margin-bottom:3px; }
        .detail-value { font-size:.9rem; font-weight:500; }
        .status-banner { padding:20px 22px; display:flex; align-items:center; gap:16px; }
        .status-banner.pending  { background:#fff8e1; border-left:5px solid #ffab00; }
        .status-banner.approved { background:#e8f5e9; border-left:5px solid #43a047; }
        .status-banner.denied   { background:#ffebee; border-left:5px solid #e53935; }
        .status-banner-icon { font-size:2rem; }
        .status-banner h3 { font-size:1.05rem; font-weight:700; }
        .status-banner p  { font-size:.85rem; margin-top:3px; }
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
    </style>
</head>
<body>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="{{ asset('images/welcome_logo.jpg') }}" alt="SPES">
        <div><span>SPES Portal<small>PESO LAL-LO</small></span></div>
    </div>
    <nav class="sidebar-nav">
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="{{ route('applications.myApplication') }}" class="nav-link {{ request()->routeIs(['applications.myApplication', 'applications.form2', 'applications.form2.store']) ? 'active' : '' }}"><i class="fa-solid fa-file-lines"></i> My Application</a>
        <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}"><i class="fa-solid fa-user-pen"></i> Edit Profile</a>
        <a href="{{ $application && $application->status === 'denied' ? route('applications.edit') : route('applications.create') }}" class="nav-link {{ request()->routeIs(['applications.create', 'applications.store', 'applications.edit']) ? 'active' : '' }}"><i class="fa-solid {{ $application && $application->status === 'denied' ? 'fa-rotate-right' : 'fa-file-circle-plus' }}"></i> {{ $application && $application->status === 'denied' ? 'Reapply' : 'Apply Now' }}</a>
        @if($application && $application->status === 'approved')
            <a href="{{ route('updates') }}" class="nav-link {{ request()->routeIs('updates') ? 'active' : '' }}"><i class="fa-solid fa-newspaper"></i> Updates</a>
        @endif
    </nav>
    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Log Out</button>
        </form>
    </div>
</aside>

<header class="topbar">
    <div style="display:flex;align-items:center;">
        <button class="hamburger" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fa-solid fa-bars"></i></button>
        <div><h1>My Application</h1><p>Track your SPES application status</p></div>
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
                <h3>No Application Yet</h3>
                <p>You haven't submitted a SPES application. Click below to start.</p>
                <a href="{{ route('applications.create') }}" class="btn-apply">
                    <i class="fa-solid fa-paper-plane"></i> Apply Now
                </a>
            </div>
        </div>
    @else

    {{-- Status Banner --}}
    <div class="card" style="overflow:hidden;">
        <div class="status-banner {{ $application->status }}">
            <div class="status-banner-icon">
                @if($application->status === 'approved') <i class="fa-solid fa-circle-check" style="color:#2e7d32;"></i>
                @elseif($application->status === 'denied') <i class="fa-solid fa-circle-xmark" style="color:#c62828;"></i>
                @else <i class="fa-solid fa-clock" style="color:#e65100;"></i>
                @endif
            </div>
            <div>
                <h3>
                    @if($application->status === 'approved') Your application has been Approved!
                    @elseif($application->status === 'denied') Your application was Denied
                    @else Your application is under review
                    @endif
                </h3>
                <p>
                    @if($application->status === 'approved') Congratulations! Please complete the employment form below to proceed.
                    @elseif($application->status === 'denied') Review the admin feedback, update your details, and reapply when you are ready.
                    @else Your application is currently being reviewed by the PESO officer.
                    @endif
                </p>
            </div>
            <div style="margin-left:auto; display:flex; gap:10px; align-items:center;">
                @if($application->status === 'denied')
                    <a href="{{ route('applications.edit') }}" class="btn-form btn-form-fill" style="padding:10px 16px;"> <i class="fa-solid fa-rotate-right"></i> Reapply</a>
                @endif
                <span class="badge badge-{{ $application->status }}">{{ ucfirst($application->status) }}</span>
            </div>
        </div>
    </div>

    {{-- Application Info --}}
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">
        <div>
            <div class="card">
                <div class="card-header"><h2><i class="fa-solid fa-id-card"></i> Submitted Information</h2></div>
                <div class="detail-grid">
                    <div class="detail-item"><div class="detail-label">Full Name</div><div class="detail-value">{{ $application->full_name }}</div></div>
                    <div class="detail-item"><div class="detail-label">Sex</div><div class="detail-value">{{ $application->sex }}</div></div>
                    <div class="detail-item"><div class="detail-label">Birthday</div><div class="detail-value">{{ $application->birthday->format('F d, Y') }}</div></div>
                    <div class="detail-item"><div class="detail-label">Age</div><div class="detail-value">{{ $application->age }}</div></div>
                    <div class="detail-item"><div class="detail-label">Barangay</div><div class="detail-value">{{ $application->barangay }}</div></div>
                    <div class="detail-item"><div class="detail-label">Civil Status</div><div class="detail-value">{{ $application->civil_status }}</div></div>
                    <div class="detail-item"><div class="detail-label">Parent Status</div><div class="detail-value">{{ $application->parent_status }}</div></div>
                    <div class="detail-item"><div class="detail-label">Education</div><div class="detail-value">{{ $application->education }}</div></div>
                    <div class="detail-item"><div class="detail-label">SPES Type</div><div class="detail-value">{{ $application->spes_status === 'new' ? 'New (1st time)' : 'SPES Baby (2nd/3rd time)' }}</div></div>
                    <div class="detail-item"><div class="detail-label">Facebook Profile</div><div class="detail-value">{{ $application->facebook }}</div></div>
                    <div class="detail-item"><div class="detail-label">Mother's Name</div><div class="detail-value">{{ $application->mother_name }}</div></div>
                    <div class="detail-item"><div class="detail-label">Mother's Occupation</div><div class="detail-value">{{ $application->mother_occupation }}</div></div>
                    <div class="detail-item"><div class="detail-label">Mother's Contact Number</div><div class="detail-value">{{ $application->mother_contact_no }}</div></div>
                    <div class="detail-item"><div class="detail-label">Father / Guardian</div><div class="detail-value">{{ $application->father_guardian_name }}</div></div>
                    <div class="detail-item"><div class="detail-label">Father's Occupation</div><div class="detail-value">{{ $application->father_occupation }}</div></div>
                    <div class="detail-item"><div class="detail-label">Father's Contact Number</div><div class="detail-value">{{ $application->father_contact_no }}</div></div>
                    @if($application->messenger)
                    <div class="detail-item" style="grid-column:1/-1;border-right:none;">
                        <div class="detail-label">Messenger</div><div class="detail-value">{{ $application->messenger }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2>Uploaded Documents</h2></div>
                <div class="card-body">
                    <div class="doc-grid">
                        @foreach([
                            ['label'=>'Resume','key'=>'resume','color'=>'#1565c0'],
                            ['label'=>'Certificate of Enrollment','key'=>'certificate_enrollment','color'=>'#2e7d32'],
                        ] as $doc)
                        <div class="doc-item">
                            <div class="label">{{ $doc['label'] }}</div>
                            @if($application->{$doc['key']})
                                <button type="button" onclick="openDocumentModal('{{ route('applications.document.view', ['application' => $application->id, 'document' => $doc['key']]) }}', '{{ $doc['label'] }}')" style="display:inline-flex;align-items:center;gap:6px;justify-content:center;min-width:110px;padding:8px 16px;border:0;border-radius:8px;background:#0d47a1;color:#fff;cursor:pointer;font-size:.72rem;font-weight:600;line-height:1.2;">
                                    View
                                </button>
                            @else
                                <a href="{{ route('applications.edit') }}" style="display:inline-flex;align-items:center;gap:6px;justify-content:center;min-width:110px;padding:8px 16px;border-radius:8px;background:#1976d2;color:#fff;text-decoration:none;font-size:.72rem;font-weight:600;line-height:1.2;">
                                    Upload
                                </a>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div id="documentModal" style="position:fixed; inset:0; background:rgba(0,0,0,.45); display:none; align-items:center; justify-content:center; z-index:2000; padding:24px;">
            <div style="width:min(980px, 92vw); max-height:90vh; background:#fff; border-radius:14px; box-shadow:0 20px 60px rgba(0,0,0,.25); overflow:hidden; border:1px solid var(--border);">
                <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-bottom:1px solid var(--border); background:#f8fafb;">
                    <strong id="documentModalTitle" style="font-size:1rem; color:var(--primary);">Document Preview</strong>
                    <button type="button" onclick="closeDocumentModal()" style="border:1.5px solid var(--border); background:#fff; color:var(--primary); border-radius:7px; padding:7px 12px; cursor:pointer; font-weight:600;">
                        <i class="fa-solid fa-xmark"></i> Close
                    </button>
                </div>
                <div style="padding:12px; background:#f5f5f5; height:80vh;">
                    <iframe id="documentFrame" title="Document Preview" style="width:100%; height:100%; border:none; background:#fff; border-radius:8px;"></iframe>
                </div>
            </div>
        </div>

        <script>
            function openDocumentModal(url, title) {
                const modal = document.getElementById('documentModal');
                const frame = document.getElementById('documentFrame');
                const titleEl = document.getElementById('documentModalTitle');

                titleEl.textContent = title + ' Preview';
                modal.style.display = 'flex';

                fetch(url)
                    .then(response => response.blob())
                    .then(blob => {
                        const objectUrl = URL.createObjectURL(blob);
                        frame.src = objectUrl;
                    })
                    .catch(() => {
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

        <div>
            <div class="card">
                <div class="card-header"><h2><i class="fa-solid fa-info-circle"></i> Submission Details</h2></div>
                <div class="card-body" style="font-size:.875rem;">
                    <p style="margin-bottom:10px;"><strong>Reference ID</strong><br><code style="color:var(--primary);">{{ $application->ref_id }}</code></p>
                    <p style="margin-bottom:10px;"><strong>Date Submitted</strong><br>{{ $application->created_at->format('F d, Y') }}</p>
                    <p><strong>Current Status</strong><br>
                        <span class="badge badge-{{ $application->status }}" style="margin-top:4px;">{{ ucfirst($application->status) }}</span>
                    </p>
                </div>
            </div>

            @if($application->admin_comment)
            <div class="card">
                <div class="card-header"><h2><i class="fa-solid fa-comment-dots"></i> Admin Feedback</h2></div>
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
</body>
</html>
