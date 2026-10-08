<!DOCTYPE html>
<html lang="{{ request()->attributes->get('applicant_language', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('SPES Application Form') }}</title>
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
        .topbar h1 { font-size:var(--type-title); font-weight:700; color:var(--type-primary-color); }
        .topbar p  { font-size:var(--type-secondary); color:var(--type-secondary-color); }

        /* Main */
        .page-wrapper { margin-left:var(--sidebar-w); padding-top:62px; }
        .page-content  { padding:24px; max-width:880px; }

        /* Form card */
        .form-card { background:var(--white); border-radius:12px; box-shadow:var(--shadow); overflow:hidden; margin-bottom:24px; }
        .form-card-header { background:var(--primary); color:#fff; padding:16px 22px; display:flex; align-items:center; gap:10px; }
        .form-card-header h2 { font-size:var(--type-section); font-weight:600; color:#fff; }
        .form-card-header .text-secondary { flex-basis:100%; margin:.25rem 0 0 1.8rem; color:rgba(255,255,255,.9); }
        .form-card-body { padding:22px; }

        /* Form elements */
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
        .form-row.three { grid-template-columns:1fr 1fr 1fr; }
        .form-row.full  { grid-template-columns:1fr; }
        .form-group { display:flex; flex-direction:column; gap:5px; }
        .form-group label { font-size:var(--type-secondary); font-weight:600; color:var(--type-primary-color); }
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
        .document-upload-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:16px; }
        .document-upload { min-width:0; display:flex; flex-direction:column; gap:10px; padding:16px; border:1px solid var(--border); border-radius:10px; background:#fff; }
        .document-upload-heading { display:flex; align-items:center; justify-content:space-between; gap:10px; }
        .document-upload-heading label { min-width:0; font-size:.9rem; font-weight:700; color:var(--text); }
        .document-status { flex:0 0 auto; display:inline-flex; align-items:center; gap:5px; padding:4px 8px; border-radius:999px; background:#e8f5e9; color:#237a36; font-size:.68rem; font-weight:700; }
        .document-status.required { background:#fff4e5; color:#9a5700; }
        .current-document { min-width:0; display:flex; align-items:center; gap:10px; padding:10px; border:1px solid #e5e7eb; border-radius:8px; background:#f8fafc; }
        .current-document > .fa-file-pdf { flex:0 0 auto; color:var(--primary); font-size:1.15rem; }
        .current-document-details { min-width:0; flex:1; display:flex; flex-direction:column; gap:3px; }
        .current-document-details small { color:var(--text-muted); font-size:.68rem; }
        .current-document-details .document-filename { display:block; overflow:hidden; color:#334155; font-size:.76rem; font-weight:600; text-overflow:ellipsis; white-space:nowrap; }
        .view-document-link { flex:0 0 auto; display:inline-flex; align-items:center; gap:5px; padding:7px 9px; border:1px solid #d1d5db; border-radius:6px; color:var(--primary); background:#fff; font-size:.72rem; font-weight:700; text-decoration:none; }
        .view-document-link:hover { border-color:var(--primary); background:#fff8f8; }
        .file-upload-area {
            position:relative; min-width:0; overflow:hidden;
            min-height:86px; border:1.5px dashed #cbd5e1; border-radius:8px; padding:12px;
            display:flex; flex-direction:column; align-items:center; justify-content:center;
            text-align:center; cursor:pointer; background:#fff; transition:border-color .2s, background .2s;
        }
        .file-upload-area:hover, .file-upload-area:focus-within { border-color:var(--primary); background:#fffafa; }
        .file-upload-area:focus-within { outline:3px solid #2563eb; outline-offset:3px; }
        .file-upload-area > i { font-size:1rem; color:var(--primary); margin-bottom:5px; }
        .file-upload-area strong { color:var(--primary-dark); font-size:.78rem; }
        .file-upload-hint { margin-top:3px; color:var(--text-muted); font-size:.68rem; }
        .file-upload-input { position:absolute; inset:0; width:100%; height:100%; opacity:0; cursor:pointer; }
        .file-name { max-width:100%; margin-top:5px; overflow:hidden; color:var(--primary); font-size:.72rem; font-weight:700; text-overflow:ellipsis; white-space:nowrap; }
        .file-name:empty { display:none; }
        .document-upload .error { font-size:.75rem; color:var(--danger); }
        .document-upload-note { margin-bottom:16px; color:var(--text-muted); font-size:.84rem; }

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
        @media(max-width:767.98px) {
            .topbar { left:0; padding:0 14px; }
            .topbar > div { min-width:0; }
            .topbar h1 { overflow:hidden; font-size:var(--type-section); text-overflow:ellipsis; white-space:nowrap; }
            .page-content { padding:14px; }
            .form-card-header { padding:14px 16px; }
            .form-card-body { padding:16px; }
            .progress-bar { justify-content:flex-start; overflow-x:auto; }
            .progress-step { flex:0 0 84px; padding:10px 6px; }
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
        <button class="hamburger" type="button" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false" aria-label="Toggle applicant navigation"><i class="fa-solid fa-bars" aria-hidden="true"></i></button>
        <div><h1>{{ __('SPES Application Form') }}</h1><p>{{ __('Fill out all required fields carefully') }}</p></div>
    </div>
</header>

<div class="page-wrapper">
<div class="page-content">

    @php
        $editing = isset($application) && $application;
        $reapplying = $editing && $application->status === 'denied';
        $defaults = $defaults ?? [];
        $getDefault = fn ($field, $fallback = '') => old($field, $application->{$field} ?? $defaults[$field] ?? $fallback);
        $documentOriginalNames = $application?->document_original_names ?? [];
        $hasCurrentBirthCertificate = $reapplying && $application->resume && \Illuminate\Support\Facades\Storage::disk('public')->exists($application->resume);
        $hasCurrentEnrollmentCertificate = $reapplying && $application->certificate_enrollment && \Illuminate\Support\Facades\Storage::disk('public')->exists($application->certificate_enrollment);
    @endphp

    @if($errors->any())
        <div class="alert alert-danger">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>            <strong>{{ __('Please fix the following errors:') }}</strong>
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
                <strong>{{ __('Reapply for SPES') }}</strong>
                <div style="margin-top:4px;">{{ __('Update your application based on the admin feedback. After you submit, your status will return to pending for review.') }}</div>
                <div style="margin-top:6px;">{{ __('Your previously submitted information and documents will stay on your application. Upload a replacement only for a document you want to change.') }}</div>
                @if($application->admin_comment)
                    <div style="margin-top:8px;"><strong>{{ __('Admin feedback:') }}</strong> {{ $application->admin_comment }}</div>
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
                <h2>{{ __('Personal Information') }}</h2>
            </div>
            <div class="form-card-body">
                <div class="form-row three">
                    <div class="form-group">
                        <label>{{ __('Surname') }} <span class="req">*</span></label>
                        <input type="text" name="surname" value="{{ $getDefault('surname') }}" placeholder="{{ __('Last Name') }}" required>
                        @error('surname')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>{{ __('First Name') }} <span class="req">*</span></label>
                        <input type="text" name="first_name" value="{{ $getDefault('first_name') }}" placeholder="{{ __('First Name') }}" required>
                        @error('first_name')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>{{ __('Middle Name') }} <span class="req">*</span></label>
                        <input type="text" name="middle_name" id="middle_name" value="{{ $getDefault('middle_name') }}" placeholder="{{ __('Middle Name') }}" required>
                        @error('middle_name')<div class="error">{{ $message }}</div>@enderror
                        <div id="middle_name_warning" class="error" style="display:none; margin-top:5px;">{{ __('Please enter your complete middle name. Single initials such as A or A. are not accepted.') }}</div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>{{ __('Sex') }} <span class="req">*</span></label>
                        <div class="radio-group" style="margin-top:8px;">
                            <label class="radio-opt"><input type="radio" name="sex" value="Male" {{ $getDefault('sex')==='Male' ? 'checked' : '' }} required> {{ __('Male') }}</label>
                            <label class="radio-opt"><input type="radio" name="sex" value="Female" {{ $getDefault('sex')==='Female' ? 'checked' : '' }}> {{ __('Female') }}</label>
                        </div>
                        @error('sex')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>{{ __('Civil Status') }} <span class="req">*</span></label>
                        <select name="civil_status" required>
                            <option value="">-- {{ __('Select') }} --</option>
                            @foreach(['Single','Married','Widowed','Separated'] as $cs)
                                <option value="{{ $cs }}" {{ $getDefault('civil_status')===$cs ? 'selected' : '' }}>{{ __($cs) }}</option>
                            @endforeach
                        </select>
                        @error('civil_status')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-row three">
                    <div class="form-group">
                        <label>{{ __('Birthday') }} <span class="req">*</span></label>
                        <input type="date" name="birthday" value="{{ old('birthday', $application?->birthday?->format('Y-m-d') ?? $defaults['birthday'] ?? '') }}" required>
                        @error('birthday')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>{{ __('Age') }} <span class="req">*</span></label>
                        <input type="number" name="age" value="{{ $getDefault('age') }}" min="15" max="30" placeholder="15–30" required id="ageField">
                        @error('age')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>{{ __('Barangay') }} <span class="req">*</span></label>
                        <select name="barangay" required>
                            <option value="">-- {{ __('Select Barangay') }} --</option>
                            @foreach(['Abagao','Alaguia','Bagumbayan','Bangag','Bical','Bicud','Binag','Cabayabasan (Capacuan)','Cagoran','Cambong','Catayauan','Catugan','Centro (Poblacion)','Cullit','Dagupan','Dalaya','Fabrica','Fusina','Jurisdiction','Lalafugan','Logac','Magapit','Malanao','Maxingal','Naguilian','Paranum','Rosario','San Antonio (Lafu)','San Jose','San Juan','San Lorenzo','San Mariano','Santa Maria','Santa Teresa (Magallungon)','Tucalana'] as $b)
                                <option value="{{ $b }}" {{ $getDefault('barangay')===$b ? 'selected' : '' }}>{{ $b }}</option>
                            @endforeach
                        </select>
                        @error('barangay')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-row three">
                    <div class="form-group">
                        <label>{{ __('Your Educational Attainment') }} <span class="req">*</span></label>
                        <select name="education" required>
                            <option value="">-- {{ __('Select') }} --</option>
                            @foreach(['High School (Currently Enrolled)','High School Graduate','Senior High School (Currently Enrolled)','Senior High School Graduate','College (Currently Enrolled)','College Graduate','Vocational / Tech-Voc','Out-of-School Youth'] as $ed)
                                <option value="{{ $ed }}" {{ $getDefault('education')===$ed ? 'selected' : '' }}>{{ __($ed) }}</option>
                            @endforeach
                        </select>
                        @error('education')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>{{ __('Grade/Year Level') }} <span class="req">*</span></label>
                        <select name="grade_year_level" required>
                            <option value="">-- {{ __('Select') }} --</option>
                            @foreach(['Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12','1st year','2nd year','4th year','5th year'] as $level)
                                <option value="{{ $level }}" {{ $getDefault('grade_year_level')===$level ? 'selected' : '' }}>{{ __($level) }}</option>
                            @endforeach
                        </select>
                        @error('grade_year_level')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>{{ __('Parent Status') }} <span class="req">*</span></label>
                        <select name="parent_status" required>
                            <option value="">-- {{ __('Select') }} --</option>
                            @foreach(['Both Parents Living','Solo Parent','Orphan','Guardian'] as $ps)
                                <option value="{{ $ps }}" {{ $getDefault('parent_status')===$ps ? 'selected' : '' }}>{{ __($ps) }}</option>
                            @endforeach
                        </select>
                        @error('parent_status')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>{{ __('SPES Beneficiary Status') }} <span class="req">*</span></label>
                        <div class="radio-group" style="margin-top:8px;">
                            <label class="radio-opt">
                                <input type="radio" name="spes_status" value="new" {{ $getDefault('spes_status')==='new' ? 'checked' : '' }} required>
                                <span><strong>{{ __('New') }}</strong> — {{ __('First time applicant') }}</span>
                            </label>
                            <label class="radio-opt">
                                <input type="radio" name="spes_status" value="baby" {{ $getDefault('spes_status')==='baby' ? 'checked' : '' }}>
                                <span><strong>{{ __('SPES Baby') }}</strong> — {{ __('2nd or 3rd time beneficiary') }}</span>
                            </label>
                        </div>
                        @error('spes_status')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>{{ __('Facebook Profile') }} <span class="req">*</span></label>
                        <input type="text" name="facebook" value="{{ $getDefault('facebook') }}" placeholder="{{ __('e.g., https://facebook.com/yourprofile or your Facebook URL') }}" required>
                        @error('facebook')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 2: Family & Contact --}}
        <div class="form-card">
            <div class="form-card-header">
                <i class="fa-solid fa-people-roof"></i>
                <h2>{{ __('Family & Contact Information') }}</h2>
            </div>
            <div class="form-card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>{{ __('Mother\'s Name') }} <span class="req" data-family-required>*</span></label>
                        <input type="text" name="mother_name" value="{{ $getDefault('mother_name') }}" placeholder="{{ __('Full name') }}" data-family-field>
                        @error('mother_name')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>{{ __('Father\'s Name / Guardian\'s Name') }} <span class="req" data-family-required>*</span></label>
                        <input type="text" name="father_guardian_name" value="{{ $getDefault('father_guardian_name') }}" placeholder="{{ __('Full name') }}" data-family-field>
                        @error('father_guardian_name')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>{{ __('Mother\'s Occupation') }} <span class="req" data-family-required>*</span></label>
                        <input type="text" name="mother_occupation" value="{{ $getDefault('mother_occupation') }}" placeholder="{{ __('Enter occupation') }}" data-family-field>
                        @error('mother_occupation')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>{{ __('Father\'s Occupation') }} <span class="req" data-family-required>*</span></label>
                        <input type="text" name="father_occupation" value="{{ $getDefault('father_occupation') }}" placeholder="{{ __('Enter occupation') }}" data-family-field>
                        @error('father_occupation')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>{{ __('Mother\'s Contact Number') }} <span class="req" data-family-required>*</span></label>
                        <input type="text" name="mother_contact_no" value="{{ $getDefault('mother_contact_no') }}" placeholder="09XXXXXXXXX" maxlength="20" data-family-field>
                        @error('mother_contact_no')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label>{{ __('Father\'s Contact Number') }} <span class="req" data-family-required>*</span></label>
                        <input type="text" name="father_contact_no" value="{{ $getDefault('father_contact_no') }}" placeholder="09XXXXXXXXX" maxlength="20" data-family-field>
                        @error('father_contact_no')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>{{ __('Facebook Account') }}</label>
                        <input type="text" name="messenger" value="{{ $getDefault('messenger') }}" placeholder="{{ __('Facebook name or link (optional)') }}">
                        @error('messenger')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Documents --}}
        <div class="form-card">
            <div class="form-card-header">
                <i class="fa-solid fa-folder-open"></i>
                <h2>{{ __('Documentary Requirements') }}</h2>
                <p class="text-secondary">{{ __('Required for application verification.') }}</p>
            </div>
            <div class="form-card-body">
                <p class="document-upload-note">
                    {{ __('PDF files only. Maximum 5 MB per file.') }}
                </p>
                <div class="document-upload-grid">
                    <div class="document-upload">
                        <div class="document-upload-heading">
                            <label for="resume">{{ __('Birth Certificate') }}</label>
                            @if($hasCurrentBirthCertificate)
                                <span class="document-status"><i class="fa-solid fa-check" aria-hidden="true"></i> {{ __('On file') }}</span>
                            @else
                                <span class="document-status required"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> {{ __('Required') }}</span>
                            @endif
                        </div>
                        @if($hasCurrentBirthCertificate)
                            <div class="current-document">
                                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                                <div class="current-document-details">
                                    <small>{{ __('Current file') }}</small>
                                    <span class="document-filename" title="{{ $documentOriginalNames['resume'] ?? basename($application->resume) }}">
                                        {{ $documentOriginalNames['resume'] ?? basename($application->resume) }}
                                    </span>
                                </div>
                                <a class="view-document-link" href="{{ route('applications.document.preview', ['application' => $application->id, 'document' => 'resume']) }}" target="_blank" rel="noopener" aria-label="{{ __('View current Birth Certificate') }}">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i><span>{{ __('View') }}</span>
                                </a>
                            </div>
                        @endif
                        <label class="file-upload-area" for="resume">
                            <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
                            <strong>{{ __($hasCurrentBirthCertificate ? 'Choose a replacement PDF (optional)' : 'Choose PDF') }}</strong>
                            <span class="file-upload-hint">{{ __('Click to browse your files') }}</span>
                            <span class="file-name" id="resumeName" aria-live="polite"></span>
                            <input type="file" name="resume" id="resume" class="file-upload-input" accept=".pdf,application/pdf" @required(! $hasCurrentBirthCertificate)
                                onchange="validateDocument(this, 'resumeName')">
                        </label>
                        @error('resume')<div class="error">{{ $message }}</div>@enderror
                    </div>
                    <div class="document-upload">
                        <div class="document-upload-heading">
                            <label for="certificate_enrollment">{{ __('Certificate of Enrollment') }}</label>
                            @if($hasCurrentEnrollmentCertificate)
                                <span class="document-status"><i class="fa-solid fa-check" aria-hidden="true"></i> {{ __('On file') }}</span>
                            @else
                                <span class="document-status required"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> {{ __('Required') }}</span>
                            @endif
                        </div>
                        @if($hasCurrentEnrollmentCertificate)
                            <div class="current-document">
                                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                                <div class="current-document-details">
                                    <small>{{ __('Current file') }}</small>
                                    <span class="document-filename" title="{{ $documentOriginalNames['certificate_enrollment'] ?? basename($application->certificate_enrollment) }}">
                                        {{ $documentOriginalNames['certificate_enrollment'] ?? basename($application->certificate_enrollment) }}
                                    </span>
                                </div>
                                <a class="view-document-link" href="{{ route('applications.document.preview', ['application' => $application->id, 'document' => 'certificate_enrollment']) }}" target="_blank" rel="noopener" aria-label="{{ __('View current Certificate of Enrollment') }}">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i><span>{{ __('View') }}</span>
                                </a>
                            </div>
                        @endif
                        <label class="file-upload-area" for="certificate_enrollment">
                            <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
                            <strong>{{ __($hasCurrentEnrollmentCertificate ? 'Choose a replacement PDF (optional)' : 'Choose PDF') }}</strong>
                            <span class="file-upload-hint">{{ __('Click to browse your files') }}</span>
                            <span class="file-name" id="certificateEnrollmentName" aria-live="polite"></span>
                            <input type="file" name="certificate_enrollment" id="certificate_enrollment" class="file-upload-input" accept=".pdf,application/pdf" @required(! $hasCurrentEnrollmentCertificate)
                                onchange="validateDocument(this, 'certificateEnrollmentName')">
                        </label>
                        @error('certificate_enrollment')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <button type="submit" class="btn-submit" id="submitBtn">
                <i class="fa-solid fa-paper-plane"></i> {{ __($reapplying ? 'Reapply Application' : ($editing ? 'Update Application' : 'Submit Application')) }}
            </button>
            <a href="{{ route('dashboard') }}" class="btn-back"><i class="fa-solid fa-arrow-left"></i> {{ __('Cancel') }}</a>
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
<x-portal-help-chat />
</body>
</html>
