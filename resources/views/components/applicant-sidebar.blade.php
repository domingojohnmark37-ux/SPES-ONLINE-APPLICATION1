@php
    $applicant = auth()->user();
    $application = $applicant->applications()->latest()->first();
    $unreadCount = $applicant->unreadNotifications()->count();
    $showApprovalCapacityToast = $applicant->role === 'user'
        && session()->pull('approval_capacity_closed', false);
    $showStatusToasts = $applicant->role === 'user' && session()->pull('show_application_status_toasts', false);
    $unreadStatusNotifications = $showStatusToasts
        ? $applicant->unreadNotifications()
            ->get()
            ->filter(fn ($notification): bool => in_array($notification->data['status'] ?? null, ['approved', 'denied'], true))
            ->values()
        : collect();
    $applicantSetting = \App\Models\ApplicantSetting::where('user_id', $applicant->id)->first();
    $applicantAppearance = $applicantSetting->appearance ?? $applicantSetting->theme_preference ?? 'system';
    $applicantLanguage = $applicantSetting->language ?? 'en';
    $applicantSidebarBehavior = $applicantSetting->sidebar_behavior ?? 'auto';
    $applicantFontSize = $applicantSetting->font_size ?? 'medium';
@endphp

<x-applicant-text-styles />

@once
    <link rel="stylesheet" href="{{ asset('css/request-loading.css') }}?v={{ filemtime(public_path('css/request-loading.css')) }}">
@endonce

