<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\News;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        $application = $user->latestApplication()->first();
        $announcements = News::published()
            ->whereIn('display_on', ['portal', 'both'])
            ->take(3)
            ->get();
        $appointments = Appointment::where('is_published', true)
            ->visibleToApplicant($user)
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->take(3)
            ->get();
        $appointmentNotifications = $user->unreadNotifications()
            ->get()
            ->filter(fn ($notification): bool => isset($notification->data['appointment_id']))
            ->take(3)
            ->values();
        $profile = $user->profile;
        $profileFields = [
            'first_name', 'last_name', 'sex', 'date_of_birth', 'contact_number',
            'present_address', 'permanent_address', 'applicant_category',
        ];
        $profileCompletion = $profile
            ? (int) round(collect($profileFields)->filter(fn ($field) => filled($profile->{$field}))->count() / count($profileFields) * 100)
            : 0;

        return view('dashboard', compact(
            'application',
            'announcements',
            'appointments',
            'appointmentNotifications',
            'profileCompletion',
        ));
    }
}
