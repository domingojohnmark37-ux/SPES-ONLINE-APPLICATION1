<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ApplicantRequirementsController;
use App\Http\Controllers\Admin\AdditionalRequirementController;
use App\Http\Controllers\Admin\AdminSettingsFeatureController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\MasterListController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\StatisticsReportController;
use App\Http\Controllers\Admin\ApplicantAuditController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ApplicantContactPesoController;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Route;

// Public
Route::get('/', function () {
    $news = \App\Models\News::published()->whereIn('display_on', ['landing', 'both'])->take(5)->get();
    return view('welcome', compact('news'));
})->name('home');

Route::view('/terms-and-conditions', 'terms')->name('terms');

// Authenticated users
Route::middleware([
    'auth',
    \App\Http\Middleware\ApplyApplicantLanguage::class,
    'admin.preferences',
    \App\Http\Middleware\TrackUserActivity::class,
])->group(function () {

    Route::get('/dashboard', function () {
        if (auth()->user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        $application = \App\Models\Application::where('user_id', auth()->id())->first();
        $announcements = \App\Models\News::published()
            ->whereIn('display_on', ['portal', 'both'])
            ->take(3)
            ->get();
        $user = auth()->user();
        $appointments = \App\Models\Appointment::where('is_published', true)
            ->visibleToApplicant($user)
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->take(3)
            ->get();
        $profile = $user->profile;
        $profileFields = [
            'first_name', 'last_name', 'sex', 'date_of_birth', 'contact_number',
            'present_address', 'permanent_address', 'applicant_category',
        ];
        $profileCompletion = $profile
            ? (int) round(collect($profileFields)->filter(fn ($field) => filled($profile->{$field}))->count() / count($profileFields) * 100)
            : 0;

        return view('dashboard', compact('application', 'announcements', 'appointments', 'profileCompletion'));
    })->name('dashboard');

    Route::get('/recent-notifications', function () {
        $announcements = \App\Models\News::published()
            ->whereIn('display_on', ['portal', 'both'])
            ->get()
            ->map(fn ($announcement): array => [
                'id' => null,
                'title' => $announcement->title,
                'message' => \Illuminate\Support\Str::limit(strip_tags($announcement->content), 180),
                'date' => $announcement->published_at,
                'status' => null,
            ]);
        $applicationUpdates = auth()->user()->notifications()
            ->get()
            ->filter(function ($notification): bool {
                $data = $notification->data;
                $title = mb_strtolower((string) ($data['title'] ?? ''));

                return in_array($data['status'] ?? null, ['approved', 'denied'], true)
                    || ($data['event'] ?? null) === 'application_feedback'
                    || (isset($data['application_id']) && $title === 'document requirement update')
                    || isset($data['appointment_id']);
            })
            ->map(fn ($notification): array => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Application status updated',
                'message' => $notification->data['message'] ?? 'Your SPES application status has been updated.',
                'date' => $notification->created_at,
                'appointment_date' => isset($notification->data['appointment_date'])
                    ? Carbon::parse($notification->data['appointment_date'])
                    : null,
                'appointment_location' => $notification->data['appointment_location'] ?? null,
                'status' => $notification->data['status'] ?? null,
                'read_at' => $notification->read_at,
            ]);
        $recentNotifications = $announcements
            ->concat($applicationUpdates)
            ->sortByDesc(fn (array $notification) => $notification['date']?->getTimestamp() ?? 0)
            ->values();

        return view('applicant.recent-notifications', compact('recentNotifications'));
    })->middleware(\App\Http\Middleware\EnsureApplicant::class)->name('applicant.notifications.recent');

    Route::get('/requirements', [ApplicantRequirementsController::class, 'index'])
        ->middleware(\App\Http\Middleware\EnsureApplicant::class)->name('applicant.requirements');
    Route::get('/requirements/{additionalRequirement}/template', [ApplicantRequirementsController::class, 'downloadTemplate'])
        ->middleware(\App\Http\Middleware\EnsureApplicant::class)->name('applicant.requirements.template');
    Route::get('/requirements/{additionalRequirement}/templates/{template}', [ApplicantRequirementsController::class, 'downloadTemplateFile'])
        ->middleware(\App\Http\Middleware\EnsureApplicant::class)->name('applicant.requirements.template-file');
    Route::post('/requirements/{additionalRequirement}/upload', [ApplicantRequirementsController::class, 'upload'])
        ->middleware(\App\Http\Middleware\EnsureApplicant::class)->name('applicant.requirements.upload');

    Route::get('/previous-notifications', function () {
        $filter = request()->query('filter', 'all');
        $search = request()->query('search', '');
        abort_unless(is_string($filter) && in_array($filter, ['all', 'events', 'announcements', 'system'], true), 400);
        abort_unless(is_string($search) && mb_strlen($search) <= 120, 400);

        $user = auth()->user();
        $history = $user->notifications()
            ->latest()
            ->get()
            ->map(function ($notification): array {
                $data = $notification->data;
                $title = (string) ($data['title'] ?? 'SPES Portal notification');
                $applicationStatus = in_array($data['status'] ?? null, ['approved', 'denied'], true);
                $category = isset($data['appointment_id']) || str_contains(strtolower($title), 'appointment')
                    ? 'events'
                    : (str_contains(strtolower($title), 'announcement') ? 'announcements' : 'system');

                return [
                    'title' => $title,
                    'message' => $data['message'] ?? 'There is a new update in your SPES application.',
                    'category' => $category,
                    'date' => $notification->created_at,
                    'location' => null,
                    'details' => isset($data['device'])
                        ? 'Device: '.$data['device'].(filled($data['ip_address'] ?? null) ? ' · IP: '.$data['ip_address'] : '')
                        : null,
                    'login_activity_id' => $data['login_activity_id'] ?? null,
                    'read_status' => $applicationStatus
                        ? ($notification->read_at
                            ? 'Read on '.$notification->read_at->format('M j, Y · g:i A')
                            : 'Unread — delivered in-app')
                        : null,
                ];
            });

        $notifiedLoginActivityIds = $history
            ->pluck('login_activity_id')
            ->filter()
            ->all();
        $loginHistory = \App\Models\ApplicantLoginActivity::query()
            ->where('user_id', $user->id)
            ->whereNotIn('id', $notifiedLoginActivityIds)
            ->get()
            ->map(fn ($activity): array => [
                'title' => 'Account sign-in',
                'message' => 'A sign-in was recorded from '.\App\Support\DeviceIdentifier::describe($activity->user_agent).'.',
                'category' => 'system',
                'date' => $activity->logged_in_at,
                'location' => null,
                'details' => 'Device: '.\App\Support\DeviceIdentifier::describe($activity->user_agent)
                    .(filled($activity->ip_address) ? ' · IP: '.$activity->ip_address : ''),
                'login_activity_id' => $activity->id,
            ]);

        $announcementHistory = \App\Models\News::published()
            ->whereIn('display_on', ['portal', 'both'])
            ->get()
            ->map(fn ($announcement): array => [
                'title' => $announcement->title,
                'message' => \Illuminate\Support\Str::limit(strip_tags($announcement->content), 220),
                'category' => 'announcements',
                'date' => $announcement->published_at,
                'location' => null,
            ]);

        $appointmentHistory = \App\Models\Appointment::where('is_published', true)
            ->visibleToApplicant($user)
            ->where('starts_at', '<=', now())
            ->get()
            ->map(fn ($appointment): array => [
                'title' => $appointment->title,
                'message' => $appointment->description ?: 'Your scheduled SPES appointment.',
                'category' => 'events',
                'date' => $appointment->starts_at,
                'location' => $appointment->location,
            ]);

        $accountHistory = collect([[
            'title' => 'Account Created',
            'message' => 'Your SPES Portal account has been successfully created. Welcome to the system!',
            'category' => 'system',
            'date' => $user->created_at,
            'location' => null,
        ]]);

        $notifications = $history
            ->concat($loginHistory)
            ->concat($announcementHistory)
            ->concat($appointmentHistory)
            ->concat($accountHistory)
            ->when($filter !== 'all', fn ($items) => $items->where('category', $filter))
            ->when($search !== '', function ($items) use ($search) {
                $term = mb_strtolower($search);

                return $items->filter(fn (array $item) =>
                    str_contains(mb_strtolower($item['title']), $term)
                    || str_contains(mb_strtolower($item['message']), $term)
                    || str_contains(mb_strtolower((string) ($item['details'] ?? '')), $term)
                    || str_contains(mb_strtolower((string) $item['location']), $term)
                );
            })
            ->sortByDesc(fn (array $item) => $item['date']->getTimestamp())
            ->values();

        return view('applicant.previous-notifications', compact('notifications', 'filter', 'search'));
    })->middleware(\App\Http\Middleware\EnsureApplicant::class)->name('applicant.notifications.previous');

    Route::get('/appointments', function () {
        $user = auth()->user();
        $monthInput = request()->query('month');
        abort_if($monthInput !== null && (!is_string($monthInput) || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $monthInput)), 400);

        $month = $monthInput ? Carbon::createFromFormat('!Y-m', $monthInput) : now()->startOfMonth();
        abort_if($monthInput !== null && $month->format('Y-m') !== $monthInput, 400);

        $calendarStart = $month->copy()->startOfWeek(CarbonInterface::SUNDAY);
        $calendarEnd = $month->copy()->endOfMonth()->endOfWeek(CarbonInterface::SATURDAY);
        $calendarAppointments = \App\Models\Appointment::where('is_published', true)
            ->visibleToApplicant($user)
            ->whereBetween('starts_at', [$calendarStart, $calendarEnd])
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn ($appointment) => $appointment->starts_at->toDateString());
        $upcomingAppointments = \App\Models\Appointment::where('is_published', true)
            ->visibleToApplicant($user)
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->get();
        $calendarWeeks = [];
        $day = $calendarStart->copy();

        while ($day->lte($calendarEnd)) {
            $week = [];
            for ($weekday = 0; $weekday < 7; $weekday++) {
                $week[] = $day->copy();
                $day->addDay();
            }
            $calendarWeeks[] = $week;
        }

        return view('applicant.appointments', compact(
            'month',
            'calendarStart',
            'calendarEnd',
            'calendarAppointments',
            'upcomingAppointments',
            'calendarWeeks',
        ));
    })->middleware(\App\Http\Middleware\EnsureApplicant::class)->name('applicant.appointments.index');

    // Notification endpoints (mark read)
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all', [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.readAll');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/settings', [\App\Http\Controllers\ApplicantSettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/account', [\App\Http\Controllers\ApplicantSettingsController::class, 'account'])->name('settings.account');
    Route::get('/settings/security', [\App\Http\Controllers\ApplicantSettingsController::class, 'security'])->name('settings.security');
    Route::get('/settings/notifications', [\App\Http\Controllers\ApplicantSettingsController::class, 'notifications'])->name('settings.notifications');
    Route::get('/settings/appearance', [\App\Http\Controllers\ApplicantSettingsController::class, 'appearance'])->name('settings.appearance');
    Route::get('/settings/language', [\App\Http\Controllers\ApplicantSettingsController::class, 'language'])->name('settings.language');
    Route::get('/settings/privacy', [\App\Http\Controllers\ApplicantSettingsController::class, 'privacy'])->name('settings.privacy');
    Route::get('/settings/accessibility', [\App\Http\Controllers\ApplicantSettingsController::class, 'accessibility'])->name('settings.accessibility');

    Route::put('/settings/password', [\App\Http\Controllers\ApplicantSettingsController::class, 'changePassword'])->name('settings.password.update');
    Route::post('/settings/email/request', [\App\Http\Controllers\ApplicantSettingsController::class, 'requestEmailChange'])->name('settings.email.request');
    Route::post('/settings/email/verify', [\App\Http\Controllers\ApplicantSettingsController::class, 'verifyEmailChange'])->name('settings.email.verify');
    Route::put('/settings/mobile-number', [\App\Http\Controllers\ApplicantSettingsController::class, 'updateMobileNumber'])
        ->middleware(\App\Http\Middleware\ThrottleApplicantSensitiveActions::class)
        ->name('settings.mobile-number.update');
    Route::put('/settings/notifications', [\App\Http\Controllers\ApplicantSettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
    Route::put('/settings/preferences', [\App\Http\Controllers\ApplicantSettingsController::class, 'updatePreferences'])->name('settings.preferences.update');
    Route::post('/settings/devices/logout', [\App\Http\Controllers\ApplicantSettingsController::class, 'logoutOtherDevices'])->name('settings.logout-other-devices');
    Route::get('/settings/{document}', [\App\Http\Controllers\ApplicantSettingsController::class, 'document'])->where('document', 'privacy-policy|data-privacy-notice')->name('settings.document');

    Route::get('/updates', function () {
        if (!auth()->user()->applications()->where('status', 'approved')->exists()) {
            return redirect()->route('dashboard')->with('info', 'Updates are available after your application is approved.');
        }

        $updates = \App\Models\News::published()->whereIn('display_on', ['portal', 'both'])->get();
        return view('updates', compact('updates'));
    })->name('updates');

    // Application
    Route::get('/apply',           [ApplicationController::class, 'create'])->name('applications.create');
    Route::post('/apply',          [ApplicationController::class, 'store'])->name('applications.store');
    Route::get('/my-application/edit', [ApplicationController::class, 'edit'])->name('applications.edit');
    Route::put('/my-application',   [ApplicationController::class, 'update'])->name('applications.update');
    Route::get('/my-application',  [ApplicationController::class, 'myApplication'])->name('applications.myApplication');

    // Uploaded document viewer for authenticated users/admins
    Route::get('/applications/{application}/documents/{document}/preview', [ApplicationController::class, 'viewDocument'])
        ->where('document', 'resume|certificate_enrollment|certificate_grade|application_letter|indigency')
        ->name('applications.document.preview');
    Route::get('/applications/{application}/documents/{document}', [ApplicationController::class, 'viewDocument'])
        ->where('document', 'resume|certificate_enrollment|certificate_grade|application_letter|indigency')
        ->name('applications.document.view');
    Route::get('/applications/{application}/documents/{document}/file', [ApplicationController::class, 'streamDocument'])
        ->where('document', 'resume|certificate_enrollment|certificate_grade|application_letter|indigency')
        ->name('applications.document.stream');
    Route::get('/applications/{application}/additional-requirements/{additionalRequirement}/document', [ApplicantRequirementsController::class, 'viewDocument'])
        ->name('applications.additional-requirements.document');
});