<style>
    html[data-font-size="small"] { font-size:14px; }
    html[data-font-size="medium"] { font-size:16px; }
    html[data-font-size="large"] { font-size:18px; }
    @media(min-width:769px) {
        html[data-sidebar-behavior="collapsed"] { --sidebar-w:76px; }
        html[data-sidebar-behavior="collapsed"] .peso-wrapper { margin-left:76px; }
        html[data-sidebar-behavior="collapsed"] .applicant-sidebar .sidebar-brand > div,
        html[data-sidebar-behavior="collapsed"] .applicant-sidebar .sidebar-user .meta {
            display:none;
        }
        html[data-sidebar-behavior="collapsed"] .applicant-sidebar .sidebar-brand > img {
            width:48px !important;
            min-width:48px;
            max-width:48px;
            height:48px !important;
            min-height:48px;
            max-height:48px;
            flex-basis:48px;
        }
        html[data-sidebar-behavior="collapsed"] .applicant-sidebar .sidebar-brand,
        html[data-sidebar-behavior="collapsed"] .applicant-sidebar .sidebar-user {
            justify-content:center;
            padding-right:8px;
            padding-left:8px;
        }
        html[data-sidebar-behavior="collapsed"] .applicant-sidebar .sidebar-brand {
            min-height:84px;
            padding-top:12px;
            padding-bottom:12px;
        }
        html[data-sidebar-behavior="collapsed"] .applicant-sidebar .sidebar-nav {
            padding-right:8px;
            padding-left:8px;
        }
        html[data-sidebar-behavior="collapsed"] .applicant-sidebar .nav-link,
        html[data-sidebar-behavior="collapsed"] .applicant-sidebar .sidebar-profile-link,
        html[data-sidebar-behavior="collapsed"] .applicant-sidebar .btn-logout {
            justify-content:center;
            gap:0;
            padding-right:6px;
            padding-left:6px;
            font-size:0;
        }
        html[data-sidebar-behavior="collapsed"] .applicant-sidebar .nav-link svg.icon,
        html[data-sidebar-behavior="collapsed"] .applicant-sidebar .sidebar-profile-link svg.icon,
        html[data-sidebar-behavior="collapsed"] .applicant-sidebar .btn-logout svg.icon {
            flex:0 0 auto;
            font-size:1.05rem;
        }
        html[data-sidebar-behavior="collapsed"] .applicant-sidebar .sidebar-user img,
        html[data-sidebar-behavior="collapsed"] .applicant-sidebar .sidebar-user .sidebar-user-fallback {
            width:42px;
            height:42px;
            flex-basis:42px;
        }
        html[data-sidebar-behavior="collapsed"] .applicant-sidebar .nav-chevron,
        html[data-sidebar-behavior="collapsed"] .applicant-sidebar .nav-count,
        html[data-sidebar-behavior="collapsed"] .applicant-sidebar .nav-submenu {
            display:none;
        }
    }
    html[data-theme="dark"] {
        color-scheme:dark;
        --primary:#ffaaaa;
        --primary-dark:#4a1212;
        --bg:#17191d;
        --white:#24272d;
        --surface:#24272d;
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
            --surface:#24272d;
            --text:#f2f4f7;
            --text-muted:#c0c6d0;
            --border:#434852;
            --shadow:0 2px 12px rgba(0,0,0,.3);
        }
    }
    html[data-theme="dark"] body,
    html[data-theme="system"] body { background:var(--bg); color:var(--text); }
    html[data-theme="dark"] body :is(.topbar,.card,.form-card,.form-card-body,.update-card,.empty,.profile-card,.portal-help-panel,.portal-help-thread,.notif-dropdown),
    html[data-theme="system"] body :is(.topbar,.card,.form-card,.form-card-body,.update-card,.empty,.profile-card,.portal-help-panel,.portal-help-thread,.notif-dropdown) {
        background-color:var(--white);
        color:var(--text);
        border-color:var(--border);
    }
    html[data-theme="dark"] body :is(input,select,textarea),
    html[data-theme="system"] body :is(input,select,textarea) {
        background-color:#30343b;
        color:var(--text);
        border-color:var(--border);
    }
    html[data-theme="dark"] body :is(.page-heading p,.page-title p,.detail-label,.update-date,.text-gray-500,.text-gray-600),
    html[data-theme="system"] body :is(.page-heading p,.page-title p,.detail-label,.update-date,.text-gray-500,.text-gray-600) { color:var(--text-muted); }
    .applicant-sidebar {
        position:fixed;
        inset:0 auto 0 0;
        z-index:100;
        display:flex;
        flex-direction:column;
        width:var(--sidebar-w, 260px);
        height:100vh;
        height:100dvh;
        overflow-y:auto;
        overflow-x:hidden;
        background:linear-gradient(180deg,#5E0A0F 0%,#5E0A0F 58%,#3c080c 100%);
        transition:transform .24s ease;
    }
    .applicant-sidebar::before {
        position:fixed;
        z-index:0;
        inset:auto auto 0 0;
        width:var(--sidebar-w,260px);
        height:50vh;
        background:
            linear-gradient(to bottom,#5E0A0F 0%,rgba(94,10,15,.85) 40%,rgba(94,10,15,.7) 100%),
            url('{{ asset('assets/images/lallo-bridge-sidebar.jpg') }}') center bottom / cover no-repeat;
        content:"";
        pointer-events:none;
    }
    .applicant-sidebar::after {
        position:fixed;
        z-index:0;
        bottom:0;
        left:0;
        width:var(--sidebar-w,260px);
        height:52px;
        background:linear-gradient(100deg,#C99700,#F5B800 72%,#FFD23F);
        clip-path:polygon(0 55%,18% 47%,42% 59%,67% 38%,100% 48%,100% 100%,0 100%);
        content:"";
        pointer-events:none;
    }
    .applicant-sidebar > * { position:relative; z-index:1; }
    .applicant-sidebar .sidebar-brand {
        display:flex;
        flex:0 0 auto;
        align-items:center;
        gap:12px;
        min-height:112px;
        padding:16px 14px;
        border-bottom:1px solid rgba(255,255,255,.18);
        color:#fff;
    }
    .applicant-sidebar .sidebar-close {
        display:none;
        width:36px;
        height:36px;
        flex:0 0 36px;
        align-items:center;
        justify-content:center;
        margin-left:auto;
        border:1px solid rgba(255,255,255,.38);
        border-radius:9px;
        background:rgba(255,255,255,.08);
        color:#fff;
        font-size:1rem;
        cursor:pointer;
    }
    .applicant-sidebar .sidebar-close:hover,
    .applicant-sidebar .sidebar-close:focus-visible { background:rgba(255,255,255,.18); }
    .applicant-sidebar .sidebar-backdrop { display:none; }
    .applicant-sidebar .sidebar-brand > img {
        display:block;
        flex:0 0 68px;
        width:68px !important;
        min-width:68px;
        max-width:68px;
        height:68px !important;
        min-height:68px;
        max-height:68px;
        border:2px solid rgba(255,255,255,.92);
        border-radius:50%;
        object-fit:cover;
        box-shadow:0 2px 10px rgba(0,0,0,.25);
    }
    .applicant-sidebar .sidebar-brand > div { min-width:0; }
    .applicant-sidebar .sidebar-brand-copy { flex:1; }
    .applicant-sidebar .sidebar-brand span {
        display:block;
        color:#fff;
        font-size:1.5rem;
        font-weight:700;
        line-height:1.2;
    }
    .applicant-sidebar .sidebar-brand .sidebar-brand-subtitle {
        margin-top:2px;
        font-size:1rem;
        font-weight:700;
        line-height:1.2;
    }
    .applicant-sidebar .sidebar-brand small {
        display:block;
        margin-top:3px;
        color:rgba(255,255,255,.86);
        font-size:.65rem;
        font-weight:400;
        line-height:1.3;
    }
    .applicant-sidebar .sidebar-nav {
        display:flex;
        flex-direction:column;
        flex:1;
        gap:5px;
        padding:14px 10px;
    }
    .applicant-sidebar .nav-link {
        display:flex;
        align-items:center;
        gap:12px;
        width:100%;
        min-height:48px;
        margin:0;
        padding:12px 12px;
        border-radius:11px;
        color:rgba(255,255,255,.96);
        font-size:.94rem;
        font-weight:500;
        text-align:left;
        text-decoration:none;
        transition:background .18s, color .18s, transform .08s, box-shadow .18s;
    }
    .applicant-sidebar .nav-link svg.icon { width:22px; flex:0 0 22px; color:#fff4d6; font-size:1.12rem; text-align:center; }
    .applicant-sidebar .nav-link:hover { background:rgba(255,255,255,.08); color:#fff; }
    .applicant-sidebar .nav-link.active {
        background:linear-gradient(110deg,#F5B800,#FFD23F);
        color:#4A0B10;
        font-weight:700;
        box-shadow:0 4px 12px rgba(0,0,0,.2);
    }
    .applicant-sidebar .nav-link.active svg.icon { color:#4A0B10; }
    .applicant-sidebar button.nav-link { border:0; background:transparent; cursor:pointer; font-family:inherit; }
    .applicant-sidebar .nav-group { display:flex; flex-direction:column; }
    .applicant-sidebar .nav-group-toggle { justify-content:flex-start; }
    .applicant-sidebar .nav-group-toggle .nav-chevron { width:auto; flex:0 0 auto; margin-left:auto; color:#fff; font-size:.72rem; transition:transform .18s; }
    .applicant-sidebar .nav-group-toggle[aria-expanded="true"] .nav-chevron { transform:rotate(180deg); }
    .applicant-sidebar .nav-group-toggle.has-count .nav-count { margin-left:auto; }
    .applicant-sidebar .nav-group-toggle.has-count .nav-chevron { margin-left:0; }
    .applicant-sidebar .nav-submenu { display:none; flex-direction:column; gap:3px; padding:4px 0 4px 14px; }
    .applicant-sidebar .nav-group.is-open .nav-submenu { display:flex; }
    .applicant-sidebar .nav-submenu .nav-link { min-height:40px; padding:9px 10px; font-size:.86rem; }
    .applicant-sidebar .nav-count {
        min-width:18px;
        margin-left:auto;
        padding:2px 5px;
        border-radius:999px;
        background:#E53935;
        color:#fff;
        font-size:.68rem;
        font-weight:800;
        text-align:center;
    }
    .applicant-sidebar .sidebar-user {
        display:flex;
        align-items:center;
        gap:11px;
        padding:13px 14px;
        border-top:1px solid rgba(255,255,255,.2);
        color:#fff;
    }
    .applicant-sidebar .sidebar-user img {
        width:46px;
        height:46px;
        flex:0 0 auto;
        border:2px solid #F5B800;
        border-radius:50%;
        object-fit:cover;
        background:#3d090d;
    }
    .applicant-sidebar .sidebar-user .sidebar-user-fallback {
        display:grid;
        width:46px;
        height:46px;
        flex:0 0 46px;
        place-items:center;
        border:2px solid #F5B800;
        border-radius:50%;
        background:#3d090d;
        color:#F5B800;
        font-size:1.1rem;
    }
    .applicant-sidebar .sidebar-user .meta { min-width:0; color:#fff; }
    .applicant-sidebar .sidebar-user .meta b {
        display:block;
        overflow:hidden;
        font-size:.9rem;
        font-weight:700;
        text-overflow:ellipsis;
        white-space:nowrap;
    }
    .applicant-sidebar .sidebar-user .meta small { color:rgba(255,255,255,.86); font-size:.75rem; }
    .applicant-sidebar .sidebar-profile-links {
        display:flex;
        flex-direction:column;
        gap:3px;
        padding:9px 12px 5px;
        border-top:1px solid rgba(255,255,255,.18);
    }
    .applicant-sidebar .sidebar-profile-link {
        display:flex;
        align-items:center;
        gap:10px;
        padding:9px 10px;
        border-radius:8px;
        color:rgba(255,255,255,.9);
        text-decoration:none;
        font-size:.88rem;
        font-weight:500;
        transition:background .18s, color .18s;
    }
    .applicant-sidebar .sidebar-profile-link svg.icon,
    .applicant-sidebar .btn-logout svg.icon { width:20px; color:#F5B800; font-size:1rem; text-align:center; }
    .applicant-sidebar .sidebar-profile-link:hover,
    .applicant-sidebar .sidebar-profile-link.active {
        background:rgba(255,255,255,.08);
        color:#fff;
    }
    .applicant-sidebar .sidebar-footer {
        flex:0 0 auto;
        padding:7px 12px 18px;
        border-top:1px solid rgba(255,255,255,.18);
    }
    .applicant-sidebar .sidebar-footer form { margin:0; }
    .applicant-sidebar .btn-logout {
        display:flex;
        align-items:center;
        justify-content:flex-start;
        gap:10px;
        width:100%;
        min-height:42px;
        padding:8px 10px;
        border:0;
        border-radius:8px;
        background:transparent;
        color:#fff;
        font:inherit;
        font-size:.88rem;
        font-weight:500;
        cursor:pointer;
    }
    .applicant-sidebar .btn-logout:hover { background:rgba(255,255,255,.08); }
    .application-status-toasts {
        position:fixed;
        top:50%;
        left:50%;
        z-index:1200;
        display:grid;
        width:min(620px, calc(100vw - 40px));
        gap:10px;
        transform:translate(-50%, -50%);
        pointer-events:none;
    }
    .application-status-toast {
        overflow:hidden;
        border:1px solid var(--border);
        border-left:5px solid #2e7d32;
        border-radius:12px;
        background:var(--white);
        color:var(--text);
        box-shadow:0 12px 35px rgba(0,0,0,.24);
        pointer-events:auto;
    }
    .application-status-toast[data-status="denied"] { border-left-color:#c62828; }
    .application-status-toast[data-status="appointment"] { border-left-color:#c99400; }
    .application-status-toast[data-status="capacity"] { border-left-color:#c62828; }
    .application-status-toast.is-dismissing { opacity:0; transform:translateY(8px); transition:opacity .2s ease, transform .2s ease; }
    .application-status-toast-content { display:flex; align-items:flex-start; gap:18px; padding:26px; }
    .application-status-toast-icon {
        display:grid;
        width:54px;
        height:54px;
        flex:0 0 54px;
        place-items:center;
        border-radius:50%;
        background:#e8f5e9;
        color:#2e7d32;
        font-size:1.35rem;
    }
    .application-status-toast[data-status="denied"] .application-status-toast-icon { background:#ffebee; color:#c62828; }
    .application-status-toast[data-status="appointment"] .application-status-toast-icon { background:#fff4bf; color:#795900; }
    .application-status-toast[data-status="capacity"] .application-status-toast-icon { background:#ffebee; color:#c62828; }
    .application-status-toast-copy { min-width:0; flex:1; }
    .application-status-toast-copy h2 { margin:0 0 8px; color:var(--text); font-size:1.35rem; font-weight:750; }
    .application-status-toast-copy p { margin:0; color:var(--text-muted); font-size:1.05rem; line-height:1.55; overflow-wrap:anywhere; }
    .application-status-toast-copy time { display:block; margin-top:12px; color:var(--text-muted); font-size:.88rem; }
    .application-status-toast form { margin:16px 0 0; }
    .application-status-toast button {
        min-height:42px;
        padding:9px 16px;
        border:0;
        border-radius:7px;
        background:var(--primary);
        color:#fff;
        font:inherit;
        font-size:.95rem;
        font-weight:700;
        cursor:pointer;
    }
    .application-status-toast button:disabled { opacity:.65; cursor:wait; }
    .application-status-toast-error { margin-top:6px !important; color:#b42318 !important; font-size:.76rem !important; }
    .application-status-toast-progress { height:3px; background:var(--primary); transform-origin:left; animation:status-toast-countdown 10s linear forwards; }
    @keyframes status-toast-enter-centered { from { opacity:0; transform:translateY(16px) scale(.97); } to { opacity:1; transform:translateY(0) scale(1); } }
    .application-status-toast { animation:status-toast-enter-centered .24s ease-out both; }
    @keyframes status-toast-countdown { to { transform:scaleX(0); } }
    @media(max-width:600px) {
        .application-status-toasts { width:calc(100vw - 28px); }
        .application-status-toast-content { gap:12px; padding:18px; }
        .application-status-toast-icon { width:44px; height:44px; flex-basis:44px; font-size:1.1rem; }
        .application-status-toast-copy h2 { font-size:1.12rem; }
        .application-status-toast-copy p { font-size:.92rem; }
    }
    @media(prefers-reduced-motion:reduce) {
        .application-status-toast,.application-status-toast-progress { animation:none; }
        .application-status-toast.is-dismissing { transition:none; }
    }
    @media(max-width:768px) {
        .applicant-sidebar:not(.open) { transform:translateX(-100%); }
        .applicant-sidebar.open { transform:translateX(0); }
        .applicant-sidebar .sidebar-close { display:inline-flex; }
        .applicant-sidebar .sidebar-brand { gap:9px; min-height:96px; padding:12px 10px; }
        .applicant-sidebar .sidebar-brand > img {
            width:56px !important;
            min-width:56px;
            max-width:56px;
            height:56px !important;
            min-height:56px;
            max-height:56px;
            flex-basis:56px;
        }
        .applicant-sidebar .sidebar-brand span { font-size:1.28rem; }
        .applicant-sidebar .sidebar-brand .sidebar-brand-subtitle { font-size:.86rem; }
        .applicant-sidebar .sidebar-brand small { font-size:.58rem; }
        .applicant-sidebar .sidebar-close { width:32px; height:32px; flex-basis:32px; }
        .sidebar-backdrop:not([hidden]) {
            position:fixed;
            inset:0;
            z-index:99;
            display:block;
            border:0;
            background:rgba(17,24,39,.5);
            cursor:pointer;
        }
    }
</style>

<aside class="sidebar applicant-sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="{{ asset('images/lal-lo_logo.png') }}" alt="{{ __('Lal-lo municipal seal') }}">
        <div class="sidebar-brand-copy">
            <span>SPES</span>
            <span class="sidebar-brand-subtitle">PESO LAL-LO</span>
            <small>{{ __('Special Program for Employment of Students') }}</small>
        </div>
        <button class="sidebar-close" type="button" data-sidebar-close aria-label="Close applicant navigation">
            <x-icon class="fa-solid fa-chevron-left" aria-hidden="true" />
        </button>
    </div>
    <nav class="sidebar-nav" aria-label="{{ __('Applicant portal') }}">
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <x-icon class="fa-solid fa-house" /> {{ __('Dashboard') }}
        </a>
        <a href="{{ route('applicant.manual') }}" class="nav-link {{ request()->routeIs('applicant.manual') ? 'active' : '' }}">
            <x-icon class="fa-solid fa-book-open" aria-hidden="true" /> {{ __('User Guide') }}
        </a>
        @php
            $applicationSectionActive = request()->routeIs([
                'applications.myApplication',
                'applications.create',
                'applications.store',
                'applications.edit',
                'applicant.requirements',
            ]);
            $supportSectionActive = request()->routeIs('contact-peso.*');
            $appointmentsSectionActive = request()->routeIs('applicant.appointments.*');
            $recentNotificationsActive = request()->routeIs('applicant.notifications.recent');
            $previousNotificationsActive = request()->routeIs('applicant.notifications.previous');
            $notificationsSectionActive = $appointmentsSectionActive || $recentNotificationsActive || $previousNotificationsActive;
        @endphp
        <div class="nav-group {{ $applicationSectionActive ? 'is-open' : '' }}" data-nav-group>
            <button
                type="button"
                class="nav-link nav-group-toggle {{ $applicationSectionActive ? 'active' : '' }}"
                aria-expanded="{{ $applicationSectionActive ? 'true' : 'false' }}"
                aria-controls="applicant-application-submenu"
                data-nav-group-toggle
            >
                <x-icon class="fa-solid fa-file-lines" /> {{ __('My Application') }}
                <x-icon class="fa-solid fa-chevron-down nav-chevron" aria-hidden="true" />
            </button>
            <div class="nav-submenu" id="applicant-application-submenu">
                <a href="{{ route('applications.myApplication') }}" class="nav-link {{ request()->routeIs('applications.myApplication') ? 'active' : '' }}">
                    <x-icon class="fa-solid fa-file-lines" /> {{ __('Application Status') }}
                </a>
                @if(!$application || $application->status === 'denied')
                    <a href="{{ $application ? route('applications.edit') : route('applications.create') }}" class="nav-link {{ request()->routeIs(['applications.create', 'applications.store', 'applications.edit']) ? 'active' : '' }}">
                        <x-icon class="fa-solid {{ $application ? 'fa-rotate-right' : 'fa-file-circle-plus' }}" />
                        {{ __($application ? 'Reapply' : 'Apply Now') }}
                    </a>
                @endif
                <a href="{{ route('applicant.requirements') }}" class="nav-link {{ request()->routeIs('applicant.requirements') ? 'active' : '' }}">
                    <x-icon class="fa-solid fa-folder-open" /> {{ __('Additional Requirements') }}
                </a>
            </div>
        </div>
        <div class="nav-group {{ $notificationsSectionActive ? 'is-open' : '' }}" data-nav-group>
            <button
                type="button"
                class="nav-link nav-group-toggle {{ $notificationsSectionActive ? 'active' : '' }} {{ $unreadCount > 0 ? 'has-count' : '' }}"
                aria-expanded="{{ $notificationsSectionActive ? 'true' : 'false' }}"
                aria-controls="applicant-notifications-submenu"
                data-nav-group-toggle
            >
                <x-icon class="fa-solid fa-bell" /> {{ __('Notifications') }}
                @if($unreadCount > 0)
                    <span class="nav-count" aria-label="{{ $unreadCount }} unread notifications">{{ $unreadCount }}</span>
                @endif
                <x-icon class="fa-solid fa-chevron-down nav-chevron" aria-hidden="true" />
            </button>
            <div class="nav-submenu" id="applicant-notifications-submenu">
                <a href="{{ route('applicant.appointments.index') }}" class="nav-link {{ $appointmentsSectionActive ? 'active' : '' }}">
                    <x-icon class="fa-solid fa-calendar-check" /> {{ __('Appointments') }}
                </a>
                <a href="{{ route('applicant.notifications.recent') }}" class="nav-link {{ $recentNotificationsActive ? 'active' : '' }}">
                    <x-icon class="fa-solid fa-bullhorn" /> {{ __('Recent Notifications') }}
                </a>
                <a href="{{ route('applicant.notifications.previous') }}" class="nav-link {{ $previousNotificationsActive ? 'active' : '' }}">
                    <x-icon class="fa-solid fa-bell" /> {{ __('Previous Notifications') }}
                </a>
            </div>
        </div>
        <div class="nav-group {{ $supportSectionActive ? 'is-open' : '' }}" data-nav-group>
            <button
                type="button"
                class="nav-link nav-group-toggle {{ $supportSectionActive ? 'active' : '' }}"
                aria-expanded="{{ $supportSectionActive ? 'true' : 'false' }}"
                aria-controls="applicant-support-submenu"
                data-nav-group-toggle
            >
                <x-icon class="fa-solid fa-circle-question" /> {{ __('Need Help & Support') }}
                <x-icon class="fa-solid fa-chevron-down nav-chevron" aria-hidden="true" />
            </button>
            <div class="nav-submenu" id="applicant-support-submenu">
                <button type="button" class="nav-link" data-open-portal-help>
                    <x-icon class="fa-solid fa-circle-question" /> {{ __('FAQs') }}
                </button>
                <a href="{{ route('contact-peso.index') }}" class="nav-link {{ $supportSectionActive ? 'active' : '' }}">
                    <x-icon class="fa-solid fa-envelope" /> {{ __('Contact Us') }}
                </a>
            </div>
        </div>
    </nav>
    <div class="sidebar-user">
        @if($applicant->profile_photo_url)
            <img src="{{ $applicant->profile_photo_url }}" alt="{{ $applicant->name }}">
        @else
            <span class="sidebar-user-fallback" aria-hidden="true"><x-icon class="fa-solid fa-user" /></span>
        @endif
        <div class="meta">
            <b>{{ $applicant->name }}</b>
            <small>{{ __('Student Applicant') }}</small>
        </div>
    </div>
    <div class="sidebar-profile-links">
        <a href="{{ route('profile.edit') }}" class="sidebar-profile-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}">
            <x-icon class="fa-solid fa-user" /> {{ __('Profile') }}
        </a>
        <a href="{{ route('settings.index') }}" class="sidebar-profile-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
            <x-icon class="fa-solid fa-gear" /> {{ __('Settings') }}
        </a>
    </div>
    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout"><x-icon class="fa-solid fa-right-from-bracket" /> {{ __('Log Out') }}</button>
        </form>
    </div>
</aside>
<button class="sidebar-backdrop" type="button" data-sidebar-backdrop aria-label="{{ __('Close applicant navigation') }}" hidden></button>
@once
    <script src="{{ asset('js/request-loading.js') }}?v={{ filemtime(public_path('js/request-loading.js')) }}"></script>
@endonce
@if($showApprovalCapacityToast)
    <div class="application-status-toasts" aria-live="assertive" aria-label="{{ __('Application period closed') }}">
        <article class="application-status-toast" data-application-status-toast data-status="capacity">
            <div class="application-status-toast-content">
                <span class="application-status-toast-icon" aria-hidden="true"><x-icon class="fa-solid fa-lock" /></span>
                <div class="application-status-toast-copy">
                    <h2>{{ __('SPES applications are closed') }}</h2>
                    <p>{{ __('The program has reached its approved-applicant limit. New applications and submissions are closed for this season. Please try again next SPES season.') }}</p>
                </div>
            </div>
            <div class="application-status-toast-progress" aria-hidden="true"></div>
        </article>
    </div>
@endif
@if($unreadStatusNotifications->isNotEmpty())
    <div class="application-status-toasts" aria-live="polite" aria-label="Application status notifications">
        @foreach($unreadStatusNotifications as $statusNotification)
            <article class="application-status-toast" data-application-status-toast data-notification-id="{{ $statusNotification->id }}" data-status="{{ $statusNotification->data['status'] }}">
                <div class="application-status-toast-content">
                    <span class="application-status-toast-icon" aria-hidden="true">
                        <x-icon class="fa-solid {{ $statusNotification->data['status'] === 'approved' ? 'fa-circle-check' : 'fa-circle-xmark' }}" />
                    </span>
                    <div class="application-status-toast-copy">
                        <h2>{{ $statusNotification->data['title'] ?? __('Application status updated') }}</h2>
                        <p>{{ $statusNotification->data['message'] ?? __('Your SPES application status has been updated.') }}</p>
                        <time datetime="{{ $statusNotification->created_at->toIso8601String() }}">{{ $statusNotification->created_at->format('M j, Y · g:i A') }}</time>
                        <form method="POST" action="{{ route('notifications.read', $statusNotification->id) }}" data-mark-status-read data-no-request-loading>
                            @csrf
                            <input type="hidden" name="redirect_to" value="back">
                            <button type="submit">{{ __('Mark as read') }}</button>
                            <p class="application-status-toast-error" data-toast-error role="alert" hidden></p>
                        </form>
                    </div>
                </div>
                <div class="application-status-toast-progress" aria-hidden="true"></div>
            </article>
        @endforeach
    </div>
@endif
<script>
    document.documentElement.dataset.theme = @json($applicantAppearance);
    document.documentElement.lang = @json($applicantLanguage === 'fil' ? 'fil-PH' : 'en');
    document.documentElement.dataset.sidebarBehavior = @json($applicantSidebarBehavior);
    document.documentElement.dataset.fontSize = @json($applicantFontSize);

    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.querySelector('[data-sidebar-toggle]');
        const backdrop = document.querySelector('[data-sidebar-backdrop]');
        document.querySelectorAll('[data-application-status-toast]').forEach(function (toast) {
            function dismissToast() {
                toast.classList.add('is-dismissing');
                window.setTimeout(function () {
                    toast.remove();
                }, 220);
            }

            const timer = window.setTimeout(dismissToast, 10000);
            toast.querySelector('[data-mark-status-read]')?.addEventListener('submit', function (event) {
                event.preventDefault();
                const form = event.currentTarget;
                const button = form.querySelector('button[type="submit"]');
                const error = form.querySelector('[data-toast-error]');
                if (form.dataset.requestPending === 'true') return;
                const request = window.requestLoading.start(button, form);
                error.hidden = true;

                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then(function (response) {
                        if (!response.ok) {
                            const failure = new Error('Could not mark this notification as read.');
                            failure.uncertain = response.status >= 500;
                            throw failure;
                        }
                        return response.json();
                    })
                    .then(function (result) {
                        if (!result.ok) throw new Error('Could not mark this notification as read.');
                        window.clearTimeout(timer);
                        request.success('Notification marked as read.');
                        dismissToast();
                    })
                    .catch(function (failure) {
                        error.textContent = @json(__('Could not mark this notification as read. Please try again.'));
                        error.hidden = false;
                        if (failure instanceof TypeError || failure instanceof SyntaxError || failure.uncertain) {
                            request.uncertain('The connection was interrupted, so we could not confirm whether this notification was marked as read.');
                        } else {
                            request.error('Could not mark this notification as read. Please try again.');
                        }
                    });
            });
        });

        function setSidebarOpen(isOpen) {
            sidebar?.classList.toggle('open', isOpen);
            toggle?.setAttribute('aria-expanded', String(isOpen));
            toggle?.setAttribute('aria-label', isOpen ? 'Close applicant navigation' : 'Open applicant navigation');
            backdrop?.toggleAttribute('hidden', !isOpen);

            const icon = toggle?.querySelector('i');
            icon?.classList.toggle('fa-bars', !isOpen);
            icon?.classList.toggle('fa-chevron-left', isOpen);
        }

        if (
            document.documentElement.dataset.sidebarBehavior === 'expanded'
            && window.matchMedia('(max-width: 768px)').matches
        ) {
            setSidebarOpen(true);
        }

        document.querySelectorAll('[data-nav-group-toggle]').forEach(function (groupToggle) {
            const submenu = document.getElementById(groupToggle.getAttribute('aria-controls'));
            const matchesCurrentHash = Array.from(submenu?.querySelectorAll('a[href*="#"]') ?? [])
                .some(function (link) {
                    return new URL(link.href).hash === window.location.hash && window.location.hash !== '';
                });

            if (matchesCurrentHash) {
                groupToggle.setAttribute('aria-expanded', 'true');
                groupToggle.closest('[data-nav-group]')?.classList.add('is-open');
            }

            groupToggle.addEventListener('click', function () {
                const isExpanded = groupToggle.getAttribute('aria-expanded') === 'true';
                groupToggle.setAttribute('aria-expanded', String(!isExpanded));
                groupToggle.closest('[data-nav-group]')?.classList.toggle('is-open', !isExpanded);
            });
        });

        toggle?.addEventListener('click', function () {
            setSidebarOpen(!sidebar?.classList.contains('open'));
        });

        document.querySelector('[data-sidebar-close]')?.addEventListener('click', function () {
            setSidebarOpen(false);
            toggle?.focus();
        });

        backdrop?.addEventListener('click', function () {
            setSidebarOpen(false);
            toggle?.focus();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && sidebar?.classList.contains('open')) {
                setSidebarOpen(false);
                toggle?.focus();
            }
        });

        document.querySelector('[data-open-portal-help]')?.addEventListener('click', function () {
            document.querySelector('[data-help-toggle]')?.click();
        });
    });
</script>
