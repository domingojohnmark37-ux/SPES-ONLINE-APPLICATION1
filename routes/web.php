<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\MasterListController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\PostApprovalFormController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Public
Route::get('/', function () {
    $news = \App\Models\News::published()->whereIn('display_on', ['landing', 'both'])->take(5)->get();
    return view('welcome', compact('news'));
})->name('home');

// Authenticated users
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', function () {
        if (auth()->user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        $application = \App\Models\Application::where('user_id', auth()->id())->first();
        return view('dashboard', compact('application'));
    })->name('dashboard');

    // Notification endpoints (mark read)
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all', [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.readAll');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

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

    // Post-approval application form (only unlocked after admin approves)
    Route::get('/my-application/form-2',  [PostApprovalFormController::class, 'showForm2'])->name('applications.form2');
    Route::post('/my-application/form-2', [PostApprovalFormController::class, 'storeForm2'])->name('applications.form2.store');

    // Uploaded document viewer for authenticated users/admins
    Route::get('/applications/{application}/documents/{document}', [ApplicationController::class, 'viewDocument'])
        ->where('document', 'resume|certificate_enrollment|certificate_grade|application_letter|indigency')
        ->name('applications.document.view');
});

// Admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

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
    Route::get('/applications/{application}/forms',    [ApplicationController::class, 'showApplicationForms'])->name('applications.forms');
    Route::post('/applications/{application}/approve', [ApplicationController::class, 'approve'])->name('applications.approve');
    Route::post('/applications/{application}/deny',    [ApplicationController::class, 'deny'])->name('applications.deny');
    Route::post('/applications/{application}/comment', [ApplicationController::class, 'addComment'])->name('applications.comment');
    Route::get('/users', [ApplicationController::class, 'users'])->name('users');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    
    // Export
    Route::get('/applications/export', [ExportController::class, 'masterList'])->name('applications.export');
    
    // Master List
    Route::get('/master-list', [MasterListController::class, 'index'])->name('masterlist.index');
    Route::post('/master-list', [MasterListController::class, 'store'])->name('masterlist.store');
    
    // News
    Route::resource('news', NewsController::class)->except(['show']);
    Route::post('/news/{news}/toggle', [NewsController::class, 'togglePublish'])->name('news.toggle');
});

require __DIR__ . '/auth.php';
