<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Previous Notifications') }} — {{ __('SPES Portal') }}</title>
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
        html[data-theme="dark"] {
            color-scheme:dark;
            --primary:#ffaaaa;
            --primary-dark:#4a1212;
            --bg:#17191d;
            --white:#24272d;
            --text:#f2f4f7;
            --text-muted:#c0c6d0;
            --border:#434852;
            --shadow:0 2px 12px rgba(0,0,0,.3);
        }
        @media(prefers-color-scheme:dark) {
            html[data-theme="system"] {
                color-scheme:dark;
                --primary:#ffaaaa;
                --primary-dark:#4a1212;
                --bg:#17191d;
                --white:#24272d;
                --text:#f2f4f7;
                --text-muted:#c0c6d0;
                --border:#434852;
                --shadow:0 2px 12px rgba(0,0,0,.3);
            }
        }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bg); color:var(--text); font-family:Inter,"Plus Jakarta Sans",system-ui,sans-serif; }
        .topbar { position:fixed; z-index:90; top:0; right:0; left:var(--sidebar-w); display:flex; align-items:center; height:62px; padding:0 24px; border-bottom:1px solid var(--border); background:var(--white); }
        .topbar h1 { margin:0; color:var(--type-primary-color); font-size:var(--type-title); font-weight:700; }
        .topbar p { margin:3px 0 0; color:var(--type-secondary-color); font-size:var(--type-caption); }
        .hamburger { display:none; margin-right:12px; border:0; background:transparent; color:var(--primary); font-size:1.1rem; cursor:pointer; }
        .page-wrapper { min-height:100vh; margin-left:var(--sidebar-w); padding:76px 16px 18px; }
        .page-content { max-width:1400px; margin:0 auto; }
        .history-panel { overflow:hidden; border:1px solid var(--border); border-radius:10px; background:var(--white); box-shadow:var(--shadow); }
        .history-toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px; }
        .filter-list { display:flex; flex-wrap:wrap; gap:8px; }
        .filter-chip { display:inline-flex; min-height:34px; align-items:center; justify-content:center; padding:7px 14px; border:1px solid var(--border); border-radius:7px; background:transparent; color:var(--type-secondary-color); font-size:var(--type-caption); font-weight:600; text-decoration:none; }
        .filter-chip:hover { border-color:var(--primary); }
        .filter-chip.active { border-color:#801a31; background:#801a31; color:#fff; }
        .search-form { display:flex; width:min(100%,280px); min-height:36px; align-items:center; gap:8px; padding:0 10px; border:1px solid var(--border); border-radius:8px; background:var(--bg); color:var(--type-caption-color); }
        .search-form input { width:100%; min-width:0; border:0; outline:0; background:transparent; color:var(--type-primary-color); font:inherit; font-size:var(--type-caption); }
        .search-form input::placeholder { color:var(--type-caption-color); }
        .history-list { display:grid; gap:0; padding:0 10px 10px; }
        .history-item { display:grid; grid-template-columns:40px minmax(0,1fr) minmax(145px,auto) 12px; align-items:center; gap:10px; min-height:66px; padding:11px 12px; border:1px solid var(--border); border-radius:9px; background:var(--white); }
        .history-item + .history-item { margin-top:-1px; }
        .history-icon { display:grid; width:36px; height:36px; place-items:center; border-radius:8px; color:#fff; }
        .history-icon.events { background:#64388a; }
        .history-icon.announcements { background:#2469b4; }
        .history-icon.system { background:#596575; }
        .history-copy { min-width:0; }
        .history-copy h2 { margin:0 0 3px; color:var(--type-primary-color); font-size:var(--type-secondary); font-weight:600; }
        .history-copy p { margin:0; color:var(--type-secondary-color); font-size:var(--type-caption); line-height:1.45; }
        .history-location { margin-top:4px !important; }
        .history-location i { margin-right:4px; }
        .history-details { margin-top:4px !important; color:var(--type-caption-color) !important; }
        .history-read-status { margin-top:4px !important; color:var(--type-caption-color) !important; font-size:var(--type-caption); }
        .history-meta { display:grid; justify-items:start; gap:5px; }
        .history-date { color:var(--type-caption-color); font-size:10px; white-space:nowrap; }
        .category-badge { display:inline-flex; padding:3px 9px; border-radius:999px; color:#fff; font-size:10px; line-height:1.2; }
        .category-badge.events { background:#64388a; }
        .category-badge.announcements { background:#2469b4; }
        .category-badge.system { background:#596575; }
        .history-chevron { color:var(--type-caption-color); font-size:11px; }
        .history-empty { padding:46px 18px; color:var(--type-secondary-color); font-size:var(--type-secondary); text-align:center; }
        .history-empty i { display:block; margin-bottom:10px; color:var(--type-caption-color); font-size:1.8rem; }
        @media(max-width:768px) {
            .topbar { left:0; padding:0 14px; }
            .hamburger { display:block; }
            .page-wrapper { margin-left:0; padding:72px 12px 16px; }
            .history-toolbar { align-items:stretch; flex-direction:column; }
            .search-form { width:100%; }
        }
        @media(max-width:560px) {
            .topbar { height:56px; }
            .topbar h1 { font-size:var(--type-section); }
            .topbar p { display:none; }
            .page-wrapper { padding-top:66px; }
            .filter-list { gap:5px; }
            .filter-chip { min-height:32px; padding:6px 10px; }
            .history-list { padding:0 7px 7px; }
            .history-item { grid-template-columns:34px minmax(0,1fr) 10px; gap:8px; padding:10px 7px; }
            .history-icon { width:32px; height:32px; }
            .history-meta { grid-column:2; grid-row:2; display:flex; align-items:center; flex-wrap:wrap; }
            .history-chevron { grid-column:3; grid-row:1 / span 2; }
        }
    </style>
</head>
<body>
    <x-applicant-sidebar />
    <header class="topbar">
        <button class="hamburger" type="button" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false" aria-label="{{ __('Toggle applicant navigation') }}">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>
        <div>
            <h1>{{ __('Previous Notifications') }}</h1>
            <p>{{ __('View your past events, announcements, and updates from the admin.') }}</p>
        </div>
    </header>
    <main class="page-wrapper">
        <div class="page-content">
            <section class="history-panel" aria-label="{{ __('Previous notification history') }}">
                <div class="history-toolbar">
                    <nav class="filter-list" aria-label="{{ __('Filter notification history') }}">
                        @foreach(['all' => 'All', 'events' => 'Events', 'announcements' => 'Announcements', 'system' => 'System'] as $value => $label)
                            <a
                                class="filter-chip {{ $filter === $value ? 'active' : '' }}"
                                href="{{ route('applicant.notifications.previous', array_filter(['filter' => $value, 'search' => $search !== '' ? $search : null])) }}"
                                @if($filter === $value) aria-current="page" @endif
                            >{{ __($label) }}</a>
                        @endforeach
                    </nav>
                    <form class="search-form" method="GET" action="{{ route('applicant.notifications.previous') }}">
                        @if($filter !== 'all')
                            <input type="hidden" name="filter" value="{{ $filter }}">
                        @endif
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <input type="search" name="search" value="{{ $search }}" maxlength="120" placeholder="{{ __('Search notifications...') }}" aria-label="{{ __('Search notifications') }}">
                    </form>
                </div>
                @if($notifications->isNotEmpty())
                    <div class="history-list">
                        @foreach($notifications as $notification)
                            @php($categoryLabel = __((match ($notification['category']) { 'events' => 'Event', 'announcements' => 'Announcement', default => 'System' })))
                            <article class="history-item">
                                <span class="history-icon {{ $notification['category'] }}" aria-hidden="true">
                                    <i class="fa-solid {{ $notification['category'] === 'events' ? 'fa-calendar-day' : ($notification['category'] === 'announcements' ? 'fa-bullhorn' : 'fa-gear') }}"></i>
                                </span>
                                <div class="history-copy">
                                    <h2>{{ $notification['title'] }}</h2>
                                    <p>{{ $notification['message'] }}</p>
                                    @if($notification['location'])
                                        <p class="history-location"><i class="fa-solid fa-location-dot" aria-hidden="true"></i>{{ $notification['location'] }}</p>
                                    @endif
                                    @if($notification['details'] ?? null)
                                        <p class="history-details"><i class="fa-solid fa-laptop" aria-hidden="true"></i> {{ $notification['details'] }}</p>
                                    @endif
                                    @if($notification['read_status'] ?? null)
                                        <p class="history-read-status"><i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i> {{ $notification['read_status'] }}</p>
                                    @endif
                                </div>
                                <div class="history-meta">
                                    <time class="history-date" datetime="{{ $notification['date']->toIso8601String() }}">{{ $notification['date']->format('M j, Y · g:i A') }}</time>
                                    <span class="category-badge {{ $notification['category'] }}">{{ $categoryLabel }}</span>
                                </div>
                                <i class="fa-solid fa-chevron-right history-chevron" aria-hidden="true"></i>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="history-empty">
                        <i class="fa-regular fa-bell" aria-hidden="true"></i>
                        {{ __('No previous notifications match your search.') }}
                    </div>
                @endif
            </section>
        </div>
    </main>
    <x-portal-help-chat />
</body>
</html>
