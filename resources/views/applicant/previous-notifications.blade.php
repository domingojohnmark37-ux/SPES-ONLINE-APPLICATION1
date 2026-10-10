<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Previous Notifications') }} — {{ __('SPES Portal') }}</title>
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
        .history-toolbar { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; padding:12px; border-bottom:1px solid var(--border); background:var(--white); }
        .filter-list { display:flex; flex-wrap:wrap; gap:8px; }
        .filter-chip { display:inline-flex; min-height:34px; align-items:center; justify-content:center; padding:7px 14px; border:1px solid var(--border); border-radius:7px; background:transparent; color:var(--type-secondary-color); font-size:var(--type-caption); font-weight:600; text-decoration:none; }
        .filter-chip:hover { border-color:var(--primary); }
        .filter-chip.active { border-color:#801a31; background:#801a31; color:#fff; }
        .search-form { display:flex; width:min(100%,280px); min-height:36px; align-items:center; gap:8px; padding:0 10px; border:1px solid var(--border); border-radius:8px; background:var(--bg); color:var(--type-caption-color); }
        .search-form input { width:100%; min-width:0; border:0; outline:0; background:transparent; color:var(--type-primary-color); font:inherit; font-size:var(--type-caption); }
        .search-form input::placeholder { color:var(--type-caption-color); }
        .mark-all-form { margin:0; }
        .history-action { display:inline-flex; min-height:36px; align-items:center; justify-content:center; gap:7px; padding:7px 11px; border:1px solid var(--primary); border-radius:7px; background:transparent; color:var(--primary); font:inherit; font-size:var(--type-caption); font-weight:650; cursor:pointer; }
        .history-action:hover,.history-action:focus-visible { background:var(--primary); color:#fff; }
        .history-action:focus-visible,.filter-chip:focus-visible,.search-form:focus-within,.history-message summary:focus-visible { outline:3px solid var(--accent); outline-offset:2px; }
        .history-feedback { margin:12px 14px 0; padding:10px 12px; border:1px solid #b8dfc0; border-radius:8px; background:#edf8ef; color:#245b2e; font-size:var(--type-caption); }
        html[data-theme="dark"] .history-feedback { border-color:#47734e; background:#23372a; color:#c5f0cd; }
        @media(prefers-color-scheme:dark) { html[data-theme="system"] .history-feedback { border-color:#47734e; background:#23372a; color:#c5f0cd; } }
        .history-feedback.error { border-color:#e1a7a7; background:#fff1f1; color:#842020; }
        html[data-theme="dark"] .history-feedback.error { border-color:#8e4a4a; background:#3a2424; color:#ffd0d0; }
        @media(prefers-color-scheme:dark) { html[data-theme="system"] .history-feedback.error { border-color:#8e4a4a; background:#3a2424; color:#ffd0d0; } }
        .history-scroll { max-height:min(68vh, 760px); overflow-y:auto; overflow-x:hidden; overscroll-behavior:contain; scrollbar-gutter:stable; }
        .history-list { display:grid; gap:0; padding:0 10px 10px; }
        .history-group-title { position:sticky; top:0; z-index:1; margin:0 -10px; padding:10px 18px 7px; border-bottom:1px solid var(--border); background:var(--bg); color:var(--type-caption-color); font-size:var(--type-caption); font-weight:700; letter-spacing:.04em; text-transform:uppercase; }
        .history-item { display:grid; grid-template-columns:40px minmax(0,1fr) minmax(145px,auto) 12px; align-items:start; gap:10px; min-height:66px; padding:12px; border:1px solid var(--border); border-radius:9px; background:var(--white); }
        .history-item.is-unread { border-left:4px solid #a27600; background:color-mix(in srgb, var(--white) 94%, var(--accent)); }
        .history-item + .history-item { margin-top:-1px; }
        .history-icon { display:grid; width:36px; height:36px; place-items:center; border-radius:8px; color:#fff; }
        .history-icon.events { background:#64388a; }
        .history-icon.announcements { background:#2469b4; }
        .history-icon.system { background:#596575; }
        .history-copy { min-width:0; }
        .history-copy h2 { margin:0 0 3px; color:var(--type-primary-color); font-size:var(--type-secondary); font-weight:600; overflow-wrap:anywhere; }
        .history-copy p { margin:0; color:var(--type-secondary-color); font-size:var(--type-caption); line-height:1.5; overflow-wrap:anywhere; word-break:break-word; }
        .history-location,.history-details { display:flex; align-items:flex-start; gap:5px; margin-top:4px !important; }
        .history-location svg.icon,.history-details svg.icon { flex:0 0 auto; margin-top:3px; }
        .history-details { color:var(--type-caption-color) !important; }
        .history-read-status { margin-top:4px !important; color:var(--type-caption-color) !important; font-size:var(--type-caption); }
        .history-actions { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
        .history-action.compact { min-height:30px; padding:5px 9px; }
        .history-action.dismiss { border-color:var(--border); color:var(--type-secondary-color); }
        .history-action.dismiss:hover,.history-action.dismiss:focus-visible { border-color:#a52222; background:#a52222; color:#fff; }
        .history-message { margin-top:6px; }
        .history-message summary { display:inline-block; color:var(--primary); font-size:var(--type-caption); font-weight:650; cursor:pointer; }
        .history-message p { margin-top:5px; padding:8px 10px; border-left:3px solid var(--accent); border-radius:0 6px 6px 0; background:var(--bg); white-space:pre-wrap; }
        .history-meta { display:grid; justify-items:start; gap:5px; min-width:0; }
        .history-date { color:var(--type-caption-color); font-size:10px; white-space:normal; overflow-wrap:anywhere; }
        .category-badge { display:inline-flex; padding:3px 9px; border-radius:999px; color:#fff; font-size:10px; line-height:1.2; }
        .category-badge.events { background:#64388a; }
        .category-badge.announcements { background:#2469b4; }
        .category-badge.system { background:#596575; }
        .history-chevron { margin-top:5px; color:var(--type-caption-color); font-size:11px; }
        .history-empty { padding:46px 18px; color:var(--type-secondary-color); font-size:var(--type-secondary); text-align:center; }
        .history-empty svg.icon { display:block; margin-bottom:10px; color:var(--type-caption-color); font-size:1.8rem; }
        @media(max-width:768px) {
            .topbar { left:0; padding:0 14px; }
            .hamburger { display:block; }
            .page-wrapper { margin-left:0; padding:72px 12px 16px; }
            .history-toolbar { align-items:stretch; flex-direction:column; }
            .search-form { width:100%; }
            .mark-all-form,.mark-all-form button { width:100%; }
            .history-scroll { max-height:70vh; }
        }
        @media(max-width:560px) {
            .topbar { height:56px; }
            .topbar h1 { font-size:var(--type-section); }
            .topbar p { display:none; }
            .page-wrapper { padding-top:66px; }
            .filter-list { gap:5px; }
            .filter-chip { min-height:32px; padding:6px 10px; }
            .history-list { padding:0 7px 7px; }
            .history-item { grid-template-columns:34px minmax(0,1fr); gap:8px; padding:10px 7px; }
            .history-icon { width:32px; height:32px; }
            .history-meta { grid-column:2; display:flex; align-items:center; flex-wrap:wrap; }
            .history-chevron { display:none; }
            .history-scroll { max-height:72vh; }
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
            <h1>{{ __('Previous Notifications') }}</h1>
            <p>{{ __('Read notifications stay in Recent for 24 hours, then appear in the archive. Unread notifications remain in Recent.') }}</p>
        </div>
    </header>
    <main class="page-wrapper">
        <div class="page-content">
            <section class="history-panel" aria-label="{{ __('Previous notification history') }}">
                <div class="history-toolbar">
                    <nav class="filter-list" aria-label="{{ __('Filter notification history') }}">
                        @foreach(['all' => 'All', 'archived' => 'Archived', 'events' => 'Events', 'announcements' => 'Announcements', 'system' => 'System'] as $value => $label)
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
                        <x-icon class="fa-solid fa-magnifying-glass" aria-hidden="true" />
                        <input type="search" name="search" value="{{ $search }}" maxlength="120" placeholder="{{ __('Search notifications...') }}" aria-label="{{ __('Search notifications') }}">
                    </form>
                    @if($unreadCount > 0)
                        <form class="mark-all-form" method="POST" action="{{ route('notifications.readAll') }}">
                            @csrf
                            <input type="hidden" name="redirect_to" value="back">
                            <button class="history-action" type="submit" data-loading-label="{{ __('Marking all as read...') }}">
                                <x-icon class="fa-solid fa-envelope-open-text" aria-hidden="true" />
                                {{ __('Mark all as read') }} ({{ $unreadCount }})
                            </button>
                        </form>
                    @endif
                </div>
                @if(session('status'))
                    <p class="history-feedback" role="status">{{ session('status') }}</p>
                @endif
                <div class="history-scroll" role="region" aria-label="{{ __('Scrollable notification history') }}" tabindex="0">
                    @if($notifications->isNotEmpty())
                        <div class="history-list">
                            @php($groupedNotifications = $notifications->groupBy(fn ($item) => $item['date']->isToday() ? 'today' : ($item['date']->isYesterday() ? 'yesterday' : ($item['date']->isSameWeek(now()) ? 'this_week' : 'older'))))
                            @foreach($groupedNotifications as $dateGroup => $groupNotifications)
                                <h2 class="history-group-title">{{ __(['today' => 'Today', 'yesterday' => 'Yesterday', 'this_week' => 'This Week', 'older' => 'Older'][$dateGroup]) }}</h2>
                                @foreach($groupNotifications as $notification)
                                    @php($categoryLabel = __((match ($notification['category']) { 'events' => 'Event', 'announcements' => 'Announcement', default => 'System' })))
                                    @php($isUnread = ($notification['id'] ?? null) && empty($notification['read_at']))
                                    <article class="history-item {{ $isUnread ? 'is-unread' : '' }}">
                                <span class="history-icon {{ $notification['category'] }}" aria-hidden="true">
                                    <x-icon class="fa-solid {{ $notification['category'] === 'events' ? 'fa-calendar-day' : ($notification['category'] === 'announcements' ? 'fa-bullhorn' : 'fa-gear') }}" />
                                </span>
                                <div class="history-copy">
                                    <h2>{{ $notification['title'] }}</h2>
                                    <p>{{ \Illuminate\Support\Str::limit($notification['message'], 220) }}</p>
                                    @if(mb_strlen($notification['message']) > 220)
                                        <details class="history-message">
                                            <summary>{{ __('View full message') }}</summary>
                                            <p>{{ $notification['message'] }}</p>
                                        </details>
                                    @endif
                                    @if($notification['location'])
                                        <p class="history-location"><x-icon class="fa-solid fa-location-dot" aria-hidden="true" />{{ $notification['location'] }}</p>
                                    @endif
                                    @if($notification['details'] ?? null)
                                        <p class="history-details"><x-icon class="fa-solid fa-laptop" aria-hidden="true" /> {{ $notification['details'] }}</p>
                                    @endif
                                    @if($notification['read_status'] ?? null)
                                        <p class="history-read-status"><x-icon class="fa-solid fa-envelope-open-text" aria-hidden="true" /> {{ $notification['read_status'] }}</p>
                                    @endif
                                    @if($notification['archived_from_recent'] ?? false)
                                        <p class="history-read-status"><x-icon class="fa-solid fa-box-archive" aria-hidden="true" /> {{ __('Archived from Recent Notifications') }}</p>
                                    @endif
                                    @if($notification['id'] ?? null)
                                        <div class="history-actions">
                                            @if($isUnread)
                                                <form method="POST" action="{{ route('notifications.read', $notification['id']) }}">
                                                    @csrf
                                                    <input type="hidden" name="redirect_to" value="back">
                                                    <button class="history-action compact" type="submit" data-loading-label="{{ __('Marking as read...') }}">
                                                        <x-icon class="fa-solid fa-envelope-open" aria-hidden="true" />
                                                        {{ __('Mark as read') }}
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('notifications.unread', $notification['id']) }}">
                                                    @csrf
                                                    <button class="history-action compact" type="submit" data-loading-label="{{ __('Marking as unread...') }}">
                                                        <x-icon class="fa-solid fa-envelope" aria-hidden="true" />
                                                        {{ __('Mark as unread') }}
                                                    </button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('notifications.dismiss', $notification['id']) }}" onsubmit="return confirm('{{ __('Dismiss this notification?') }}')" data-notification-dismiss-form data-no-request-loading>
                                                @csrf
                                                @method('DELETE')
                                                <button class="history-action compact dismiss" type="submit" data-loading-label="{{ __('Dismissing...') }}" aria-label="{{ __('Dismiss :title', ['title' => $notification['title']]) }}">
                                                    <x-icon class="fa-solid fa-xmark" aria-hidden="true" />
                                                    {{ __('Dismiss') }}
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                                <div class="history-meta">
                                    <time class="history-date" datetime="{{ $notification['date']->toIso8601String() }}">{{ $notification['date']->format('M j, Y · g:i A') }}</time>
                                    <span class="category-badge {{ $notification['category'] }}">{{ $categoryLabel }}</span>
                                </div>
                                <x-icon class="fa-solid fa-chevron-right history-chevron" aria-hidden="true" />
                                    </article>
                                @endforeach
                            @endforeach
                        </div>
                    @else
                        <div class="history-empty">
                            <x-icon class="fa-regular fa-bell" aria-hidden="true" />
                            {{ $filter === 'archived' ? __('No notifications have been archived yet.') : __('No previous notifications match your search.') }}
                        </div>
                    @endif
                </div>
                    <p class="history-feedback" data-dismiss-feedback role="status" aria-live="polite" hidden></p>
            </section>
        </div>
    </main>
    <x-portal-help-chat />
    <script>
        document.querySelectorAll('[data-notification-dismiss-form]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (form.dataset.requestPending === 'true') return;

                const button = form.querySelector('button[type="submit"]');
                const item = form.closest('.history-item');
                const feedback = document.querySelector('[data-dismiss-feedback]');
                const parent = item.parentElement;
                const nextSibling = item.nextElementSibling;
                const originalHtml = button.innerHTML;
                form.dataset.requestPending = 'true';
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
                button.innerHTML = '<span class="request-loading-spinner" aria-hidden="true"></span><span>{{ __('Dismissing...') }}</span>';
                feedback.classList.remove('error');
                feedback.setAttribute('role', 'status');
                feedback.textContent = @json(__('Dismissing notification...'));
                feedback.hidden = false;
                item.remove();

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (response.redirected) {
                        throw new Error(@json(__('Your session has expired. Refresh the page and sign in again.')));
                    }
                    if (!response.ok) {
                        throw new Error(response.status === 401 || response.status === 419
                            ? @json(__('Your session has expired. Refresh the page and sign in again.'))
                            : @json(__('Could not dismiss this notification. Please try again.')));
                    }

                    const result = await response.json();
                    if (result.ok !== true) {
                        throw new Error(@json(__('Could not dismiss this notification. Please try again.')));
                    }

                    window.location.reload();
                } catch (error) {
                    console.error('Notification dismissal failed.', error);
                    if (nextSibling && nextSibling.parentElement === parent) {
                        parent.insertBefore(item, nextSibling);
                    } else {
                        parent.append(item);
                    }
                    button.disabled = false;
                    button.removeAttribute('aria-busy');
                    button.innerHTML = originalHtml;
                    form.dataset.requestPending = 'false';
                    feedback.classList.add('error');
                    feedback.setAttribute('role', 'alert');
                    feedback.textContent = error.message || @json(__('Could not dismiss this notification. Please try again.'));
                    feedback.hidden = false;
                }
            });
        });
    </script>
</body>
</html>
