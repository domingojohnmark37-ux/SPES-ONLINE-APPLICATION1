<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Recent Notifications') }} — {{ __('SPES Portal') }}</title>
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
        .topbar { position:fixed; z-index:90; top:0; right:0; left:var(--sidebar-w); display:flex; align-items:center; height:62px; padding:0 28px; border-bottom:1px solid var(--border); background:var(--white); }
        .topbar h1 { margin:0; color:var(--type-primary-color); font-size:var(--type-title); font-weight:700; }
        .topbar p { margin:3px 0 0; color:var(--type-secondary-color); font-size:var(--type-caption); }
        .hamburger { display:none; margin-right:12px; border:0; background:transparent; color:var(--primary); font-size:1.1rem; cursor:pointer; }
        .page-wrapper { min-height:100vh; margin-left:var(--sidebar-w); padding:84px 28px 24px; }
        .page-content { max-width:1400px; margin:0 auto; }
        .page-heading { margin:0 0 14px; color:var(--type-secondary-color); font-size:var(--type-caption); }
        .notifications-card { overflow:hidden; border:1px solid var(--border); border-radius:12px; background:var(--white); box-shadow:var(--shadow); }
        .notifications-card-heading { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:18px 20px; border-bottom:1px solid var(--border); }
        .notifications-card-heading h2 { margin:0; color:var(--type-primary-color); font-size:var(--type-section); font-weight:700; }
        .notifications-list { padding:0 20px; }
        .notification-item { display:grid; grid-template-columns:40px minmax(0,1fr) auto auto; align-items:center; gap:12px; padding:13px 6px; border-bottom:1px solid var(--border); }
        .notification-item:last-child { border-bottom:0; }
        .notification-icon { display:grid; width:38px; height:38px; place-items:center; border-radius:50%; background:#f6eeee; color:var(--primary); }
        .notification-copy { min-width:0; }
        .notification-copy h3 { margin:0 0 3px; color:var(--type-primary-color); font-size:var(--type-secondary); font-weight:700; }
        .notification-copy p { margin:0; color:var(--type-secondary-color); font-size:var(--type-caption); line-height:1.5; }
        .notification-state { margin-top:4px !important; color:var(--type-caption-color) !important; font-size:var(--type-caption) !important; }
        .notification-time { color:var(--type-caption-color); font-size:var(--type-caption); white-space:nowrap; }
        .notification-item form { margin:0; }
        .notification-read {
            padding:7px 10px;
            border:1px solid var(--primary);
            border-radius:7px;
            background:transparent;
            color:var(--primary);
            font:inherit;
            font-size:var(--type-caption);
            font-weight:650;
            cursor:pointer;
        }
        .notification-read:hover,.notification-read:focus-visible { background:var(--primary); color:#fff; }
        .notification-empty { padding:42px 20px; color:var(--type-secondary-color); font-size:var(--type-secondary); text-align:center; }
        .notification-empty svg.icon { display:block; margin-bottom:10px; color:var(--type-caption-color); font-size:1.8rem; }
        @media(max-width:768px) {
            .topbar { left:0; padding:0 14px; }
            .hamburger { display:block; }
            .page-wrapper { margin-left:0; padding:82px 14px 20px; }
        }
        @media(max-width:600px) {
            .topbar { height:56px; }
            .topbar h1 { font-size:var(--type-section); }
            .notifications-card-heading { padding:15px; }
            .notifications-list { padding:0 14px; }
            .notification-item { grid-template-columns:34px minmax(0,1fr); gap:9px; padding:12px 0; }
            .notification-icon { width:32px; height:32px; }
            .notification-time { grid-column:2; white-space:normal; }
            .notification-read { grid-column:2; justify-self:start; }
        }
    </style>
</head>
<body>
    <x-applicant-sidebar />
    <header class="topbar">
        <button class="hamburger" type="button" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false" aria-label="{{ __('Toggle applicant navigation') }}">
            <x-icon class="fa-solid fa-bars" aria-hidden="true" />
        </button>
        <div>
            <h1>{{ __('Recent Notifications') }}</h1>
            <p>{{ __('Unread notifications and items received in the last 24 hours.') }}</p>
        </div>
    </header>
    <main class="page-wrapper">
        <div class="page-content">
            <section class="notifications-card" aria-labelledby="recent-notifications-title">
                <div class="notifications-card-heading">
                    <h2 id="recent-notifications-title">{{ __('Recent Notifications') }}</h2>
                </div>
                @if($recentNotifications->isNotEmpty())
                    <div class="notifications-list">
                        @foreach($recentNotifications as $notification)
                            <article class="notification-item">
                                <span class="notification-icon" aria-hidden="true">
                                    <x-icon class="fa-solid {{ $notification['status'] === 'approved' ? 'fa-circle-check' : ($notification['status'] === 'denied' ? 'fa-circle-xmark' : 'fa-bell') }}" />
                                </span>
                                <div class="notification-copy">
                                    <h3>{{ $notification['title'] }}</h3>
                                    <p>{{ $notification['message'] }}</p>
                                    @if($notification['appointment_date'] ?? null)
                                        <p class="notification-state">
                                            <x-icon class="fa-solid fa-calendar-check" aria-hidden="true" />
                                            {{ __('Appointment date') }}: {{ $notification['appointment_date']->locale(app()->getLocale())->translatedFormat('l, F j, Y · g:i A') }}
                                            @if($notification['appointment_location'])
                                                · {{ $notification['appointment_location'] }}
                                            @endif
                                        </p>
                                    @endif
                                    @if($notification['id'])
                                        <p class="notification-state">
                                            <x-icon class="fa-solid {{ $notification['read_at'] ? 'fa-envelope-open' : 'fa-envelope' }}" aria-hidden="true" />
                                            {{ $notification['read_at'] ? __('Read :date', ['date' => $notification['read_at']->format('M j, Y · g:i A')]) : __('Unread') }}
                                        </p>
                                    @endif
                                </div>
                                <time class="notification-time" datetime="{{ $notification['date']->toIso8601String() }}">{{ $notification['date']->format('M j, Y · g:i A') }}</time>
                                @if($notification['id'] && !$notification['read_at'])
                                    <form method="POST" action="{{ route('notifications.read', $notification['id']) }}">
                                        @csrf
                                        <input type="hidden" name="redirect_to" value="previous">
                                        <button class="notification-read" type="submit">{{ __('Mark as read') }}</button>
                                    </form>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="notification-empty">
                        <x-icon class="fa-regular fa-bell" aria-hidden="true" />
                        {{ __('No recent notifications. New announcements and application status updates will appear here.') }}
                    </div>
                @endif
            </section>
        </div>
    </main>
    <x-portal-help-chat />
</body>
</html>
