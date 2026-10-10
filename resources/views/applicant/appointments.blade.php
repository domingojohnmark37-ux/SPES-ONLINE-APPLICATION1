<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Appointments') }} — {{ __('SPES Portal') }}</title>
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
        .page-wrapper { min-height:100vh; margin-left:var(--sidebar-w); padding:78px 16px 18px; }
        .page-content { display:grid; grid-template-columns:minmax(0,2fr) minmax(275px,.9fr); gap:12px; max-width:1600px; margin:0 auto; }
        .page-content.is-list-view { grid-template-columns:minmax(0,1fr); }
        .page-content.is-list-view .calendar-panel,
        .page-content.is-list-view .side-panel-section:not(.upcoming-panel) { display:none; }
        .page-content.is-list-view .side-panel { display:block; overflow:visible; }
        .page-content.is-list-view .upcoming-panel { max-height:none; overflow:visible; }
        .calendar-panel,.side-panel-section { min-width:0; overflow:hidden; border:1px solid var(--border); border-radius:14px; background:var(--white); box-shadow:var(--shadow); }
        .appointment-view-switcher { display:flex; grid-column:1 / -1; justify-content:flex-end; }
        .view-tabs,.month-navigation { display:flex; align-items:center; gap:6px; }
        .view-tabs { padding:3px; border:1px solid var(--border); border-radius:10px; background:var(--white); box-shadow:var(--shadow); }
        .view-tab,.month-button,.today-button { display:inline-flex; min-height:38px; align-items:center; justify-content:center; gap:7px; padding:8px 13px; border:1px solid transparent; border-radius:8px; background:transparent; color:var(--type-secondary-color); font:inherit; font-size:var(--type-caption); font-weight:700; text-decoration:none; white-space:nowrap; transition:background .18s ease,border-color .18s ease,color .18s ease,box-shadow .18s ease; }
        .view-tab[aria-current="page"] { border-color:var(--primary); background:var(--primary); color:#fff; }
        .month-button:hover,.today-button:hover,.view-tab:hover { border-color:var(--primary); color:var(--primary); }
        .view-tab[aria-current="location"] { border-color:var(--primary); background:var(--primary); color:#fff; box-shadow:0 2px 5px rgba(102,0,0,.18); }
        .month-navigation strong { min-width:112px; color:var(--type-primary-color); font-size:var(--type-secondary); text-align:center; }
        .month-button,.today-button { min-height:36px; border-color:var(--border); background:var(--white); }
        .month-button { width:36px; padding:0; }
        .calendar-table-wrap { overflow-x:auto; }
        .calendar-grid { width:100%; min-width:580px; table-layout:fixed; border-collapse:collapse; }
        .calendar-grid th { height:38px; color:var(--type-caption-color); font-size:var(--type-caption); font-weight:600; }
        .calendar-grid td { height:88px; vertical-align:top; border:1px solid var(--border); }
        .calendar-day { display:flex; height:100%; min-height:88px; flex-direction:column; gap:4px; padding:6px; }
        .calendar-day-number { align-self:flex-start; display:grid; width:22px; height:22px; place-items:center; border-radius:50%; color:var(--type-primary-color); font-size:var(--type-caption); }
        .calendar-day.is-outside .calendar-day-number { color:var(--type-caption-color); }
        .calendar-day.is-today .calendar-day-number { background:var(--accent); color:#302700; font-weight:800; }
        .calendar-event { display:block; overflow:hidden; padding:4px 5px; border-left:3px solid #c99400; border-radius:4px; background:rgba(212,167,44,.18); color:var(--type-primary-color); font-size:10px; line-height:1.3; text-decoration:none; text-overflow:ellipsis; }
        .calendar-event-time { display:block; color:var(--type-secondary-color); font-size:9px; }
        .calendar-event:hover { background:rgba(212,167,44,.3); }
        .side-panel { display:flex; flex-direction:column; gap:12px; }
        .side-panel-section { padding:16px; }
        .side-panel-heading { display:flex; align-items:center; justify-content:space-between; gap:12px; margin:0 0 14px; color:var(--type-primary-color); font-size:var(--type-secondary); font-weight:700; }
        .heading-title { display:flex; min-width:0; align-items:center; gap:9px; }
        .heading-title > svg.icon { display:grid; width:34px; height:34px; flex:0 0 auto; place-items:center; border-radius:10px; background:rgba(139,0,0,.08); color:var(--primary); }
        .heading-copy { display:grid; gap:2px; }
        .heading-copy strong { color:var(--type-primary-color); font-size:var(--type-secondary); }
        .heading-copy small { color:var(--type-caption-color); font-size:var(--type-caption); font-weight:500; }
        .appointment-count { display:inline-flex; min-width:25px; height:25px; align-items:center; justify-content:center; padding:0 7px; border-radius:999px; background:rgba(139,0,0,.08); color:var(--primary); font-size:var(--type-caption); font-weight:800; }
        .upcoming-list { display:grid; gap:10px; }
        .upcoming-item { display:grid; grid-template-columns:58px minmax(0,1fr); gap:14px; padding:14px; border:1px solid var(--border); border-left:3px solid #c99400; border-radius:12px; background:var(--white); transition:box-shadow .18s ease,transform .18s ease; }
        .upcoming-item:hover { transform:translateY(-1px); box-shadow:var(--shadow); }
        .date-tile { display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:62px; border:1px solid rgba(139,0,0,.1); border-radius:10px; background:rgba(139,0,0,.06); color:var(--type-primary-color); line-height:1.1; }
        .date-tile span:first-child { color:var(--primary); font-size:10px; font-weight:800; letter-spacing:.06em; text-transform:uppercase; }
        .date-tile strong { margin:3px 0; font-size:21px; }
        .date-tile span:last-child { color:var(--type-caption-color); font-size:10px; }
        .upcoming-copy { min-width:0; }
        .upcoming-copy h3 { margin:0 0 8px; color:var(--type-primary-color); font-size:var(--type-body); font-weight:750; line-height:1.35; overflow-wrap:anywhere; }
        .upcoming-copy p { margin:5px 0 0; color:var(--type-secondary-color); font-size:var(--type-caption); line-height:1.5; }
        .upcoming-copy p svg.icon { width:15px; color:var(--primary); }
        .upcoming-copy .appointment-description { margin-top:9px; color:var(--type-caption-color); }
        .page-content.is-list-view .upcoming-panel { padding:20px; }
        .page-content.is-list-view .side-panel-heading { margin-bottom:18px; }
        .page-content.is-list-view .heading-title > svg.icon { width:42px; height:42px; font-size:1.05rem; }
        .page-content.is-list-view .heading-copy strong { font-size:var(--type-section); }
        .page-content.is-list-view .heading-copy small { font-size:var(--type-secondary); }
        .page-content.is-list-view .upcoming-list { gap:12px; }
        .page-content.is-list-view .upcoming-item { grid-template-columns:70px minmax(0,1fr); gap:18px; padding:18px; }
        .page-content.is-list-view .date-tile { min-height:76px; }
        .page-content.is-list-view .date-tile strong { font-size:25px; }
        .page-content.is-list-view .upcoming-copy h3 { font-size:1.05rem; }
        .page-content.is-list-view .upcoming-copy p { font-size:var(--type-secondary); }
        .upcoming-title-row { display:flex; align-items:center; gap:10px; }
        .upcoming-title-row h3 { min-width:0; }
        .next-badge { flex:0 0 auto; padding:4px 8px; border-radius:999px; background:rgba(212,167,44,.18); color:#806000; font-size:10px; font-weight:800; }
        .appointment-legend { display:flex; align-items:center; gap:9px; color:var(--type-secondary-color); font-size:var(--type-caption); }
        .legend-dot { width:9px; height:9px; flex:0 0 auto; border-radius:50%; background:#c99400; }
        .appointment-note { display:flex; gap:9px; padding:11px; border-radius:8px; background:rgba(107,114,128,.08); color:var(--type-secondary-color); font-size:var(--type-caption); line-height:1.45; }
        .appointment-note svg.icon { margin-top:2px; color:var(--type-caption-color); }
        .appointment-empty { display:grid; justify-items:center; gap:8px; padding:40px 16px; border:1px dashed var(--border); border-radius:12px; color:var(--type-secondary-color); font-size:var(--type-secondary); text-align:center; }
        .appointment-empty svg.icon { color:var(--type-caption-color); font-size:1.5rem; }
        .side-panel-section[id],.calendar-panel[id] { scroll-margin-top:76px; }
        .upcoming-panel { max-height:560px; overflow-y:auto; }
        @media(max-width:1050px) {
            .page-content { grid-template-columns:minmax(0,1fr); }
            .side-panel { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); }
            .side-panel-section { border-right:1px solid var(--border); }
            .side-panel-section:nth-child(2n) { border-right:0; }
            .side-panel-section:last-child { grid-column:1 / -1; border-right:0; }
        }
        @media(max-width:768px) {
            .topbar { left:0; padding:0 14px; }
            .hamburger { display:block; }
            .page-wrapper { margin-left:0; padding:76px 12px 16px; }
        }
        @media(max-width:600px) {
            .topbar { height:56px; }
            .topbar h1 { font-size:var(--type-section); }
            .topbar p { display:none; }
            .page-wrapper { padding-top:68px; }
            .appointment-view-switcher { justify-content:stretch; }
            .view-tabs { width:100%; }
            .view-tab { flex:1; padding:8px 7px; }
            .calendar-toolbar { justify-content:center; }
            .month-navigation { width:100%; justify-content:center; }
            .page-content.is-list-view .upcoming-panel { padding:14px; }
            .page-content.is-list-view .upcoming-item { grid-template-columns:56px minmax(0,1fr); gap:12px; padding:13px; }
            .page-content.is-list-view .date-tile { min-height:62px; }
            .page-content.is-list-view .date-tile strong { font-size:21px; }
            .upcoming-title-row { align-items:flex-start; flex-direction:column; gap:6px; }
            .calendar-grid { min-width:490px; }
            .calendar-grid td,.calendar-day { min-height:72px; height:72px; }
            .calendar-day { padding:3px; gap:2px; }
            .calendar-event { padding:3px; font-size:9px; }
            .calendar-event-time { font-size:8px; }
            .side-panel { grid-template-columns:minmax(0,1fr); }
            .side-panel-section { border-right:0; }
            .side-panel-section:last-child { grid-column:auto; }
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
            <h1>{{ __('Appointments') }}</h1>
            <p>{{ __('View your scheduled appointments and upcoming reminders') }}</p>
        </div>
    </header>
    <main class="page-wrapper">
        <div class="page-content">
            <nav class="appointment-view-switcher" aria-label="{{ __('Appointment views') }}">
                <div class="view-tabs">
                    <a class="view-tab calendar-tab" href="#calendar" aria-current="location"><x-icon class="fa-regular fa-calendar" aria-hidden="true" />{{ __('Calendar') }}</a>
                    <a class="view-tab upcoming-tab" href="#upcoming-list" aria-current="false"><x-icon class="fa-solid fa-list" aria-hidden="true" />{{ __('Upcoming List') }}</a>
                </div>
            </nav>
            <section class="calendar-panel" id="calendar" aria-label="{{ __('Appointment calendar') }}">
                <div class="calendar-toolbar">
                    <div class="month-navigation" aria-label="{{ __('Calendar month navigation') }}">
                            <a class="month-button" href="{{ route('applicant.appointments.index', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}#calendar" aria-label="{{ __('Previous month') }}"><x-icon class="fa-solid fa-chevron-left" aria-hidden="true" /></a>
                            <strong>{{ $month->locale(app()->getLocale())->translatedFormat('F Y') }}</strong>
                            <a class="month-button" href="{{ route('applicant.appointments.index', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}#calendar" aria-label="{{ __('Next month') }}"><x-icon class="fa-solid fa-chevron-right" aria-hidden="true" /></a>
                            <a class="today-button" href="{{ route('applicant.appointments.index') }}#calendar">{{ __('Today') }}</a>
                        </div>
                    </div>
                    <div class="calendar-table-wrap">
                        <table class="calendar-grid">
                            <thead>
                                <tr>
                                    @foreach(['Sun' => 'Dom', 'Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Miy', 'Thu' => 'Huw', 'Fri' => 'Biy', 'Sat' => 'Sab'] as $weekday => $filipinoWeekday)
                                        <th scope="col">{{ app()->getLocale() === 'fil' ? $filipinoWeekday : $weekday }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($calendarWeeks as $week)
                                    <tr>
                                        @foreach($week as $day)
                                            @php($dayAppointments = $calendarAppointments->get($day->toDateString(), collect()))
                                            <td>
                                                <div class="calendar-day {{ $day->month !== $month->month ? 'is-outside' : '' }} {{ $day->isToday() ? 'is-today' : '' }}">
                                                    <span class="calendar-day-number">{{ $day->day }}</span>
                                                    @foreach($dayAppointments->take(2) as $appointment)
                                                        <div class="calendar-event" title="{{ $appointment->title }}">
                                                            {{ $appointment->title }}
                                                            <span class="calendar-event-time">{{ $appointment->starts_at->format('g:i A') }}</span>
                                                        </div>
                                                    @endforeach
                                                    @if($dayAppointments->count() > 2)
                                                        <span class="calendar-event-time">+{{ $dayAppointments->count() - 2 }} {{ __('more') }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
                <aside class="side-panel">
                    <section class="side-panel-section upcoming-panel" id="upcoming-list" aria-labelledby="upcoming-appointments-title">
                        <h2 class="side-panel-heading" id="upcoming-appointments-title">
                            <span class="heading-title">
                                <x-icon class="fa-regular fa-calendar-check" aria-hidden="true" />
                                <span class="heading-copy">
                                    <strong>{{ __('Upcoming Appointments') }}</strong>
                                    <small>{{ __('Published appointments scheduled from today onward') }}</small>
                                </span>
                            </span>
                            <span class="appointment-count" aria-label="{{ trans_choice(':count upcoming appointment|:count upcoming appointments', $upcomingAppointments->count(), ['count' => $upcomingAppointments->count()]) }}">{{ $upcomingAppointments->count() }}</span>
                        </h2>
                        @if($upcomingAppointments->isNotEmpty())
                            <div class="upcoming-list">
                                @foreach($upcomingAppointments as $appointment)
                                    @include('applicant.partials.appointment-list-item', ['appointment' => $appointment, 'showDescription' => true, 'isNext' => $loop->first])
                                @endforeach
                            </div>
                        @else
                            <div class="appointment-empty">
                                <x-icon class="fa-regular fa-calendar-xmark" aria-hidden="true" />
                                <strong>{{ __('No upcoming appointments') }}</strong>
                                <span>{{ __('Published appointments will appear here when they are scheduled.') }}</span>
                            </div>
                        @endif
                    </section>
                    <section class="side-panel-section" aria-labelledby="appointment-legend-title">
                        <h2 class="side-panel-heading" id="appointment-legend-title">{{ __('Calendar Legend') }}</h2>
                        <div class="appointment-legend"><span class="legend-dot" aria-hidden="true"></span> {{ __('Published PESO appointment') }}</div>
                    </section>
                    <div class="side-panel-section">
                        <div class="appointment-note">
                            <x-icon class="fa-solid fa-circle-info" aria-hidden="true" />
                            <span>{{ __('Stay on track. Check your upcoming appointments and prepare any documents requested by the PESO office.') }}</span>
                        </div>
                    </div>
                </aside>
        </div>
    </main>
    <x-portal-help-chat />
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const pageContent = document.querySelector('.page-content');
            const tabs = document.querySelectorAll('.appointment-view-switcher .calendar-tab, .appointment-view-switcher .upcoming-tab');
            const syncAppointmentTab = function () {
                const upcomingSelected = window.location.hash === '#upcoming-list';
                pageContent.classList.toggle('is-list-view', upcomingSelected);
                tabs.forEach(function (tab) {
                    const selected = upcomingSelected === tab.classList.contains('upcoming-tab');
                    tab.setAttribute('aria-current', selected ? 'location' : 'false');
                });
            };

            syncAppointmentTab();
            window.addEventListener('hashchange', syncAppointmentTab);
        });
    </script>
</body>
</html>
