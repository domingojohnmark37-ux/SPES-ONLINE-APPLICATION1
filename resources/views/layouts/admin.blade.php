<!DOCTYPE html>
@php
    $applicationBatchYear = \App\Models\SystemSetting::query()->first()?->application_start_date?->year ?? now()->year;
@endphp
<html lang="{{ auth()->user()->adminPreference?->language === 'fil' ? 'fil' : 'en' }}"
      data-admin-theme="{{ auth()->user()->adminPreference?->theme ?? 'system' }}"
      data-admin-sidebar="{{ auth()->user()->adminPreference?->sidebar_behavior ?? 'auto' }}"
      data-admin-font-size="{{ auth()->user()->adminPreference?->font_size ?? 'medium' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Admin')) — {{ __('SPES Management System') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #8B0000;
            --primary-dark: #660000;
            --primary-light: #A52A2A;
            --accent: #FFD700;
            --accent2: #FFA500;
            --danger: #c62828;
            --success: #2e7d32;
            --info: #1565c0;
            --bg: #f0f2f5;
            --sidebar-w: 260px;
            --white: #fff;
            --text: #212121;
            --text-muted: #757575;
            --border: #e0e0e0;
            --shadow: 0 2px 12px rgba(0,0,0,.08);
        }
        html[data-admin-theme="dark"] {
            color-scheme: dark;
            --bg: #17191d;
            --white: #24272d;
            --text: #f2f4f7;
            --text-muted: #c0c6d0;
            --border: #434852;
            --shadow: 0 2px 12px rgba(0,0,0,.3);
        }
        @media (prefers-color-scheme: dark) {
            html[data-admin-theme="system"] {
                color-scheme: dark;
                --bg: #17191d;
                --white: #24272d;
                --text: #f2f4f7;
                --text-muted: #c0c6d0;
                --border: #434852;
                --shadow: 0 2px 12px rgba(0,0,0,.3);
            }
        }
        html[data-admin-font-size="small"] { font-size: 14px; }
        html[data-admin-font-size="medium"] { font-size: 16px; }
        html[data-admin-font-size="large"] { font-size: 18px; }
        html[data-admin-sidebar="collapsed"][data-admin-sidebar-open="true"] { --sidebar-w: 260px; }
        @media (min-width: 901px) {
            html[data-admin-sidebar="collapsed"]:not([data-admin-sidebar-open="true"]) { --sidebar-w: 76px; }
            html[data-admin-sidebar="collapsed"]:not([data-admin-sidebar-open="true"]) .sidebar-brand { justify-content: center; padding-right: 8px; padding-left: 8px; }
            html[data-admin-sidebar="collapsed"]:not([data-admin-sidebar-open="true"]) .sidebar-brand > div { display: none; }
            html[data-admin-sidebar="collapsed"]:not([data-admin-sidebar-open="true"]) .sidebar-nav { padding-right: 8px; padding-left: 8px; }
            html[data-admin-sidebar="collapsed"]:not([data-admin-sidebar-open="true"]) .sidebar-nav > .nav-link,
            html[data-admin-sidebar="collapsed"]:not([data-admin-sidebar-open="true"]) .sidebar-nav .nav-group-toggle {
                justify-content: center;
                gap: 0;
                padding-right: 6px;
                padding-left: 6px;
                font-size: 0;
            }
            html[data-admin-sidebar="collapsed"]:not([data-admin-sidebar-open="true"]) .sidebar-nav > .nav-link i,
            html[data-admin-sidebar="collapsed"]:not([data-admin-sidebar-open="true"]) .sidebar-nav .nav-group-toggle i:first-child {
                flex: 0 0 auto;
                font-size: .95rem;
            }
            html[data-admin-sidebar="collapsed"]:not([data-admin-sidebar-open="true"]) .sidebar-nav .nav-chevron,
            html[data-admin-sidebar="collapsed"]:not([data-admin-sidebar-open="true"]) .sidebar-nav .nav-section,
            html[data-admin-sidebar="collapsed"]:not([data-admin-sidebar-open="true"]) .sidebar-nav .nav-submenu { display: none; }
            html[data-admin-sidebar="collapsed"]:not([data-admin-sidebar-open="true"]) .sidebar-footer .btn-logout { justify-content: center; gap: 0; font-size: 0; }
            html[data-admin-sidebar="collapsed"]:not([data-admin-sidebar-open="true"]) .sidebar-footer .btn-logout i { font-size: .95rem; }
        }

        body { font-family: 'Segoe UI', Roboto, Arial, sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

        /* ── Sidebar ─────────────────────────────────────── */
        .sidebar {
            position: fixed; top: 0; left: 0; width: var(--sidebar-w);
            height: 100vh; background: var(--primary-dark);
            display: flex; flex-direction: column; z-index: 100;
            overflow-y: auto;
        }
        .sidebar-brand {
            padding: 22px 20px 18px;
            border-bottom: 1px solid rgba(255,255,255,.1);
            display: flex; align-items: center; gap: 12px;
        }
        .sidebar-brand img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .sidebar-brand span { font-size: 1rem; font-weight: 700; color: #fff; line-height: 1.2; }
        .sidebar-brand small { display: block; font-size: .72rem; color: rgba(255,255,255,.55); font-weight: 400; }

        .sidebar-nav { padding: 16px 12px; flex: 1; }
        .nav-section { font-size: .68rem; font-weight: 700; color: rgba(255,255,255,.4);
            letter-spacing: .08em; padding: 14px 8px 6px; text-transform: uppercase; }
        .nav-link {
            display: flex; align-items: center; gap: 12px;
            color: rgba(255,255,255,.75); text-decoration: none;
            padding: 11px 12px; border-radius: 8px; font-size: .9rem;
            transition: background .2s, color .2s; margin-bottom: 2px;
        }
        .nav-link i { width: 18px; text-align: center; font-size: .95rem; }
        .nav-link:hover { background: rgba(255,255,255,.1); color: #fff; }
        .nav-link.active { background: var(--accent); color: var(--primary-dark); font-weight: 600; }
        .nav-link.active i { color: var(--primary-dark); }
        .nav-group { display:flex; flex-direction:column; }
        .nav-group-toggle {
            width:100%;
            border:0;
            background:transparent;
            cursor:pointer;
            font:inherit;
            text-align:left;
        }
        .nav-group-toggle .nav-chevron { width:auto; margin-left:auto; transition:transform .18s ease; }
        .nav-group-toggle[aria-expanded="true"] .nav-chevron { transform:rotate(180deg); }
        .nav-group-toggle.is-active { background:rgba(255,255,255,.08); color:#fff; }
        .nav-submenu { display:none; flex-direction:column; gap:2px; padding:3px 0 5px 14px; }
        .nav-group.is-open .nav-submenu { display:flex; }
        .nav-submenu .nav-link { min-height:36px; padding:9px 10px; font-size:.84rem; }

        .sidebar-footer {
            padding: 14px 12px;
            border-top: 1px solid rgba(255,255,255,.1);
        }

        /* ── Top Bar ──────────────────────────────────────── */
        .topbar {
            position: fixed; top: 0; left: var(--sidebar-w); right: 0; height: 64px;
            background: var(--white); box-shadow: var(--shadow);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 28px; z-index: 90;
        }
        .topbar-left h1 { font-size: 1.25rem; font-weight: 700; color: var(--primary); }
        .topbar-left p { font-size: .8rem; color: var(--text-muted); margin-top: 1px; }
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .topbar-date-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 14px;
            background: #f3f6f5;
            color: var(--primary);
            font-size: .82rem;
            font-weight: 700;
        }
        .topbar-date-pill small { display: block; font-size: .72rem; font-weight: 400; color: #6b7280; }
        .notif { position:relative; }
        .notif .bell { position:relative; display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:10px; background:#f3f6f5; cursor:pointer; }
        .notif .count { position:absolute; top:-6px; right:-6px; background:#e53935; color:#fff; font-size:.72rem; padding:3px 6px; border-radius:999px; font-weight:700; }
        .notif-dropdown { position:absolute; right:0; top:48px; width:320px; background:#fff; box-shadow:0 10px 30px rgba(0,0,0,.08); border-radius:10px; display:none; z-index:120; }
        .notif-dropdown.open { display:block; }
        .notif-item { padding:12px; border-bottom:1px solid #f1f5f6; display:flex; gap:10px; align-items:flex-start; }
        .notif-item:last-child { border-bottom:none; }
        .notif-item .meta { font-size:.9rem; }
        .notif-empty { padding:12px; color:#6b7680; }
        .topbar-user { font-size: .85rem; color: var(--text-muted); }
        .btn-logout {
            background: var(--danger); color: #fff; border: none; padding: 8px 16px;
            border-radius: 6px; cursor: pointer; font-size: .82rem;
            text-decoration: none; transition: opacity .2s; display: flex; align-items: center; gap: 6px;
        }
        .btn-logout:hover { opacity: .88; }

        /* ── Main wrapper ─────────────────────────────────── */
        .page-wrapper { margin-left: var(--sidebar-w); padding-top: 64px; min-height: 100vh; }
        .page-content { padding: 28px; }

        /* ── Cards ────────────────────────────────────────── */
        .card {
            background: var(--white); border-radius: 12px;
            box-shadow: var(--shadow); overflow: hidden;
        }
        .card-header {
            padding: 18px 22px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-header h2 { font-size: 1rem; font-weight: 700; color: var(--primary); }
        .card-body { padding: 22px; }

        /* ── Stats grid ───────────────────────────────────── */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 18px; margin-bottom: 28px; }
        .stat-card {
            background: var(--white); border-radius: 12px; padding: 20px 22px;
            box-shadow: var(--shadow); display: flex; align-items: center; gap: 16px;
        }
        .stat-icon { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
        .stat-icon.blue   { background: #e3f2fd; color: #1565c0; }
        .stat-icon.green  { background: #e8f5e9; color: #2e7d32; }
        .stat-icon.orange { background: #fff8e1; color: #e65100; }
        .stat-icon.red    { background: #ffebee; color: #c62828; }
        .stat-icon.teal   { background: #e0f2f1; color: #00695c; }
        .stat-num { font-size: 1.9rem; font-weight: 800; line-height: 1; color: var(--primary); }
        .stat-label { font-size: .78rem; color: var(--text-muted); margin-top: 3px; }

        /* ── Table ────────────────────────────────────────── */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        thead { background: #f5f7fa; }
        th { padding: 12px 16px; text-align: left; font-weight: 600; color: var(--text-muted); font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; white-space: nowrap; }
        td { padding: 13px 16px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }

        /* ── Badges ───────────────────────────────────────── */
        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-size: .75rem; font-weight: 600; white-space: nowrap; }
        .badge-pending  { background: #fff8e1; color: #e65100; }
        .badge-approved { background: #e8f5e9; color: #2e7d32; }
        .badge-denied   { background: #ffebee; color: #c62828; }
        .badge-new      { background: #e3f2fd; color: #1565c0; }
        .badge-baby     { background: #f3e5f5; color: #6a1b9a; }

        /* ── Buttons ──────────────────────────────────────── */
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 15px;
            border-radius: 7px; font-size: .82rem; font-weight: 600; border: none;
            cursor: pointer; text-decoration: none; transition: opacity .2s, transform .1s; }
        .btn:active { transform: scale(.97); }
        .btn:hover { opacity: .88; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-success { background: var(--success); color: #fff; }
        .btn-danger  { background: var(--danger); color: #fff; }
        .btn-info    { background: var(--info); color: #fff; }
        .btn-sm { padding: 5px 10px; font-size: .77rem; }
        .btn-outline { background: transparent; border: 1.5px solid var(--primary); color: var(--primary); }
        .btn-outline:hover { background: var(--primary); color: #fff; opacity: 1; }

        /* ── Alerts ───────────────────────────────────────── */
        .alert { padding: 13px 16px; border-radius: 8px; margin-bottom: 20px; font-size: .875rem; display: flex; align-items: flex-start; gap: 10px; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #43a047; }
        .alert-danger  { background: #ffebee; color: #c62828; border-left: 4px solid #e53935; }
        .alert-info    { background: #e3f2fd; color: #1565c0; border-left: 4px solid #1e88e5; }

        .admin-confirm-backdrop { position:fixed; inset:0; z-index:1500; display:grid; place-items:center; padding:20px; background:rgba(17,24,39,.58); backdrop-filter:blur(3px); }
        .admin-confirm-backdrop[hidden] { display:none; }
        .admin-confirm-dialog { width:min(460px,100%); overflow:hidden; border:1px solid var(--border); border-radius:16px; background:var(--white); color:var(--text); box-shadow:0 24px 70px rgba(0,0,0,.3); animation:admin-confirm-in .18s ease-out; }
        .admin-confirm-top { display:flex; align-items:center; gap:13px; padding:22px 24px 13px; }
        .admin-confirm-icon { display:grid; width:44px; height:44px; flex:0 0 44px; place-items:center; border-radius:13px; background:rgba(21,101,192,.1); color:var(--info); font-size:1.1rem; }
        .admin-confirm-dialog[data-kind="save"] .admin-confirm-icon { background:rgba(46,125,50,.11); color:var(--success); }
        .admin-confirm-dialog h2 { font-size:1.05rem; font-weight:750; }
        .admin-confirm-copy { padding:0 24px 20px 81px; color:var(--text-muted); font-size:.88rem; line-height:1.55; }
        .admin-confirm-actions { display:flex; justify-content:flex-end; gap:9px; padding:15px 20px; border-top:1px solid var(--border); background:var(--bg); }
        .admin-confirm-actions button { min-height:40px; padding:9px 14px; border:1px solid var(--border); border-radius:8px; background:var(--white); color:var(--text); font:inherit; font-size:.82rem; font-weight:700; cursor:pointer; }
        .admin-confirm-actions button:focus-visible { outline:3px solid rgba(21,101,192,.35); outline-offset:2px; }
        .admin-confirm-actions [data-admin-confirm] { border-color:var(--primary); background:var(--primary); color:#fff; }
        .admin-confirm-dialog[data-kind="leave"] .admin-confirm-actions [data-admin-confirm] { border-color:var(--danger); background:var(--danger); }
        @keyframes admin-confirm-in { from { opacity:0; transform:translateY(8px) scale(.98); } to { opacity:1; transform:translateY(0) scale(1); } }
        @media(prefers-reduced-motion:reduce) { .admin-confirm-dialog { animation:none; } }
        .approval-limit-toast { position:fixed; right:24px; bottom:24px; z-index:1450; display:flex; width:min(460px,calc(100vw - 32px)); align-items:flex-start; gap:13px; padding:17px 18px; border:1px solid rgba(198,40,40,.2); border-left:5px solid var(--danger); border-radius:12px; background:var(--white); color:var(--text); box-shadow:0 16px 45px rgba(17,24,39,.22); animation:admin-confirm-in .2s ease-out; }
        .approval-limit-toast[hidden] { display:none; }
        .approval-limit-toast > i { margin-top:2px; color:var(--danger); font-size:1.1rem; }
        .approval-limit-toast-copy { flex:1; min-width:0; }
        .approval-limit-toast-copy strong { display:block; margin-bottom:4px; font-size:.92rem; }
        .approval-limit-toast-copy p { color:var(--text-muted); font-size:.82rem; line-height:1.5; }
        .approval-limit-toast button { display:grid; width:30px; height:30px; flex:0 0 30px; place-items:center; border:0; border-radius:7px; background:transparent; color:var(--text-muted); cursor:pointer; }
        .approval-limit-toast button:hover { background:var(--bg); color:var(--text); }

        /* ── Search bar ───────────────────────────────────── */
        .search-bar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .search-bar input, .search-bar select {
            padding: 9px 13px; border: 1.5px solid var(--border); border-radius: 7px;
            font-size: .875rem; outline: none; transition: border-color .2s;
        }
        .search-bar input:focus, .search-bar select:focus { border-color: var(--primary); }

        /* ── Pagination ───────────────────────────────────── */
        .pagination { display: flex; gap: 6px; margin-top: 20px; justify-content: flex-end; flex-wrap: wrap; }
        .pagination a, .pagination span {
            padding: 7px 12px; border-radius: 6px; font-size: .82rem;
            border: 1.5px solid var(--border); color: var(--text); text-decoration: none;
        }
        .pagination .active { background: var(--primary); color: #fff; border-color: var(--primary); }
        .pagination a:hover { background: #f0f2f5; }

        /* ── Responsive ───────────────────────────────────── */
        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); transition: transform .3s; }
            .sidebar.open { transform: translateX(0); }
            .topbar, .page-wrapper { left: 0; margin-left: 0; }
            .topbar { left: 0; }
            .hamburger { display: block !important; }
        }
        .hamburger { display: none; background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--primary); }

        /* ── Detail view ──────────────────────────────────── */
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
        .detail-item { padding: 12px 16px; border-bottom: 1px solid var(--border); }
        .detail-item:nth-child(odd) { border-right: 1px solid var(--border); }
        .detail-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; color: var(--text-muted); font-weight: 600; margin-bottom: 3px; }
        .detail-value { font-size: .92rem; color: var(--text); font-weight: 500; }

        @media (max-width: 600px) {
            .detail-grid { grid-template-columns: 1fr; }
            .detail-item:nth-child(odd) { border-right: none; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .topbar {
                height: 60px;
                padding: 0 12px;
                gap: 8px;
            }
            .topbar > div:first-child { min-width: 0; gap: 10px !important; }
            .topbar-left { min-width: 0; }
            .topbar-left h1 {
                overflow: hidden;
                font-size: .95rem;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .topbar-left p, .topbar-date-pill, .topbar-user { display: none; }
            .topbar-right { flex-shrink: 0; gap: 8px; }
            .page-wrapper { padding-top: 60px; }
            .page-content { padding: 16px 14px 24px; }
            body > footer {
                margin-left: 0 !important;
                padding: 14px 16px !important;
                flex-direction: column;
                align-items: flex-start;
            }
            body > footer > span:last-child {
                display: flex;
                flex-wrap: wrap;
                gap: 10px 14px;
            }
            body > footer a { overflow-wrap: anywhere; }
        }
    </style>
    @yield('styles')
</head>
<body>

<script>
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('[data-admin-nav-toggle]').forEach(function(toggle){
        toggle.addEventListener('click', function(){
            const group = toggle.closest('[data-admin-nav-group]');
            if (document.documentElement.dataset.adminSidebar === 'collapsed'
                && document.documentElement.dataset.adminSidebarOpen !== 'true') {
                document.documentElement.dataset.adminSidebarOpen = 'true';
                toggle.setAttribute('aria-expanded', 'true');
                group?.classList.add('is-open');
                return;
            }
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', String(!isExpanded));
            group?.classList.toggle('is-open', !isExpanded);
            if (document.documentElement.dataset.adminSidebar === 'collapsed'
                && !document.querySelector('[data-admin-nav-group].is-open')) {
                delete document.documentElement.dataset.adminSidebarOpen;
            }
        });
    });

    const bell = document.getElementById('notifBell');
    const dd = document.getElementById('notifDropdown');
    const markAll = document.getElementById('markAllRead');
    if (!bell) return;
    bell.addEventListener('click', ()=> dd.classList.toggle('open'));
    document.addEventListener('click', (e)=>{
        if (!bell.contains(e.target) && !dd.contains(e.target)) dd.classList.remove('open');
    });
    if (markAll) {
        markAll.addEventListener('click', function(e){
            e.preventDefault();
            fetch('{{ route('notifications.readAll') }}', { method: 'POST', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content') } })
            .then(()=>{
                document.getElementById('notifCount')?.remove();
                const emptyMessage = document.createElement('div');
                emptyMessage.className = 'notif-empty';
                emptyMessage.textContent = @json(__('No new notifications'));
                dd.replaceChildren(emptyMessage);
            })
        });
    }
    // mark single notification when clicked
    dd?.addEventListener('click', function(e){
        let item = e.target.closest('.notif-item');
        if (!item) return;
        const id = item.getAttribute('data-id');
        fetch('/notifications/'+id+'/read', { method:'POST', headers:{'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content') } })
        .then(()=> { item.remove(); const cnt = document.getElementById('notifCount'); if (cnt) { let v = parseInt(cnt.innerText)-1; if (v<=0) cnt.remove(); else cnt.innerText = v; } });
    });
});
</script>

<!-- ── Sidebar ───────────────────────────────────────────── -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="{{ asset('images/welcome_logo.jpg') }}" alt="PESO LAL-LO Logo">
        <div>
            <span>SPES Admin<small>PESO LAL-LO</small></span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="{{ route('admin.dashboard') }}"
           class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-chart-pie"></i> {{ __('Dashboard') }}
        </a>

        @php
            $applicationsNavActive = request()->routeIs([
                'admin.applications.*',
                'admin.additional-requirements.*',
                'admin.masterlist.*',
            ]);
        @endphp
        <div class="nav-group {{ $applicationsNavActive ? 'is-open' : '' }}" data-admin-nav-group>
            <button
                type="button"
                class="nav-link nav-group-toggle {{ $applicationsNavActive ? 'is-active' : '' }}"
                aria-expanded="{{ $applicationsNavActive ? 'true' : 'false' }}"
                aria-controls="admin-applications-submenu"
                data-admin-nav-toggle
            >
                <i class="fa-solid fa-file-lines"></i> {{ __('Applications') }}
                <i class="fa-solid fa-chevron-down nav-chevron" aria-hidden="true"></i>
            </button>
            <div class="nav-submenu" id="admin-applications-submenu">
                <a href="{{ route('admin.applications.index') }}"
                   class="nav-link {{ request()->routeIs('admin.applications.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-users"></i> {{ __('Applicants') }}
                </a>
                <a href="{{ route('admin.additional-requirements.index') }}"
                   class="nav-link {{ request()->routeIs('admin.additional-requirements.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-folder-plus"></i> {{ __('Requirements') }}
                </a>
                <a href="{{ route('admin.masterlist.index') }}"
                   class="nav-link {{ request()->routeIs('admin.masterlist.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-list-check"></i> {{ __('Final List of Batch') }} {{ $applicationBatchYear }}
                </a>
            </div>
        </div>
        <a href="{{ route('admin.users') }}"
           class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}">
            <i class="fa-solid fa-users"></i> {{ __('Users') }}
        </a>
        @php
            $statisticsAuditNavActive = request()->routeIs([
                'admin.statistics-report',
                'admin.applicant-audit.*',
            ]);
            $announcementsNavActive = request()->routeIs([
                'admin.settings',
                'admin.news.*',
                'admin.appointments.*',
            ]);
            $adminSettingsNavActive = request()->routeIs([
                'admin.preferences',
                'admin.manual',
                'admin.security',
                'admin.backup.*',
            ]);
        @endphp
        <div class="nav-group {{ $statisticsAuditNavActive ? 'is-open' : '' }}" data-admin-nav-group>
            <button
                type="button"
                class="nav-link nav-group-toggle {{ $statisticsAuditNavActive ? 'is-active' : '' }}"
                aria-expanded="{{ $statisticsAuditNavActive ? 'true' : 'false' }}"
                aria-controls="admin-statistics-audit-submenu"
                data-admin-nav-toggle
            >
                <i class="fa-solid fa-chart-line"></i> {{ __('Statistics & Audit') }}
                <i class="fa-solid fa-chevron-down nav-chevron" aria-hidden="true"></i>
            </button>
            <div class="nav-submenu" id="admin-statistics-audit-submenu">
                <a href="{{ route('admin.statistics-report') }}"
                   class="nav-link {{ request()->routeIs('admin.statistics-report') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-bar"></i> {{ __('Statistics Report') }}
                </a>
                @can('viewApplicantAudit')
                    <a href="{{ route('admin.applicant-audit.index') }}"
                       class="nav-link {{ request()->routeIs('admin.applicant-audit.index', 'admin.applicant-audit.show', 'admin.applicant-audit.event', 'admin.applicant-audit.status-history') ? 'active' : '' }}">
                        <i class="fa-solid fa-user-clock"></i> {{ __('Applicant Audit') }}
                    </a>
                @endcan
            </div>
        </div>
        <div class="nav-group {{ $announcementsNavActive ? 'is-open' : '' }}" data-admin-nav-group>
            <button
                type="button"
                class="nav-link nav-group-toggle {{ $announcementsNavActive ? 'is-active' : '' }}"
                aria-expanded="{{ $announcementsNavActive ? 'true' : 'false' }}"
                aria-controls="admin-announcements-submenu"
                data-admin-nav-toggle
            >
                <i class="fa-solid fa-bullhorn"></i> {{ __('Announcements') }}
                <i class="fa-solid fa-chevron-down nav-chevron" aria-hidden="true"></i>
            </button>
            <div class="nav-submenu" id="admin-announcements-submenu">
                <a href="{{ route('admin.appointments.index') }}"
                   class="nav-link {{ request()->routeIs('admin.appointments.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-check"></i> {{ __('Appointments') }}
                </a>
                <a href="{{ route('admin.news.index') }}"
                   class="nav-link {{ request()->routeIs('admin.news.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-newspaper"></i> {{ __('News') }}
                </a>
                <a href="{{ route('admin.settings') }}"
                   class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-days"></i> {{ __('Schedule') }}
                </a>
            </div>
        </div>
        <a href="{{ route('admin.preferences') }}"
           class="nav-link {{ $adminSettingsNavActive ? 'active' : '' }}">
            <i class="fa-solid fa-sliders"></i> {{ __('Settings') }}
        </a>
    </nav>

    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout" style="width:100%; justify-content:center;">
                <i class="fa-solid fa-right-from-bracket"></i> {{ __('Log Out') }}
            </button>
        </form>
    </div>
</aside>

<!-- ── Top Bar ────────────────────────────────────────────── -->
<header class="topbar">
    <div style="display:flex;align-items:center;gap:14px;">
        <button class="hamburger" onclick="document.getElementById('sidebar').classList.toggle('open')">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="topbar-left">
            <h1>@yield('page-title', __('Admin Panel'))</h1>
            <p>@yield('page-sub', __('SPES Management System'))</p>
        </div>
    </div>
        <div class="topbar-right">
        <div class="topbar-date-pill">
            <i class="fa-solid fa-calendar-days"></i>
            <div>
                <div>{{ now()->translatedFormat('F j, Y') }}</div>
                <small>{{ now()->translatedFormat('l') }}</small>
            </div>
        </div>
        <div class="notif">
            <div class="bell" id="notifBell" title="{{ __('Notifications') }}" aria-label="{{ __('Notifications') }}">
                <i class="fa-solid fa-bell" style="color:var(--primary);"></i>
                @php $unread = auth()->user()->unreadNotifications->count(); @endphp
                @if($unread > 0)
                    <div class="count" id="notifCount">{{ $unread }}</div>
                @endif
            </div>
            <div class="notif-dropdown" id="notifDropdown">
                @php $notes = auth()->user()->unreadNotifications->take(8); @endphp
                @if($notes->count())
                    @foreach($notes as $n)
                        <div class="notif-item" data-id="{{ $n->id }}">
                            <div style="width:36px;height:36px;border-radius:8px;background:#eef7ff;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-info" style="color:#1e6fb3"></i></div>
                            <div class="meta">
                                <div style="font-weight:700;">{{ __($n->data['message'] ?? 'Notification') }}</div>
                                <div style="font-size:.8rem;color:#6b7680;margin-top:4px;">{{ optional($n->created_at)->diffForHumans() }}</div>
                            </div>
                        </div>
                    @endforeach
                    <div style="padding:10px;text-align:center;border-top:1px solid #f1f5f6;"><a href="#" id="markAllRead" style="color:var(--primary);text-decoration:none;font-weight:700;">{{ __('Mark all as read') }}</a></div>
                @else
                    <div class="notif-empty">{{ __('No new notifications') }}</div>
                @endif
            </div>
        </div>
        <span class="topbar-user"><i class="fa-solid fa-circle-user" style="color:var(--primary)"></i> {{ Auth::user()->name }}</span>
    </div>
</header>

<!-- ── Page Content ───────────────────────────────────────── -->
<div class="page-wrapper">
    <div class="page-content">
        @if(session('success'))
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger"><i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}</div>
        @endif
        @if(session('info'))
            <div class="alert alert-info"><i class="fa-solid fa-circle-info"></i> {{ session('info') }}</div>
        @endif

        @yield('content')
    </div>
</div>

<!-- ── Footer ─────────────────────────────────────────────── -->
<footer style="margin-left:var(--sidebar-w);background:#fff;border-top:1px solid var(--border);padding:14px 28px;font-size:.78rem;color:var(--text-muted);display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;">
    <span>&copy; {{ date('Y') }} SPES Management System — PESO LAL-LO</span>
    <span>
        <a href="https://www.facebook.com" target="_blank" style="color:var(--primary);text-decoration:none;margin-right:14px;"><i class="fa-brands fa-facebook"></i> Facebook</a>
        <a href="mailto:lgulalloinformationoffice@gmail.com" style="color:var(--primary);text-decoration:none;"><i class="fa-solid fa-envelope"></i> lgulalloinformationoffice@gmail.com</a>
    </span>
</footer>

@yield('scripts')
@if(session('approval_limit_notice'))
    <aside class="approval-limit-toast" role="alert" aria-live="assertive" data-approval-limit-toast>
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        <div class="approval-limit-toast-copy">
            <strong>SPES approval limit reached</strong>
            <p>{{ session('approval_limit_notice') }}</p>
        </div>
        <button type="button" aria-label="Dismiss approval limit notice" data-dismiss-approval-limit><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </aside>
    <script>
        (() => {
            const toast = document.querySelector('[data-approval-limit-toast]');
            if (!toast) return;
            toast.querySelector('[data-dismiss-approval-limit]').addEventListener('click', () => toast.remove());
            window.setTimeout(() => toast.remove(), 12000);
        })();
    </script>
@endif
<div class="admin-confirm-backdrop" data-admin-confirm-backdrop hidden>
    <section class="admin-confirm-dialog" data-admin-confirm-dialog data-kind="save" role="dialog" aria-modal="true" aria-labelledby="admin-confirm-title" aria-describedby="admin-confirm-copy" tabindex="-1">
        <div class="admin-confirm-top">
            <span class="admin-confirm-icon" aria-hidden="true"><i class="fa-solid fa-circle-question" data-admin-confirm-icon></i></span>
            <h2 id="admin-confirm-title" data-admin-confirm-title>Confirm changes</h2>
        </div>
        <p class="admin-confirm-copy" id="admin-confirm-copy" data-admin-confirm-copy>Are you sure you want to save your changes?</p>
        <div class="admin-confirm-actions">
            <button type="button" data-admin-confirm-cancel>Keep editing</button>
            <button type="button" data-admin-confirm>Save changes</button>
        </div>
    </section>
</div>
<script>
    (() => {
        const backdrop = document.querySelector('[data-admin-confirm-backdrop]');
        const dialog = backdrop?.querySelector('[data-admin-confirm-dialog]');
        if (!backdrop || !dialog) return;

        const title = dialog.querySelector('[data-admin-confirm-title]');
        const copy = dialog.querySelector('[data-admin-confirm-copy]');
        const icon = dialog.querySelector('[data-admin-confirm-icon]');
        const confirmButton = dialog.querySelector('[data-admin-confirm]');
        const cancelButton = dialog.querySelector('[data-admin-confirm-cancel]');
        const formStates = new Map();
        const approvedForms = new WeakSet();
        const bypassCancelActions = new WeakSet();
        let activeAction = null;
        let returnFocus = null;
        let allowUnload = false;

        const fieldSignature = (form) => Array.from(form.elements)
            .filter((field) => {
                if (field.disabled || ['button', 'submit', 'reset', 'image'].includes(field.type)) return false;
                if (field.type === 'hidden' && ['_token', '_method'].includes(field.name)) return false;
                return true;
            })
            .map((field) => {
                if (field.type === 'checkbox' || field.type === 'radio') {
                    return [field.name, field.type, field.value, field.checked];
                }
                if (field.type === 'file') {
                    return [field.name, Array.from(field.files, (file) => [file.name, file.size, file.lastModified])];
                }
                if (field instanceof HTMLSelectElement && field.multiple) {
                    return [field.name, Array.from(field.selectedOptions, (option) => option.value)];
                }
                return [field.name, field.value];
            });

        const dirtyForms = () => Array.from(formStates.keys()).filter((form) => {
            const initial = formStates.get(form);
            return form.isConnected && JSON.stringify(fieldSignature(form)) !== initial;
        });

        document.querySelectorAll('.page-content form[method]').forEach((form) => {
            if (form.dataset.unsavedGuard === 'off') return;
            const method = (form.querySelector('input[name="_method"]')?.value || form.method).toLowerCase();
            if (method === 'get' || method === 'delete') return;
            if (form.getAttribute('onsubmit')?.includes('confirm(')) return;
            formStates.set(form, JSON.stringify(fieldSignature(form)));
        });

        const closeDialog = () => {
            backdrop.hidden = true;
            activeAction = null;
            returnFocus?.focus();
        };

        const openDialog = (kind, action, trigger) => {
            activeAction = action;
            returnFocus = trigger || document.activeElement;
            dialog.dataset.kind = kind;

            if (kind === 'save') {
                title.textContent = 'Confirm changes';
                copy.textContent = 'Are you sure you want to save your changes?';
                icon.className = 'fa-solid fa-circle-question';
                confirmButton.textContent = 'Save changes';
                cancelButton.textContent = 'Keep editing';
            } else {
                title.textContent = 'Leave without saving?';
                copy.textContent = 'You have unsaved changes. If you leave this page or cancel editing, those changes will be lost.';
                icon.className = 'fa-solid fa-triangle-exclamation';
                confirmButton.textContent = 'Discard changes';
                cancelButton.textContent = 'Stay and keep editing';
            }

            backdrop.hidden = false;
            cancelButton.focus();
        };

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!formStates.has(form)) return;
            if (approvedForms.has(form)) {
                approvedForms.delete(form);
                return;
            }

            if (JSON.stringify(fieldSignature(form)) === formStates.get(form)) return;
            event.preventDefault();
            openDialog('save', () => {
                if (!form.reportValidity()) return;
                approvedForms.add(form);
                allowUnload = true;
                if (event.submitter) {
                    form.requestSubmit(event.submitter);
                } else {
                    form.requestSubmit();
                }
                window.setTimeout(() => {
                    approvedForms.delete(form);
                    allowUnload = false;
                }, 0);
            }, event.submitter);
        }, true);

        document.addEventListener('click', (event) => {
            const cancelControl = event.target.closest('[data-unsaved-cancel]');
            if (cancelControl) {
                if (bypassCancelActions.has(cancelControl)) {
                    bypassCancelActions.delete(cancelControl);
                    return;
                }
                const form = document.getElementById(cancelControl.dataset.unsavedCancel);
                if (form && formStates.has(form)
                    && JSON.stringify(fieldSignature(form)) !== formStates.get(form)) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    openDialog('leave', () => {
                        bypassCancelActions.add(cancelControl);
                        cancelControl.click();
                    }, cancelControl);
                    return;
                }
            }

            if (event.defaultPrevented || event.button !== 0
                || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

            const link = event.target.closest('a[href]');
            if (!link || (link.target && link.target !== '_self') || link.hasAttribute('download')) return;
            if (!dirtyForms().length) return;

            const destination = new URL(link.href, window.location.href);
            if (destination.href === window.location.href
                || (destination.hash && destination.pathname === window.location.pathname
                    && destination.search === window.location.search)) return;

            event.preventDefault();
            openDialog('leave', () => {
                allowUnload = true;
                window.location.assign(destination.href);
            }, link);
        }, true);

        confirmButton.addEventListener('click', () => {
            const action = activeAction;
            closeDialog();
            action?.();
        });

        cancelButton.addEventListener('click', closeDialog);
        backdrop.addEventListener('click', (event) => {
            if (event.target === backdrop) closeDialog();
        });

        document.addEventListener('keydown', (event) => {
            if (backdrop.hidden) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                closeDialog();
            } else if (event.key === 'Tab') {
                event.preventDefault();
                (document.activeElement === cancelButton ? confirmButton : cancelButton).focus();
            }
        });

        window.addEventListener('beforeunload', (event) => {
            if (dirtyForms().length && !allowUnload) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
    })();
</script>
</body>
</html>
