<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplicantSettings\ChangeAccountPasswordRequest;
use App\Http\Requests\ApplicantSettings\LogoutOtherDevicesRequest;
use App\Http\Requests\ApplicantSettings\RequestEmailChangeRequest;
use App\Http\Requests\ApplicantSettings\UpdateNotificationsRequest;
use App\Http\Requests\ApplicantSettings\UpdateMobileNumberRequest;
use App\Http\Requests\ApplicantSettings\UpdatePreferencesRequest;
use App\Http\Requests\ApplicantSettings\VerifyEmailChangeRequest;
use App\Mail\ApplicantEmailChangeCode;
use App\Models\ApplicantLoginActivity;
use App\Models\ApplicantSetting;
use App\Models\PendingEmailChange;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ApplicantSettingsController extends Controller
{
    public function index(Request $request): View
    {
        return $this->showSection($request, 'account');
    }

    public function account(Request $request): View
    {
        return $this->showSection($request, 'account');
    }

    public function security(Request $request): View
    {
        return $this->showSection($request, 'security');
    }

    public function notifications(Request $request): View
    {
        return $this->showSection($request, 'notifications');
    }

    public function appearance(Request $request): View
    {
        return $this->showSection($request, 'appearance');
    }

    public function language(Request $request): View
    {
        return $this->showSection($request, 'language');
    }

    public function privacy(Request $request): View
    {
        return $this->showSection($request, 'privacy');
    }

    public function accessibility(Request $request): View
    {
        return $this->showSection($request, 'accessibility');
    }

    private function showSection(Request $request, string $section): View
    {
        $user = $request->user();
        $settings = ApplicantSetting::firstOrNew(['user_id' => $user->id]);

        return view('applicant.settings.index', [
            'user' => $user,
            'settings' => $settings,
            'section' => in_array($section, ['account', 'security', 'notifications', 'appearance', 'language', 'privacy', 'accessibility'], true) ? $section : 'account',
            'pendingEmailChange' => PendingEmailChange::where('user_id', $user->id)->first(),
            'loginActivities' => ApplicantLoginActivity::where('user_id', $user->id)
                ->latest('logged_in_at')
                ->take(10)
                ->get()
                ->map(function (ApplicantLoginActivity $activity) use ($request): ApplicantLoginActivity {
                    $activity->browser_label = $this->browserLabel($activity->user_agent);
                    $activity->is_current = $activity->session_id !== null
                        && hash_equals($activity->session_id, $request->session()->getId());

                    return $activity;
                }),
        ]);
    }

    public function changePassword(ChangeAccountPasswordRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user, $request, $auditLogger): void {
            $user->forceFill([
                'password' => Hash::make($request->validated('password')),
            ])->save();
            $auditLogger->recordPasswordChanged($user);
        });

        return back()->with('settings_success', 'Your password has been changed.');
    }

    public function requestEmailChange(RequestEmailChangeRequest $request): RedirectResponse
    {
        $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $pending = PendingEmailChange::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'email' => $request->validated('email'),
                'pin_hash' => Hash::make($pin),
                'pin_expires_at' => now()->addMinutes(10),
                'failed_attempts' => 0,
            ],
        );

        Mail::to($pending->email)->send(new ApplicantEmailChangeCode($pin));

        return back()->with('settings_success', 'A 6-digit code has been sent to the new email address.');
    }

    public function verifyEmailChange(VerifyEmailChangeRequest $request): RedirectResponse
    {
        $user = $request->user();
        $pending = PendingEmailChange::where('user_id', $user->id)->first();

        if (! $pending || $pending->pin_expires_at->isPast()) {
            $pending?->delete();

            return back()->withErrors(['pin' => 'That code is no longer available. Request a new code to continue.']);
        }

        if (! Hash::check($request->validated('pin'), $pending->pin_hash)) {
            $pending->failed_attempts = ($pending->failed_attempts ?? 0) + 1;
            if ($pending->failed_attempts >= 5) {
                $pending->delete();
            } else {
                $pending->save();
            }

            return back()->withErrors(['pin' => 'That code is incorrect. Check the code and try again.']);
        }

        $user->forceFill([
            'email' => $pending->email,
            'email_verified_at' => now(),
        ])->save();

        $pending->delete();

        return back()->with('settings_success', 'Your email address has been verified and updated.');
    }

    public function updateMobileNumber(UpdateMobileNumberRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();
        $oldMobileNumber = $user->profile?->contact_number ?: $user->contact_number;
        $newMobileNumber = $request->validated('mobile_number');

        DB::transaction(function () use ($user, $oldMobileNumber, $newMobileNumber, $auditLogger): void {
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                ['contact_number' => $newMobileNumber],
            );

            $auditLogger->recordChanges(
                ['mobile_number' => $oldMobileNumber],
                ['mobile_number' => $newMobileNumber],
                'Applicant Information',
                $user,
                $user,
                $user->applications()->latest('created_at')->first(),
                \App\Models\AuditAction::INFORMATION_UPDATED,
                ['description' => 'Applicant contact information changed'],
            );
        });

        return back()->with('settings_success', 'Your mobile number has been updated.');
    }

    public function updateNotifications(UpdateNotificationsRequest $request): RedirectResponse
    {
        $preferences = $request->validated();
        foreach (['email_notifications', 'system_notifications', 'application_updates'] as $key) {
            $preferences[$key] = $request->boolean($key);
        }

        ApplicantSetting::updateOrCreate(
            ['user_id' => $request->user()->id],
            $preferences,
        );

        return back()->with('settings_success', 'Your notification choices have been saved.');
    }

    public function updatePreferences(UpdatePreferencesRequest $request): RedirectResponse
    {
        $preferences = $request->validated();
        if (array_key_exists('theme_preference', $preferences)) {
            $preferences['appearance'] = $preferences['theme_preference'];
        } elseif (array_key_exists('appearance', $preferences)) {
            $preferences['theme_preference'] = $preferences['appearance'];
        }
        if (array_key_exists('sidebar_behavior', $preferences) || array_key_exists('font_size', $preferences)) {
            $currentSettings = ApplicantSetting::firstOrNew(['user_id' => $request->user()->id]);
            $preferences['sidebar_behavior'] ??= $currentSettings->sidebar_behavior ?? 'auto';
            $preferences['font_size'] ??= $currentSettings->font_size ?? 'medium';
        }

        ApplicantSetting::updateOrCreate(
            ['user_id' => $request->user()->id],
            $preferences,
        );

        return back()->with('settings_success', 'Your preferences have been saved.');
    }

    public function logoutOtherDevices(LogoutOtherDevicesRequest $request): RedirectResponse
    {
        $currentSessionId = $request->session()->getId();
        DB::table(config('session.table'))
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        Auth::logoutOtherDevices($request->validated('current_password'));

        return back()->with('settings_success', 'You have been signed out of your other sessions.');
    }

    public function document(string $document): View
    {
        abort_unless(in_array($document, ['privacy-policy', 'data-privacy-notice'], true), 404);

        return view('applicant.settings.document', compact('document'));
    }

    private function browserLabel(?string $userAgent): string
    {
        $agent = (string) $userAgent;

        foreach ([
            'Edg/' => 'Microsoft Edge',
            'OPR/' => 'Opera',
            'Firefox/' => 'Mozilla Firefox',
            'Chrome/' => 'Google Chrome',
            'Safari/' => 'Safari',
        ] as $marker => $name) {
            if (str_contains($agent, $marker)) {
                return $name;
            }
        }

        return $agent === '' ? 'Unknown browser' : mb_substr($agent, 0, 120);
    }
}