Route::middleware([
    'auth',
    \App\Http\Middleware\EnsureApplicant::class,
    \App\Http\Middleware\TrackUserActivity::class,
])->group(function () {
    Route::get('/contact-peso', [ApplicantContactPesoController::class, 'index'])->name('contact-peso.index');
});

// Admin
Route::middleware(['auth', 'admin', 'admin.preferences', \App\Http\Middleware\TrackUserActivity::class])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/dashboard', function () {
        $stats = [
            'total'    => \App\Models\Application::count(),
            'pending'  => \App\Models\Application::where('status', 'pending')->count(),
            'approved' => \App\Models\Application::where('status', 'approved')->count(),
            'denied'   => \App\Models\Application::where('status', 'denied')->count(),
            'users'    => \App\Models\User::where('role', 'user')->count(),
        ];

        $pendingApplications = \App\Models\Application::with('user')
            ->where('status', 'pending')
            ->latest()
            ->take(6)
            ->get();

        $applicationActivities = \App\Models\Application::with('user')
            ->latest()
            ->take(4)
            ->get()
            ->map(function ($application) {
                $timeLabel = $application->created_at->isToday()
                    ? 'Submitted today · ' . $application->created_at->format('h:i A')
                    : 'Submitted on ' . $application->created_at->format('M j, Y · h:i A');

                return [
                    'message'   => "{$application->user->name} submitted a new application.",
                    'time'      => $timeLabel,
                    'icon'      => 'fa-solid fa-file-lines',
                    'timestamp' => $application->created_at->timestamp,
                ];
            });

        $userActivities = \App\Models\User::where('role', 'user')
            ->latest()
            ->take(4)
            ->get()
            ->map(function ($user) {
                $timeLabel = $user->created_at->isToday()
                    ? 'Joined today · ' . $user->created_at->format('h:i A')
                    : 'Joined on ' . $user->created_at->format('M j, Y · h:i A');

                return [
                    'message'   => "{$user->name} created an account.",
                    'time'      => $timeLabel,
                    'icon'      => 'fa-solid fa-user-plus',
                    'timestamp' => $user->created_at->timestamp,
                ];
            });

        $recentActivities = $applicationActivities
            ->concat($userActivities)
            ->sortByDesc('timestamp')
            ->values()
            ->take(6);

        return view('admin.dashboard', compact('stats', 'pendingApplications', 'recentActivities'));
    })->name('dashboard');

    Route::get('/applications',                         [ApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/{application}',          [ApplicationController::class, 'show'])->name('applications.show');
    Route::post('/applications/{application}/approve', [ApplicationController::class, 'approve'])->name('applications.approve');
    Route::post('/applications/{application}/deny',    [ApplicationController::class, 'deny'])->name('applications.deny');
    Route::post('/applications/{application}/comment', [ApplicationController::class, 'addComment'])->name('applications.comment');
    Route::get('/users', [ApplicationController::class, 'users'])->name('users');
    Route::get('/users/{user}', [ApplicationController::class, 'showUser'])->name('users.show');
    Route::get('/statistics-report', StatisticsReportController::class)->name('statistics-report');
    Route::get('/applicant-audit', [ApplicantAuditController::class, 'index'])->name('applicant-audit.index');
    Route::get('/applicant-audit/export', [ApplicantAuditController::class, 'export'])->name('applicant-audit.export');
    Route::get('/applicant-audit/applicants/{applicant}/status-history', [ApplicantAuditController::class, 'statusHistory'])
        ->name('applicant-audit.status-history');
    Route::get('/applicant-audit/applicants/{applicant}', [ApplicantAuditController::class, 'show'])
        ->name('applicant-audit.show');
    Route::get('/applicant-audit/events/{auditLog}', [ApplicantAuditController::class, 'event'])
        ->name('applicant-audit.event');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('/settings/preferences', [AdminSettingsFeatureController::class, 'preferences'])->name('preferences');
    Route::put('/settings/preferences', [AdminSettingsFeatureController::class, 'updatePreferences'])->name('preferences.update');
    Route::get('/manual', [AdminSettingsFeatureController::class, 'manual'])->name('manual');
    Route::get('/security', [AdminSettingsFeatureController::class, 'security'])->name('security');
    Route::put('/security', [AdminSettingsFeatureController::class, 'updateSecurity'])->name('security.update');
    Route::get('/backup', [AdminSettingsFeatureController::class, 'backup'])->name('backup.index');
    Route::post('/backup', [AdminSettingsFeatureController::class, 'downloadBackup'])->name('backup.download');
    Route::post('/backup/restore', [AdminSettingsFeatureController::class, 'restoreBackup'])->name('backup.restore');
    Route::get('/backup/archives/{year}/download', [AdminSettingsFeatureController::class, 'downloadAnnualBackup'])
        ->whereNumber('year')->name('backup.archive.download');
    Route::post('/backup/archives/{year}/restore', [AdminSettingsFeatureController::class, 'restoreAnnualBackup'])
        ->whereNumber('year')->name('backup.archive.restore');
    
    // Export
    Route::get('/applications/export', [ExportController::class, 'masterList'])->name('applications.export');
    
    // Master List
    Route::get('/master-list', [MasterListController::class, 'index'])->name('masterlist.index');
    Route::post('/master-list', [MasterListController::class, 'store'])->name('masterlist.store');
    Route::get('/master-list/{masterList}/download', [MasterListController::class, 'download'])->name('masterlist.download');
    
    // News
    Route::resource('news', NewsController::class)->except(['show']);
    Route::post('/news/{news}/toggle', [NewsController::class, 'togglePublish'])->name('news.toggle');

    // Shared applicant schedule
    Route::resource('appointments', AppointmentController::class)->except(['show']);
    Route::post('/appointments/{appointment}/toggle', [AppointmentController::class, 'togglePublish'])->name('appointments.toggle');

    // Requirements shown on the applicant checklist
    Route::resource('additional-requirements', AdditionalRequirementController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['additional-requirements' => 'additionalRequirement']);
});

require __DIR__ . '/auth.php';
