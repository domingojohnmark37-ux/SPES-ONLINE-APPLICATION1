<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Profile — SPES Applicant Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
        .sidebar { position: fixed; top:0; left:0; width:var(--sidebar-w); height:100vh; background:var(--primary-dark); display:flex; flex-direction:column; z-index:100; }
        .sidebar-brand { padding:22px 20px 18px; border-bottom:1px solid rgba(255,255,255,.1); display:flex; align-items:center; gap:12px; }
        .sidebar-brand img { width:40px; height:40px; border-radius:50%; object-fit:cover; }
        .sidebar-brand span { font-size:.95rem; font-weight:700; color:#fff; line-height:1.2; }
        .sidebar-brand small { display:block; font-size:.7rem; color:rgba(255,255,255,.5); }
        .sidebar-nav { padding:16px 12px; flex:1; }
        .nav-link { display:flex; align-items:center; gap:12px; color:rgba(255,255,255,.95); text-decoration:none; padding:14px 16px; border-radius:12px; font-size:1rem; transition:background .18s, transform .08s; margin-bottom:10px; }
        .nav-link i { width:16px; text-align:center; }
        .nav-link:hover { background:rgba(255,255,255,.04); transform:translateX(2px); }
        .nav-link.active { background:rgba(255,255,255,.12); box-shadow:none; font-weight:700; color:#fff; }
        .sidebar-footer { padding:14px 12px; border-top:1px solid rgba(255,255,255,.1); }
        .btn-logout { background:none; border:1.5px solid rgba(255,255,255,.3); color:rgba(255,255,255,.8); padding:9px 14px; border-radius:7px; cursor:pointer; font-size:.82rem; display:flex; align-items:center; gap:7px; width:100%; justify-content:center; transition:background .2s; }
        .btn-logout:hover { background:rgba(255,255,255,.1); color:#fff; }
        .topbar { position:fixed; top:0; left:var(--sidebar-w); right:0; height:62px; background:var(--white); box-shadow:var(--shadow); display:flex; align-items:center; justify-content:space-between; padding:0 24px; z-index:90; }
        .topbar h1 { font-size:1.15rem; font-weight:700; color:var(--primary); }
        .topbar p { font-size:.78rem; color:var(--text-muted); margin-top:1px; }
        .page-wrapper { margin-left:var(--sidebar-w); padding-top:62px; }
        .page-content { padding:24px; }
        .page-title { margin-bottom:20px; }
        .page-title h1 { font-size:1.5rem; font-weight:800; color:#102a43; }
        .page-title p { color:#475569; margin-top:6px; }
        .card { background:var(--white); border-radius:18px; box-shadow:var(--shadow); overflow:hidden; margin-bottom:20px; }
        .page-grid { display:grid; grid-template-columns:1fr; gap:20px; }
        @media (min-width: 1024px) { .page-grid { grid-template-columns: 1fr; } }
        @media(max-width:768px) { .sidebar { transform:translateX(-100%); transition:transform .3s; } .sidebar.open { transform:translateX(0); } .topbar, .page-wrapper { margin-left:0; left:0; } }
        .hamburger { display:none; background:none; border:none; font-size:1.2rem; cursor:pointer; color:var(--primary); margin-right:10px; }
        @media(max-width:768px) { .hamburger { display:block; } }
    </style>
</head>
<body>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="{{ asset('images/welcome_logo.jpg') }}" alt="SPES">
        <div><span>SPES Portal<small>PESO Camalaniugan</small></span></div>
    </div>
    <nav class="sidebar-nav">
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-house"></i> Dashboard
        </a>
        <a href="{{ route('applications.myApplication') }}" class="nav-link {{ request()->routeIs(['applications.myApplication', 'applications.form2', 'applications.form2.store']) ? 'active' : '' }}">
            <i class="fa-solid fa-file-lines"></i> My Application
        </a>
        <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}">
            <i class="fa-solid fa-user-pen"></i> Edit Profile
        </a>
        <a href="{{ auth()->user()->applications()->where('status', 'denied')->exists() ? route('applications.edit') : route('applications.create') }}" class="nav-link {{ request()->routeIs(['applications.create', 'applications.store', 'applications.edit']) ? 'active' : '' }}">
            <i class="fa-solid {{ auth()->user()->applications()->where('status', 'denied')->exists() ? 'fa-rotate-right' : 'fa-file-circle-plus' }}"></i> {{ auth()->user()->applications()->where('status', 'denied')->exists() ? 'Reapply' : 'Apply Now' }}
        </a>
        @if(auth()->user()->applications()->where('status', 'approved')->exists())
            <a href="{{ route('updates') }}" class="nav-link {{ request()->routeIs('updates') ? 'active' : '' }}">
                <i class="fa-solid fa-newspaper"></i> Updates
            </a>
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
        <div>
            <h1>Welcome, {{ Auth::user()->name }}!</h1>
            <p>SPES Applicant Portal</p>
        </div>
    </div>
    <span style="font-size:.82rem;color:var(--text-muted);">{{ Auth::user()->email }}</span>
</header>

<div class="page-wrapper">
    <div class="page-content">
        @if (session('status') === 'profile-updated')
            <div class="card" style="border-left:4px solid #22c55e;padding:16px 20px;">
                <p class="text-slate-700"><i class="fa-solid fa-circle-check"></i> Profile updated successfully.</p>
            </div>
        @endif

        <div class="page-title">
            <h1>Profile</h1>
            <p>Manage your account details and profile information.</p>
        </div>

        <div class="page-grid">
            <div class="card p-6">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>
    </div>
</div>

</body>
</html>
