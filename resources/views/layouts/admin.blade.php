<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — SPES Management System</title>
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
        }
    </style>
    @yield('styles')
</head>
<body>

<script>
document.addEventListener('DOMContentLoaded', function(){
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
            .then(()=>{ document.getElementById('notifCount')?.remove(); dd.innerHTML = '<div class="notif-empty">No new notifications</div>'; })
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
        <div class="nav-section">Main</div>
        <a href="{{ route('admin.dashboard') }}"
           class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-chart-pie"></i> Dashboard
        </a>

        <div class="nav-section">Management</div>
        <a href="{{ route('admin.applications.index') }}"
           class="nav-link {{ request()->routeIs('admin.applications.*') ? 'active' : '' }}">
            <i class="fa-solid fa-file-lines"></i> Applicants
        </a>
        <a href="{{ route('admin.users') }}"
           class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}">
            <i class="fa-solid fa-users"></i> Users
        </a>

        <div class="nav-section">Settings</div>
        <a href="{{ route('admin.settings') }}"
           class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
            <i class="fa-solid fa-calendar-days"></i> Schedule
        </a>
        <a href="{{ route('admin.applications.index') }}"
           class="nav-link {{ request()->routeIs('admin.applications.index') && request()->has('export') ? 'active' : '' }}">
            <i class="fa-solid fa-file-excel"></i> Download Excel
        </a>
        <a href="{{ route('admin.masterlist.index') }}"
           class="nav-link {{ request()->routeIs('admin.masterlist.*') ? 'active' : '' }}">
            <i class="fa-solid fa-list-check"></i> Approve Candidate
        </a>
        <a href="{{ route('admin.news.index') }}"
           class="nav-link {{ request()->routeIs('admin.news.*') ? 'active' : '' }}">
            <i class="fa-solid fa-newspaper"></i> News
        </a>
    </nav>

    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout" style="width:100%; justify-content:center;">
                <i class="fa-solid fa-right-from-bracket"></i> Log Out
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
            <h1>@yield('page-title', 'Admin Panel')</h1>
            <p>@yield('page-sub', 'SPES Management System')</p>
        </div>
    </div>
        <div class="topbar-right">
        <div class="topbar-date-pill">
            <i class="fa-solid fa-calendar-days"></i>
            <div>
                <div>{{ now()->format('F j, Y') }}</div>
                <small>{{ now()->format('l') }}</small>
            </div>
        </div>
        <div class="notif">
            <div class="bell" id="notifBell" title="Notifications">
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
                                <div style="font-weight:700;">{{ $n->data['message'] ?? 'Notification' }}</div>
                                <div style="font-size:.8rem;color:#6b7680;margin-top:4px;">{{ optional($n->created_at)->diffForHumans() }}</div>
                            </div>
                        </div>
                    @endforeach
                    <div style="padding:10px;text-align:center;border-top:1px solid #f1f5f6;"><a href="#" id="markAllRead" style="color:var(--primary);text-decoration:none;font-weight:700;">Mark all as read</a></div>
                @else
                    <div class="notif-empty">No new notifications</div>
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
</body>
</html>
