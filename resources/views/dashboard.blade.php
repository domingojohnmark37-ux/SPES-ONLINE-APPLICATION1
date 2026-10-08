@php
    $notes_u = auth()->user()->unreadNotifications()->get()->reject(function ($notification) {
        $data = $notification->data;

        return array_key_exists('status', $data)
            || str_contains(mb_strtolower((string) ($data['title'] ?? '')), 'application status');
    });
    $unread_u = $notes_u->count();
    $notes_u = $notes_u->take(8);
@endphp
<!DOCTYPE html>
<html lang="{{ request()->attributes->get('applicant_language', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('User Dashboard — SPES') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #8B0000;
            --primary-dark: #660000;
            --primary-light: #A52A2A;
            --accent: #FFD700;
            --accent-soft: #fff4bf;
            --success: #2e7d32;
            --info: #1565c0;
            --danger: #c62828;
            --bg: #f0f2f5;
            --white: #fff;
            --text: #212121;
            --text-muted: #6b7280;
            --border: #e0e0e0;
            --shadow: 0 2px 12px rgba(0,0,0,.08);
            --sidebar-w: 220px;
        }
        body { font-family: 'Segoe UI', Roboto, Arial, sans-serif; background: var(--bg); color: var(--text); }

        /* Sidebar */
        .sidebar {
            position: fixed; top: 0; left: 0; width: var(--sidebar-w); height: 100vh;
            background: var(--primary-dark); display: flex; flex-direction: column; z-index: 100;
            padding-bottom: 12px; overflow-y: auto; overflow-x: hidden;
        }
        .sidebar-brand {
            padding: 22px 20px 18px; border-bottom: 1px solid rgba(255,255,255,.08);
            display: flex; align-items: center; gap: 12px;
        }
        .sidebar-brand img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .sidebar-brand span { font-size: 1rem; font-weight: 800; color: #fff; line-height: 1.1; }
        .sidebar-brand small { display: block; font-size: .72rem; color: rgba(255,255,255,.72); }
        .sidebar-nav { padding: 14px 10px; flex: 1; display: flex; flex-direction: column; gap: 4px; }
        .nav-link {
            display: flex; align-items: center; gap: 10px; width: 100%; min-height: 38px; color: rgba(255,255,255,.88);
            text-decoration: none; padding: 9px 10px; border-radius: 9px; font-size: .86rem;
            transition: background .18s, color .18s, transform .08s; margin-bottom: 0;
        }
        .nav-link i { width: 18px; text-align: center; }
        .nav-link:hover { background: rgba(255,255,255,.08); transform: translateX(2px); color: #fff; }
        button.nav-link { background: transparent; border: 0; font: inherit; text-align: left; cursor: pointer; }
        .nav-link.active {
            background: var(--accent); color: var(--primary-dark); font-weight: 700; box-shadow: inset 0 0 0 1px rgba(0,0,0,.05);
        }
        .nav-link.active i { color: var(--primary-dark); }
        .nav-count { margin-left: auto; min-width: 18px; padding: 2px 5px; border-radius: 999px; background: #e53935; color: #fff; font-size: .68rem; font-weight: 800; text-align: center; }
        .sidebar-user {
            padding: 16px 14px; border-top: 1px solid rgba(255,255,255,.08);
            display: flex; gap: 12px; align-items: center; margin-top: auto;
        }
        .sidebar-user img {
            width: 46px; height: 46px; border-radius: 50%; border: 2px solid rgba(255,255,255,.08);
            box-shadow: 0 2px 6px rgba(0,0,0,.12);
        }
        .sidebar-user .meta { color: #fff; }
        .sidebar-user .meta b { display: block; font-size: .98rem; }

        /* Topbar */
        .topbar {
            position: fixed; top: 0; left: var(--sidebar-w); right: 0; height: 72px;
            background: var(--white); box-shadow: var(--shadow); display: flex; align-items: center;
            justify-content: space-between; padding: 0 22px; z-index: 90;
        }
        .topbar .brand { display: flex; align-items: center; gap: 12px; }
        .topbar h1 { font-size: var(--type-title); font-weight: 700; color: var(--type-primary-color); }
        .topbar p { font-size: var(--type-secondary); color: var(--type-secondary-color); margin-top: .25rem; }
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .date-pill {
            background: #f9f5ea; border: 1px solid var(--border); padding: 8px 12px;
            border-radius: 10px; color: var(--primary); font-weight: 700;
        }
        .notif { position:relative; }
        .notif .bell { position:relative; display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:10px; background:#f3f6f5; cursor:pointer; }
        .notif .count { position:absolute; top:-6px; right:-6px; background:#e53935; color:#fff; font-size:.72rem; padding:3px 6px; border-radius:999px; font-weight:700; }
        .notif-dropdown { position:absolute; right:0; top:48px; width:320px; background:#fff; box-shadow:0 10px 30px rgba(0,0,0,.08); border-radius:10px; display:none; z-index:120; }
        .notif-dropdown.open { display:block; }
        .notif-item { padding:12px; border-bottom:1px solid #f1f5f6; display:flex; gap:10px; align-items:flex-start; }
        .notif-item:last-child { border-bottom:none; }
        .notif-item .meta { font-size:.9rem; }
        .notif-empty { padding:12px; color:#6b7680; }
        .user-pill { display: flex; align-items: center; gap: 10px; }
        .user-pill img { width: 38px; height: 38px; border-radius: 50%; }

        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-size: .75rem; font-weight: 600; white-space: nowrap; }
        .badge-pending  { background: #fff8e1; color: #e65100; }
        .badge-approved { background: #e8f5e9; color: #2e7d32; }
        .badge-denied   { background: #ffebee; color: #c62828; }
        .badge-new      { background: #e3f2fd; color: #1565c0; }
        .badge-baby     { background: #f3e5f5; color: #6a1b9a; }

        /* Main */
        .page-wrapper { margin-left: var(--sidebar-w); padding-top: 72px; }
        .page-content { padding: 20px; max-width: 1600px; margin: 0 auto; }

        /* Cards */
        .card {
            background: var(--white); border-radius: 12px; box-shadow: var(--shadow);
            overflow: hidden; margin-bottom: 20px; border: 1px solid rgba(139,0,0,.04);
        }
        .card-header { padding: 16px 18px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
        .card-header h2 { font-size: var(--type-section); font-weight: 600; color: var(--type-primary-color); }
        .card-body { padding: 18px; }

        /* Stat cards */
        .stats-row { display: grid; grid-template-columns: repeat(4,minmax(0,1fr)); gap: 12px; margin-bottom: 16px; }
        .stat-card {
            background: var(--white); border-radius: 12px; padding: 18px; box-shadow: var(--shadow);
            display:flex; align-items:center; gap:14px; border: 1px solid rgba(139,0,0,.04);
        }
        .stat-icon {
            width: 54px; height: 54px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem; flex-shrink: 0;
        }
        .stat-num { font-size: 1.4rem; font-weight: 600; color: var(--type-primary-color); line-height: 1.2; }
        .stat-label { font-size: var(--type-secondary); color: var(--type-secondary-color); margin-top: .3rem; }

        /* Hero / welcome */
        .layout-grid { display: grid; grid-template-columns: minmax(0,1.75fr) minmax(280px,1fr); gap: 14px; align-items: start; }
        .hero { display: flex; gap: 18px; align-items: center; padding: 20px; }
        .hero-ill {
            width: 84px; height: 84px; background: linear-gradient(135deg, var(--accent-soft), #f8f3d9);
            border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 0 0 1px rgba(139,0,0,.04);
        }
        .hero h3 { font-size: var(--type-section); color: var(--type-primary-color); font-weight:600; margin-bottom: .3rem; }
        .hero p { color: var(--type-secondary-color); font-size:var(--type-secondary); line-height:var(--type-line-height); }
        .quick-actions { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; }
        .qa {
            padding: 12px; border-radius: 10px; background: linear-gradient(180deg, #fffaf0 0%, #fff4bf 100%);
            text-align: center; font-weight: 700; color: var(--primary); border: 1px solid rgba(255,215,0,.45);
            text-decoration: none;
        }

        /* Progress steps horizontal */
        .progress { background: var(--white); padding: 18px; border-radius: 12px; box-shadow: var(--shadow); }
        .progress-track { display: flex; gap: 12px; align-items: center; justify-content: space-between; }
        .progress-step { flex: 1; display: flex; align-items: center; gap: 10px; }
        .progress-step .dot {
            width: 36px; height: 36px; border-radius: 50%; display:flex; align-items:center; justify-content:center;
            font-weight: 800; color: var(--primary-dark); background: #f4e7e7;
        }
        .progress-step.active .dot { background: var(--accent); color: var(--primary-dark); }

        /* How to Apply list */
        .how-list { display: flex; flex-direction: column; gap: 10px; }
        .how-item {
            background: var(--white); color:var(--type-primary-color); border-radius: 10px; padding: 12px; border: 1px solid var(--border);
            display: flex; gap: 12px; align-items: flex-start;
        }
        .how-item .num {
            width: 34px; height: 34px; border-radius: 50%; background: var(--primary); color: #fff;
            display: flex; align-items: center; justify-content: center; font-weight: 800;
        }
        .dashboard-heading { margin: 0 0 14px; color: var(--type-secondary-color); font-size: var(--type-secondary); line-height:var(--type-line-height); }
        .summary-card { min-width: 0; align-items: flex-start; padding: 14px; gap: 10px; }
        .summary-card .stat-icon { width: 42px; height: 42px; font-size: 1rem; border-radius: 10px; }
        .summary-card .stat-num { font-size: 1.18rem; }
        .summary-card .stat-label { font-size: var(--type-secondary); line-height: 1.4; }
        .summary-card a { display: inline-block; margin-top: .45rem; color: var(--info); font-size: var(--type-caption); font-weight: 600; text-decoration: none; }
        .summary-card a:hover { text-decoration: underline; }
        .summary-card .summary-progress { height: 6px; width: min(130px,100%); margin-top: 9px; border-radius: 99px; background: #edf0f2; overflow: hidden; }
        .summary-card .summary-progress span { display: block; height: 100%; border-radius: inherit; background: var(--accent); }
        .dashboard-section { margin-top: 14px; }
        .dashboard-section .card-header a { color: var(--info); font-size: .76rem; font-weight: 700; text-decoration: none; }
        .announcement-list { display: grid; gap: 0; }
        .announcement-item { padding: 12px 0; border-bottom: 1px solid #edf0f2; }
        .announcement-item:first-child { padding-top: 0; }
        .announcement-item:last-child { padding-bottom: 0; border-bottom: 0; }
        .announcement-item h3 { margin: .3rem 0; color: var(--type-primary-color); font-size: var(--type-body); font-weight:600; }
        .announcement-item p { color: var(--type-secondary-color); font-size: var(--type-secondary); line-height: var(--type-line-height); }
        .announcement-date { color: var(--type-caption-color); font-size: var(--type-caption); }
        .notifications-list { display:grid; }
        .notification-item { display:grid; grid-template-columns:40px minmax(0,1fr) auto; align-items:center; gap:12px; padding:13px 6px; border-bottom:1px solid var(--border); }
        .notification-item:first-child { padding-top:0; }
        .notification-item:last-child { border-bottom:0; }
        .notifications-page-heading { margin:22px 0 14px; }
        .notifications-page-heading h2 { color:var(--type-primary-color); font-size:var(--type-title); font-weight:700; }
        .notifications-page-heading p { margin-top:4px; color:var(--type-secondary-color); font-size:var(--type-caption); }
        .notification-icon { display:grid; width:38px; height:38px; place-items:center; border-radius:50%; background:#f6eeee; color:var(--primary); }
        .notification-copy { min-width:0; }
        .notification-copy h3 { margin:0 0 3px; color:var(--type-primary-color); font-size:var(--type-secondary); font-weight:700; }
        .notification-copy p { margin:0; color:var(--type-secondary-color); font-size:var(--type-caption); line-height:1.5; }
        .notification-time { color:var(--type-caption-color); font-size:var(--type-caption); white-space:nowrap; }
        .notification-actions { display:flex; justify-content:center; padding:16px 0 4px; }
        .notification-view-all { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:38px; padding:8px 18px; border:1px solid var(--accent); border-radius:999px; color:#806000; background:transparent; font-size:var(--type-caption); font-weight:700; text-decoration:none; transition:background .18s,color .18s; }
        .notification-view-all:hover { background:var(--accent); color:#212121; }
        .notification-view-all:focus-visible { outline:3px solid var(--primary); outline-offset:3px; }
        .quick-action-list { display: grid; gap: 8px; }
        .quick-action-link { display: flex; align-items: center; gap: 10px; padding: 11px 12px; border: 1px solid var(--border); border-radius: 9px; color: var(--text); font-size: .84rem; font-weight: 700; text-decoration: none; }
        .quick-action-link i { width: 18px; color: var(--primary); }
        .quick-action-link:hover { border-color: var(--primary); background: #fffafa; }
        .requirement-list { display: grid; gap: 11px; }
        .requirement-item { display: flex; align-items: center; justify-content: space-between; gap: 10px; font-size: .82rem; }
        .requirement-item span:first-child { display: flex; align-items: center; gap: 8px; }
        .requirement-item i { color: var(--primary); }
        .requirement-state { color: var(--text-muted); font-size: .73rem; white-space: nowrap; }
        .requirement-state.complete { color: var(--success); font-weight: 700; }
        .empty-announcements { color: var(--text-muted); font-size: .86rem; line-height: 1.5; }

        /* Misc */
        .btn {
            display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 8px;
            font-size: .9rem; font-weight: 700; border: none; cursor: pointer; text-decoration: none;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { opacity: .92; }
        .btn-outline { background: transparent; border: 1.5px solid var(--primary); color: var(--primary); }

        .hamburger { display: none; background: none; border: none; font-size: 1.2rem; cursor: pointer; color: var(--primary); margin-right: 10px; }
        @media(max-width:900px) { .hamburger { display:block; } }

        /* Sidebar logout button */
        .sidebar-footer { padding: 12px; }
        .btn-logout {
            display: inline-flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 8px;
            background: #fff; color: var(--primary-dark); border: 1px solid rgba(0,0,0,.08); font-weight: 700;
            box-shadow: 0 2px 6px rgba(0,0,0,.06);
        }
        .btn-logout i { color: var(--primary-dark); }
        .btn-logout:hover { transform: translateY(-1px); }

        @media(max-width:900px) {
            .layout-grid { grid-template-columns: 1fr; }
            .stats-row { grid-template-columns: repeat(2,minmax(0,1fr)); }
            .topbar { left: 0; }
            .page-wrapper { margin-left: 0; padding-top: 72px; }
            .sidebar { transform: translateX(-100%); transition: transform .24s; }
            .sidebar.open { transform: translateX(0); }
        }
        @media(max-width:767.98px) {
            .topbar { height: 60px; padding: 0 12px; gap: 8px; }
            .topbar .brand { min-width: 0; flex: 1; gap: 8px; }
            .topbar h1 { overflow: hidden; font-size: var(--type-section); text-overflow: ellipsis; white-space: nowrap; }
            .topbar p { display: none; }
            .hamburger { flex: 0 0 auto; margin-right: 0; }
            .topbar-right { flex: 0 0 auto; gap: 8px; }
            .user-pill { display: none; }
            .page-wrapper { padding-top: 60px; }
            .page-content { min-width: 0; padding: 14px; }
            .stats-row { grid-template-columns: repeat(2,minmax(0,1fr)); gap: 10px; }
            .stat-card { min-width: 0; padding: 14px; }
            .summary-card { padding: 12px; gap: 8px; }
            .summary-card .stat-icon { width: 34px; height: 34px; font-size: .88rem; }
            .summary-card .stat-num { font-size: 1rem; }
            .stat-card > div:last-child { min-width: 0; }
            .layout-grid, .layout-grid > div { min-width: 0; }
            .layout-grid { gap: 14px; }
            .hero {
                display: grid;
                grid-template-columns: 52px minmax(0, 1fr);
                gap: 12px;
                padding: 16px;
            }
            .hero-ill { width: 52px; height: 52px; }
            .hero h3 { font-size: 1rem; }
            .hero p { font-size: .9rem; line-height: 1.45; overflow-wrap: anywhere; }
            .hero > div:nth-child(2) { min-width: 0; }
            .hero > div:last-child { grid-column: 1 / -1; min-width: 0 !important; width: 100%; }
            .hero > div:last-child .qa { min-width: 0; padding: 10px 6px; font-size: .82rem; }
            .progress { min-width: 0; padding: 14px; }
            .progress .card-body { padding: 14px 0 0; }
            .progress-track {
                justify-content: flex-start;
                overflow-x: auto;
                overscroll-behavior-x: contain;
                padding-bottom: 8px;
            }
            .progress-step { flex: 0 0 112px; }
            .progress-step > div:last-child { font-size: .78rem !important; }
            .notification-item { grid-template-columns:34px minmax(0,1fr); gap:9px; padding:12px 0; }
            .notification-icon { width:32px; height:32px; }
            .notification-time { grid-column:2; grid-row:2; white-space:normal; }
            body > footer {
                margin-left: 0 !important;
                padding: 14px 16px !important;
                flex-direction: column;
                align-items: flex-start;
            }
            body > footer > span:last-child { display: flex; flex-wrap: wrap; gap: 10px 14px; }
            body > footer a { overflow-wrap: anywhere; }
        }
    </style>
</head>
<body>

<x-applicant-sidebar />

<header class="topbar">
    <div class="brand" style="display:flex;align-items:center;">
        <button class="hamburger" type="button" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false" aria-label="{{ __('Toggle applicant navigation') }}"><i class="fa-solid fa-bars" aria-hidden="true"></i></button>
        <div>
            <h1>{{ __('Welcome back, :name!', ['name' => explode(' ', Auth::user()->name)[0]]) }}</h1>
            <p>{{ __('SPES Applicant Portal') }}</p>
        </div>
    </div>
    <div class="topbar-right">
        <div class="notif">
            <div class="bell" id="notifBellUser" title="{{ __('Notifications') }}">
                <i class="fa-solid fa-bell" style="color:var(--primary);"></i>
                @if($unread_u > 0)
                    <div class="count" id="notifCountUser">{{ $unread_u }}</div>
                @endif
            </div>
            <div class="notif-dropdown" id="notifDropdownUser">
                @if($notes_u->count())
                    @foreach($notes_u as $n)
                        <div class="notif-item" data-id="{{ $n->id }}">
                            <div style="width:36px;height:36px;border-radius:8px;background:#eef7ff;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-info" style="color:#1e6fb3"></i></div>
                            <div class="meta">
                                <div style="font-weight:700;">{{ $n->data['message'] ?? 'Notification' }}</div>
                                <div style="font-size:.8rem;color:#6b7680;margin-top:4px;">{{ optional($n->created_at)->diffForHumans() }}</div>
                            </div>
                        </div>
                    @endforeach
                    <div style="padding:10px;text-align:center;border-top:1px solid #f1f5f6;"><a href="#" id="markAllReadUser" style="color:var(--primary);text-decoration:none;font-weight:700;">{{ __('Mark all as read') }}</a></div>
                @else
                    <div class="notif-empty">{{ __('No new notifications') }}</div>
                @endif
            </div>
        </div>
        <div class="user-pill">
            <img src="{{ Auth::user()->profile_photo_url ?? asset('images/avatar.png') }}" alt="User">
            <div style="text-align:left;">
                <div style="font-weight:700;color:var(--primary);">{{ Auth::user()->name }}</div>
                <div style="font-size:.78rem;color:var(--text-muted);">{{ Auth::user()->email }}</div>
            </div>
        </div>
        </div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const bellU = document.getElementById('notifBellUser');
    const ddU = document.getElementById('notifDropdownUser');
    const markAllU = document.getElementById('markAllReadUser');
    if (bellU) {
        bellU.addEventListener('click', ()=> ddU.classList.toggle('open'));
        document.addEventListener('click', (e)=>{ if (!bellU.contains(e.target) && !ddU.contains(e.target)) ddU.classList.remove('open'); });
    }
    if (markAllU) {
        markAllU.addEventListener('click', function(e){ e.preventDefault(); fetch('{{ route('notifications.readAll') }}', { method: 'POST', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content') } }).then(()=>{ document.getElementById('notifCountUser')?.remove(); ddU.innerHTML = '<div class="notif-empty">' + @json(__('No new notifications')) + '</div>'; }) });
    }
    ddU?.addEventListener('click', function(e){ let item = e.target.closest('.notif-item'); if (!item) return; const id = item.getAttribute('data-id'); fetch('/notifications/'+id+'/read', { method:'POST', headers:{'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content') } }).then(()=> { item.remove(); const cnt = document.getElementById('notifCountUser'); if (cnt) { let v = parseInt(cnt.innerText)-1; if (v<=0) cnt.remove(); else cnt.innerText = v; } }); });
});
</script>
</header>

<div class="page-wrapper">
<div class="page-content">

    @if(session('success'))
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="alert alert-info"><i class="fa-solid fa-circle-info"></i> {{ session('info') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}</div>
    @endif

    @php
        $uploadedRequirements = $application
            ? (int) filled($application->resume) + (int) filled($application->certificate_enrollment)
            : 0;
    @endphp

    {{-- Application overview --}}
    <div class="stats-row" style="grid-template-columns:repeat(2,minmax(0,1fr));">
        <div class="stat-card summary-card">
            <div class="stat-icon" style="background:#e7f9f1;color:#0b6f45;"><i class="fa-solid fa-file-circle-check"></i></div>
            <div>
                <div class="stat-num">{{ __($application ? 'Submitted' : 'Not started') }}</div>
                <div class="stat-label">{{ __('Application') }}</div>
                <a href="{{ $application ? route('applications.myApplication') : route('applications.create') }}">{{ __($application ? 'View details' : 'Start application') }} →</a>
            </div>
        </div>
        <div class="stat-card summary-card">
            <div class="stat-icon" style="background:#fff4bf;color:#946b00;"><i class="fa-solid fa-folder-open"></i></div>
            <div>
                <div class="stat-num">{{ $uploadedRequirements }} / 2</div>
                <div class="stat-label">{{ __('Required documents') }}</div>
                <a href="{{ route('applicant.requirements') }}">{{ __('View additional requirements') }} →</a>
            </div>
        </div>
    </div>

    <div class="layout-grid">
        <div>
            <div class="card">
                <div class="card-body hero">
                    <div class="hero-ill"><i class="fa-solid fa-clipboard-check" style="font-size:28px;color:var(--primary);"></i></div>
                    <div style="flex:1;">
                        <h2 class="text-primary-line">{{ __('Welcome back, :name!', ['name' => explode(' ', Auth::user()->name)[0]]) }}</h2>
                        <p class="text-secondary">{{ __($application ? 'Your application is on file. Review your submitted information and documents here.' : 'Start your SPES application by completing your personal information and required documents.') }}</p>
                        <div style="margin-top:12px;"><a href="{{ $application ? route('applications.myApplication') : route('applications.create') }}" class="btn btn-primary">{{ __($application ? 'View My Application' : 'Apply Now') }}</a></div>
                    </div>
                </div>
            </div>

            <div class="card dashboard-section" id="announcements">
                <div class="card-header">
                    <h2 class="text-section"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i> {{ __('Announcements') }}</h2>
                    @if($application && $application->status === 'approved')
                        <a href="{{ route('updates') }}">{{ __('View all') }} →</a>
                    @endif
                </div>
                <div class="card-body">
                    @if($announcements->isNotEmpty())
                        <div class="announcement-list">
                            @foreach($announcements as $announcement)
                                <article class="announcement-item">
                                    <span class="announcement-date text-caption">{{ optional($announcement->published_at)->format('M j, Y') }}</span>
                                    <h3 class="text-primary-line">{{ $announcement->title }}</h3>
                                    <p class="text-secondary">{{ \Illuminate\Support\Str::limit(strip_tags($announcement->content), 150) }}</p>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <p class="empty-announcements">{{ __('There are no current announcements. Check back here for SPES updates.') }}</p>
                    @endif
                </div>
            </div>

            <div class="card dashboard-section" id="appointments">
                <div class="card-header">
                    <h2 class="text-section"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> {{ __('Upcoming Appointments') }}</h2>
                </div>
                <div class="card-body">
                    @if($appointments->isNotEmpty())
                        <div class="announcement-list">
                            @foreach($appointments as $appointment)
                                <article class="announcement-item">
                                    <span class="announcement-date text-caption">{{ __('Attend on') }} {{ $appointment->starts_at->format('M j, Y · g:i A') }}</span>
                                    <h3 class="text-primary-line">{{ $appointment->title }}</h3>
                                    @if($appointment->location)
                                        <p class="text-secondary"><i class="fa-solid fa-location-dot"></i> {{ $appointment->location }}</p>
                                    @endif
                                    @if($appointment->description)
                                        <p class="text-secondary">{{ $appointment->description }}</p>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @else
                        <x-info-item class="empty-announcements" description="{{ __('Check here again for schedule updates.') }}">{{ __('There are no upcoming appointments at this time.') }}</x-info-item>
                    @endif
                </div>
            </div>
        </div>

        <div>
            <div class="card">
                <div class="card-header"><h2 class="text-section">{{ __('Quick Actions') }}</h2></div>
                <div class="card-body">
                    <div class="quick-action-list">
                        <a class="quick-action-link" href="{{ $application ? route('applications.myApplication') : route('applications.create') }}">
                            <i class="fa-solid fa-file-circle-plus"></i>{{ __($application ? 'View My Application' : 'Start Application') }}
                        </a>
                        <a class="quick-action-link" href="{{ route('profile.edit') }}">
                            <i class="fa-solid fa-user"></i>{{ __('Profile') }}
                        </a>
                        <a class="quick-action-link" href="#announcements">
                            <i class="fa-solid fa-bullhorn"></i>{{ __('View Announcements') }}
                        </a>
                        <a class="quick-action-link" href="{{ route('applicant.appointments.index') }}">
                            <i class="fa-solid fa-calendar-check"></i>{{ __('View Appointments') }}
                        </a>
                        <a class="quick-action-link" href="{{ route('applicant.notifications.recent') }}">
                            <i class="fa-solid fa-bullhorn"></i>{{ __('Recent Notifications') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="card dashboard-section" id="profile-completion">
                <div class="card-header"><h2 class="text-section">{{ __('Profile Completion') }}</h2></div>
                <div class="card-body">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                        <strong class="text-primary-line">{{ $profileCompletion }}% {{ __('complete') }}</strong>
                        <a href="{{ route('profile.edit') }}" style="color:var(--info);font-weight:700;text-decoration:none;">{{ __($profileCompletion === 100 ? 'Review profile' : 'Complete profile') }} →</a>
                    </div>
                    <div class="summary-progress" role="progressbar" aria-label="Profile completion" aria-valuenow="{{ $profileCompletion }}" aria-valuemin="0" aria-valuemax="100">
                        <span style="width:{{ $profileCompletion }}%;"></span>
                    </div>
                    <p class="text-secondary" style="margin-top:.6rem;">{{ __('Keep your personal and contact details up to date.') }}</p>
                </div>
            </div>

            <div class="card dashboard-section" id="requirements">
                <div class="card-header"><h2 class="text-section">{{ __('Required Documents') }}</h2></div>
                <div class="card-body">
                    <div class="requirement-list">
                        <div class="requirement-item">
                            <span class="text-primary-line"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i> {{ __('Birth Certificate') }}</span>
                            <span class="requirement-state text-caption {{ $application && filled($application->resume) ? 'complete' : '' }}">
                                {{ __($application && filled($application->resume) ? 'Submitted' : 'Required') }}
                            </span>
                        </div>
                        <div class="requirement-item">
                            <span class="text-primary-line"><i class="fa-solid fa-file-circle-check" aria-hidden="true"></i> {{ __('Certificate of Enrollment') }}</span>
                            <span class="requirement-state text-caption {{ $application && filled($application->certificate_enrollment) ? 'complete' : '' }}">
                                {{ __($application && filled($application->certificate_enrollment) ? 'Submitted' : 'Required') }}
                            </span>
                        </div>
                    </div>
                    <a class="btn btn-outline" style="margin-top:14px;" href="{{ route('applicant.requirements') }}">{{ __('View requirements') }}</a>
                </div>
            </div>

            <div class="card dashboard-section">
                <div class="card-header"><h2 class="text-section">{{ __('How to Apply') }}</h2></div>
                <div class="card-body">
                    <div class="how-list">
                        <div class="how-item"><div class="num" aria-hidden="true">1</div><x-info-item :description="__('Gather your Birth Certificate, Application Letter, and Certificate of Indigency.')">{{ __('Prepare your documents') }}</x-info-item></div>
                        <div class="how-item"><div class="num" aria-hidden="true">2</div><x-info-item :description="__('Click Apply Now and complete all required fields.')">{{ __('Fill out the form') }}</x-info-item></div>
                        <div class="how-item"><div class="num" aria-hidden="true">3</div><x-info-item :description="__('Upload PDF files only. Each file must be 5 MB or smaller.')">{{ __('Upload your documents') }}</x-info-item></div>
                        <div class="how-item"><div class="num" aria-hidden="true">4</div><x-info-item :description="__('The PESO officer will review your application.')">{{ __('Submit and wait') }}</x-info-item></div>
                        <div class="how-item"><div class="num" aria-hidden="true">5</div><x-info-item :description="__('Review any admin comments in My Application.')">{{ __('Check for feedback') }}</x-info-item></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</div>

{{-- Footer --}}
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
