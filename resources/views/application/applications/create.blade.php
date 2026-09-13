<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SPES Application Form</title>
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
            --danger: #c62828;
            --sidebar-w: 260px;
        }
        body { font-family: 'Segoe UI', Roboto, Arial, sans-serif; background: var(--bg); color: var(--text); }

        /* Sidebar (dashboard style) */
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

        /* Topbar */
        .topbar { position:fixed; top:0; left:var(--sidebar-w); right:0; height:62px;
            background:var(--white); box-shadow:var(--shadow); display:flex; align-items:center;
            justify-content:space-between; padding:0 24px; z-index:90; }
        .topbar h1 { font-size:1.15rem; font-weight:700; color:var(--primary); }
        .topbar p  { font-size:.78rem; color:var(--text-muted); }

        /* Main */
        .page-wrapper { margin-left:var(--sidebar-w); padding-top:62px; }
        .page-content  { padding:24px; max-width:880px; }

        /* Form card */
        .form-card { background:var(--white); border-radius:12px; box-shadow:var(--shadow); overflow:hidden; margin-bottom:24px; }
        .form-card-header { background:var(--primary); color:#fff; padding:16px 22px; display:flex; align-items:center; gap:10px; }
        .form-card-header h3 { font-size:1rem; font-weight:700; }
        .form-card-body { padding:22px; }

        /* Form elements */
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
        .form-row.three { grid-template-columns:1fr 1fr 1fr; }
        .form-row.full  { grid-template-columns:1fr; }
        .form-group { display:flex; flex-direction:column; gap:5px; }
        .form-group label { font-size:.8rem; font-weight:600; color:var(--text); }
        .form-group label .req { color:var(--danger); margin-left:2px; }
        .form-group input, .form-group select, .form-group textarea {
            padding:10px 12px; border:1.5px solid var(--border); border-radius:7px;
            font-size:.875rem; font-family:inherit; outline:none; transition:border-color .2s;
            background:#fff; color:var(--text);
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color:var(--primary); }
        .form-group .error { font-size:.75rem; color:var(--danger); margin-top:2px; }

        /* Radio group */
        .radio-group { display:flex; gap:16px; flex-wrap:wrap; margin-top:4px; }
        .radio-opt { display:flex; align-items:center; gap:7px; cursor:pointer; font-size:.875rem; }
        .radio-opt input { width:16px; height:16px; cursor:pointer; accent-color:var(--primary); }

        /* File upload */
        .document-upload-grid { display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; }
        .document-upload { min-width:0; display:flex; flex-direction:column; gap:6px; }
        .document-upload > label:first-child { font-size:.8rem; font-weight:600; color:var(--text); }
        .file-upload-area {
            min-width:0; overflow:hidden;
            min-height:92px; border:1.5px dashed var(--border); border-radius:8px; padding:12px;
            display:flex; flex-direction:column; align-items:center; justify-content:center;
            text-align:center; cursor:pointer; transition:border-color .2s, background .2s;
        }
        .file-upload-area:hover { border-color:var(--primary); background:#f0f9f7; }
        .file-upload-area i { font-size:1.15rem; color:var(--primary); margin-bottom:5px; }
        .file-upload-area strong { font-size:.78rem; }
        .file-name { font-size:.7rem; color:var(--primary); font-weight:600; margin-top:4px; overflow:hidden; text-overflow:ellipsis; max-width:100%; white-space:nowrap; }

        /* Submit */
        .btn-submit { background:var(--primary); color:#fff; border:none; padding:13px 32px;
            border-radius:8px; font-size:1rem; font-weight:700; cursor:pointer;
            display:flex; align-items:center; gap:8px; transition:opacity .2s; }
        .btn-submit:hover { opacity:.88; }
        .btn-back { background:transparent; border:1.5px solid var(--primary); color:var(--primary);
            padding:11px 20px; border-radius:8px; font-size:.875rem; font-weight:600;
            cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }

        /* Alert */
        .alert { padding:13px 16px; border-radius:8px; margin-bottom:20px; font-size:.875rem; display:flex; align-items:flex-start; gap:10px; }
        .alert-danger { background:#ffebee; color:#c62828; border-left:4px solid #e53935; }
        .alert-success { background:#e8f5e9; color:#2e7d32; border-left:4px solid #43a047; }
        .alert-info { background:#e3f2fd; color:#1565c0; border-left:4px solid #1e88e5; }

        .progress-bar { display:flex; gap:0; margin-bottom:28px; border-radius:8px; overflow:hidden; box-shadow:var(--shadow); }
        .progress-step { flex:1; padding:12px 8px; text-align:center; font-size:.75rem; font-weight:600; background:#e0e0e0; color:#757575; position:relative; }
        .progress-step.active { background:var(--primary); color:#fff; }
        .progress-step.done   { background:var(--accent); color:var(--primary-dark); }

        @media(max-width:768px) {
            .sidebar { transform:translateX(-100%); transition:transform .3s; }
            .sidebar.open { transform:translateX(0); }
            .topbar, .page-wrapper { margin-left:0; }
            .form-row { grid-template-columns:1fr; }
            .form-row.three { grid-template-columns:1fr; }
            .document-upload-grid { grid-template-columns:1fr; }
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
        <div><h1>SPES Application Form</h1><p>Fill out all required fields carefully</p></div>
    </div>
</header>

<div class="page-wrapper">
<div class="page-content">

    @php
        $editing = isset($application) && $application;
        $reapplying = $editing && $application->status === 'denied';
        $defaults = $defaults ?? [];
        $getDefault = fn ($field, $fallback = '') => old($field, $application->{$field} ?? $defaults[$field] ?? $fallback);
    @endphp

    @if($errors->any())
        <div class="alert alert-danger">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div><strong>Please fix the following errors:</strong>
                <ul style="margin-top:6px;padding-left:18px;">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="alert alert-info"><i class="fa-solid fa-circle-info"></i> {{ session('info') }}</div>
    @endif
    @if($reapplying)
        <div class="alert alert-info">
            <i class="fa-solid fa-rotate-right"></i>
            <div>
                <strong>Reapply for SPES</strong>
                <div style="margin-top:4px;">Update your application based on the admin feedback. After you submit, your status will return to pending for review.</div>
                @if($application->admin_comment)
                    <div style="margin-top:8px;"><strong>Admin feedback:</strong> {{ $application->admin_comment }}</div>
                @endif
            </div>
        </div>
    @endif

    <form method="POST" action="{{ $editing ? route('applications.update') : route('applications.store') }}" enctype="multipart/form-data" id="appForm">
        @csrf
        @if($editing)
            @method('PUT')
        @endif

        {{-- Section 1: Personal Info --}}
        <div class="form-card">
            <div class="form-card-header">
                <i class="fa-solid fa-user"></i>
                <h3>Personal Information</h3>
            </div>
            <div class="form-card-body">
                <div class="form-row three">
                    <div class="form-group">
                        <label>Surname <span class="req">*</span></label>
                        <input type="text" name="surname" value="{{ $getDefault('surname') }}" placeholder="Last Name" required>
                        @error('surname')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>First Name <span class="req">*</span></label>
                        <input type="text" name="first_name" value="{{ $getDefault('first_name') }}" placeholder="First Name" required>
                        @error('first_name')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Middle Name <span class="req">*</span></label>
                        <input type="text" name="middle_name" id="middle_name" value="{{ $getDefault('middle_name') }}" placeholder="Middle Name" required>
                        @error('middle_name')<div class="error">{{ $message }}</div>@enderror
                        <div id="middle_name_warning" class="error" style="display:none; margin-top:5px;">Please enter your complete middle name. Single initials such as A or A. are not accepted.</div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Sex <span class="req">*</span></label>
                        <div class="radio-group" style="margin-top:8px;">
                            <label class="radio-opt"><input type="radio" name="sex" value="Male" {{ $getDefault('sex')==='Male' ? 'checked' : '' }} required> Male</label>
                            <label class="radio-opt"><input type="radio" name="sex" value="Female" {{ $getDefault('sex')==='Female' ? 'checked' : '' }}> Female</label>
                        </div>
                        @error('sex')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Civil Status <span class="req">*</span></label>
                        <select name="civil_status" required>
                            <option value="">-- Select --</option>
                            @foreach(['Single','Married','Widowed','Separated'] as $cs)
                                <option value="{{ $cs }}" {{ $getDefault('civil_status')===$cs ? 'selected' : '' }}>{{ $cs }}</option>
                            @endforeach
                        </select>
                        @error('civil_status')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-row three">
                    <div class="form-group">
                        <label>Birthday <span class="req">*</span></label>
                        <input type="date" name="birthday" value="{{ old('birthday', $application?->birthday?->format('Y-m-d') ?? $defaults['birthday'] ?? '') }}" required>
                        @error('birthday')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Age <span class="req">*</span></label>
                        <input type="number" name="age" value="{{ $getDefault('age') }}" min="15" max="30" placeholder="15–30" required id="ageField">
                        @error('age')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Barangay <span class="req">*</span></label>
                        <select name="barangay" required>
                            <option value="">-- Select Barangay --</option>
                            @foreach(['Abagao','Alaguia','Bagumbayan','Bangag','Bical','Bicud','Binag','Cabayabasan (Capacuan)','Cagoran','Cambong','Catayauan','Catugan','Centro (Poblacion)','Cullit','Dagupan','Dalaya','Fabrica','Fusina','Jurisdiction','Lalafugan','Logac','Magapit','Malanao','Maxingal','Naguilian','Paranum','Rosario','San Antonio (Lafu)','San Jose','San Juan','San Lorenzo','San Mariano','Santa Maria','Santa Teresa (Magallungon)','Tucalana'] as $b)
                                <option value="{{ $b }}" {{ $getDefault('barangay')===$b ? 'selected' : '' }}>{{ $b }}</option>
                            @endforeach
                        </select>
                        @error('barangay')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Parent Status <span class="req">*</span></label>
                        <select name="parent_status" required>
                            <option value="">-- Select --</option>
                            @foreach(['Both Parents Living','Solo Parent','Orphan','Guardian'] as $ps)
                                <option value="{{ $ps }}" {{ $getDefault('parent_status')===$ps ? 'selected' : '' }}>{{ $ps }}</option>
                            @endforeach
                        </select>
                        @error('parent_status')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Educational Attainment <span class="req">*</span></label>
                        <select name="education" required>
                            <option value="">-- Select --</option>
                            @foreach(['High School (Currently Enrolled)','High School Graduate','Senior High School (Currently Enrolled)','Senior High School Graduate','College (Currently Enrolled)','College Graduate','Vocational / Tech-Voc','Out-of-School Youth'] as $ed)
                                <option value="{{ $ed }}" {{ $getDefault('education')===$ed ? 'selected' : '' }}>{{ $ed }}</option>
                            @endforeach
                        </select>
                        @error('education')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>SPES Beneficiary Status <span class="req">*</span></label>
                        <div class="radio-group" style="margin-top:8px;">
                            <label class="radio-opt">
                                <input type="radio" name="spes_status" value="new" {{ $getDefault('spes_status')==='new' ? 'checked' : '' }} required>
                                <span><strong>New</strong> — First time applicant</span>
                            </label>
                            <label class="radio-opt">
                                <input type="radio" name="spes_status" value="baby" {{ $getDefault('spes_status')==='baby' ? 'checked' : '' }}>
                                <span><strong>SPES Baby</strong> — 2nd or 3rd time beneficiary</span>
                            </label>
                        </div>
                        @error('spes_status')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Facebook Profile <span class="req">*</span></label>
                        <input type="text" name="facebook" value="{{ $getDefault('facebook') }}" placeholder="e.g., https://facebook.com/yourprofile or your Facebook URL" required>
                        @error('facebook')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 2: Family & Contact --}}
        <div class="form-card">
            <div class="form-card-header">
                <i class="fa-solid fa-people-roof"></i>
                <h3>Family & Contact Information</h3>
            </div>
            <div class="form-card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Mother's Name <span class="req" data-family-required>*</span></label>
                        <input type="text" name="mother_name" value="{{ $getDefault('mother_name') }}" placeholder="Full name" data-family-field>
                        @error('mother_name')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Father's Name / Guardian's Name <span class="req" data-family-required>*</span></label>
                        <input type="text" name="father_guardian_name" value="{{ $getDefault('father_guardian_name') }}" placeholder="Full name" data-family-field>
                        @error('father_guardian_name')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Mother's Occupation <span class="req" data-family-required>*</span></label>
                        <input type="text" name="mother_occupation" value="{{ $getDefault('mother_occupation') }}" placeholder="Enter occupation" data-family-field>
                        @error('mother_occupation')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Father's Occupation <span class="req" data-family-required>*</span></label>
                        <input type="text" name="father_occupation" value="{{ $getDefault('father_occupation') }}" placeholder="Enter occupation" data-family-field>
                        @error('father_occupation')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Mother's Contact Number <span class="req" data-family-required>*</span></label>
                        <input type="text" name="mother_contact_no" value="{{ $getDefault('mother_contact_no') }}" placeholder="09XXXXXXXXX" maxlength="20" data-family-field>
                        @error('mother_contact_no')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>Father's Contact Number <span class="req" data-family-required>*</span></label>
                        <input type="text" name="father_contact_no" value="{{ $getDefault('father_contact_no') }}" placeholder="09XXXXXXXXX" maxlength="20" data-family-field>
                        @error('father_contact_no')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Facebook Account</label>
                        <input type="text" name="messenger" value="{{ $getDefault('messenger') }}" placeholder="Facebook name or link (optional)">
                        @error('messenger')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Documents --}}
        <div class="form-card">
            <div class="form-card-header">
                <i class="fa-solid fa-folder-open"></i>
                <h3>Documentary Requirements <span style="font-weight:400;font-size:.85rem;">(Required for application verification)</span></h3>
            </div>
            <div class="form-card-body">
                <p style="font-size:.83rem;color:var(--text-muted);margin-bottom:14px;">
                    PDF files only. Maximum 5 MB per file.
                </p>
                <div class="document-upload-grid">
                    <div class="document-upload">
                        <label>Birth Certificate</label>
                        <label class="file-upload-area" for="resume">
                            <i class="fa-solid fa-file-user"></i>
                            <strong>Choose PDF</strong>
                            <div class="file-name" id="resumeName"></div>
                        </label>
                        <input type="file" name="resume" id="resume" style="display:none;" accept=".pdf,application/pdf" required
                            onchange="validateDocument(this, 'resumeName')">
                        @error('resume')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="document-upload">
                        <label>Certificate of Enrollment</label>
                        <label class="file-upload-area" for="certificate_enrollment">
                            <i class="fa-solid fa-file-lines"></i>
                            <strong>Choose PDF</strong>
                            <div class="file-name" id="certificateEnrollmentName"></div>
                        </label>
                        <input type="file" name="certificate_enrollment" id="certificate_enrollment" style="display:none;" accept=".pdf,application/pdf" required
                            onchange="validateDocument(this, 'certificateEnrollmentName')">
                        @error('certificate_enrollment')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <button type="submit" class="btn-submit" id="submitBtn">
                <i class="fa-solid fa-paper-plane"></i> {{ $reapplying ? 'Reapply Application' : ($editing ? 'Update Application' : 'Submit Application') }}
            </button>
            <a href="{{ route('dashboard') }}" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Cancel</a>
        </div>
    </form>
</div>
</div>

<footer style="margin-left:var(--sidebar-w);background:#fff;border-top:1px solid var(--border);padding:14px 24px;font-size:.78rem;color:var(--text-muted);display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;">
    <span>&copy; {{ date('Y') }} SPES Management System — PESO LAL-LO</span>
    <span>
        <a href="https://www.facebook.com" target="_blank" style="color:var(--primary);text-decoration:none;margin-right:14px;"><i class="fa-brands fa-facebook"></i> Facebook</a>
        <a href="mailto:lgulalloinformationoffice@gmail.com" style="color:var(--primary);text-decoration:none;"><i class="fa-solid fa-envelope"></i> lgulalloinformationoffice@gmail.com</a>
    </span>
</footer>

<script>
// Auto-calculate age from birthday
function validateDocument(input, nameId) {
    const file = input.files[0];
    const maxSize = 5 * 1024 * 1024;
    const isPdf = file && (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf'));

    if (file && (!isPdf || file.size > maxSize)) {
        alert(!isPdf ? 'Only PDF files are accepted.' : 'Each PDF file must be 5 MB or smaller.');
        input.value = '';
        document.getElementById(nameId).textContent = '';
        return;
    }

    document.getElementById(nameId).textContent = file?.name || '';
}

document.querySelector('input[name="birthday"]').addEventListener('change', function () {
    const dob = new Date(this.value);
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
    document.getElementById('ageField').value = age > 0 ? age : '';
});

// Validate middle name - standalone initials are not accepted.
function validateMiddleName(input) {
    const middleNameWarning = document.getElementById('middle_name_warning');
    const middleName = input.value.trim();
    const isStandaloneInitial = /^[A-Za-z]\.?$/.test(middleName);

    if (isStandaloneInitial) {
        middleNameWarning.style.display = 'block';
        input.setCustomValidity('Please enter your complete middle name. Single initials such as A or A. are not accepted.');
    } else {
        middleNameWarning.style.display = 'none';
        input.setCustomValidity('');
    }
}

const middleNameInput = document.getElementById('middle_name');
middleNameInput.addEventListener('input', function () {
    validateMiddleName(this);
});
middleNameInput.addEventListener('blur', function () {
    validateMiddleName(this);
});

// Only Both Parents Living requires every Family & Contact field.
function updateFamilyContactRequirements() {
    const parentStatus = document.querySelector('select[name="parent_status"]').value;
    const isFamilyRequired = parentStatus === 'Both Parents Living';

    document.querySelectorAll('[data-family-field]').forEach(field => {
        field.required = isFamilyRequired;
    });

    document.querySelectorAll('[data-family-required]').forEach(marker => {
        marker.style.display = isFamilyRequired ? 'inline' : 'none';
    });
}

const parentStatusInput = document.querySelector('select[name="parent_status"]');
parentStatusInput.addEventListener('change', updateFamilyContactRequirements);
updateFamilyContactRequirements();

// Prevent double-submit
document.getElementById('appForm').addEventListener('submit', function () {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting…';
});
</script>
</body>
</html>
