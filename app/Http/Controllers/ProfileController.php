<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\AuditAction;
use App\Services\AuditLogger;
use App\Models\UserProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        if ($request->query('edit') === '1') {
            $request->session()->forget('profile_read_only');
        }

        $user = $request->user();
        $profile = $user->profile;

        return view('profile.edit', [
            'user' => $user,
            'profile' => $profile,
            'readOnly' => $request->session()->get('profile_read_only', false),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $originalUser = $user->getOriginal();
        $originalProfile = $user->profile?->getOriginal() ?? [];
        $applicant = $user->role === 'user' ? $user : null;
        $application = $applicant?->applications()->latest('created_at')->first();

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $user->profile_photo = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        unset($data['profile_photo']);

        $user->email = $data['email'];

        if (isset($data['name']) && trim((string) $data['name']) !== '') {
            $user->name = $data['name'];
        } else {
            $profileFirstName = $data['first_name'] ?? $user->profile?->first_name ?? '';
            $profileMiddleName = $data['middle_name'] ?? $user->profile?->middle_name ?? '';
            $profileLastName = $data['last_name'] ?? $user->profile?->last_name ?? '';

            $fullName = trim($profileFirstName . ' ' . $profileMiddleName . ' ' . $profileLastName);
            if ($fullName !== '') {
                $user->name = $fullName;
            }
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $profileData = collect($data)->only([
            'last_name',
            'first_name',
            'middle_name',
            'sex',
            'date_of_birth',
            'place_of_birth',
            'status',
            'citizenship',
            'social_media',
            'contact_number',
            'present_address',
            'permanent_address',
            'applicant_category',
            'education_history',
            'father_name',
            'father_contact_number',
            'father_occupation',
            'mother_name',
            'mother_contact_number',
            'mother_occupation',
            'parent_status_details',
            'special_skills',
        ])->toArray();

        $parentStatusOptions = ['Living Together', 'Solo Parent', 'Orphan', 'Guardian'];
        $selectedParentStatuses = $data['parent_status_details'] ?? [];
        $profileData['parent_status_details'] = collect($parentStatusOptions)
            ->mapWithKeys(fn ($status) => [$status => in_array($status, $selectedParentStatuses, true)])
            ->all();

        DB::transaction(function () use ($user, $profileData, $originalUser, $originalProfile, $applicant, $application, $auditLogger): void {
            $user->save();
            $profile = $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );
            if ($applicant) {
                $oldValues = array_intersect_key($originalUser, array_flip(['name', 'email']));
                $newValues = array_intersect_key($user->getAttributes(), array_flip(['name', 'email']));
                $profileFields = array_keys($profileData);
                $oldValues += array_intersect_key($originalProfile, array_flip($profileFields));
                $newValues += array_intersect_key($profile->getAttributes(), array_flip($profileFields));
                $auditLogger->recordChanges(
                    $oldValues,
                    $newValues,
                    'Applicant Information',
                    $user,
                    $user,
                    $application,
                    AuditAction::INFORMATION_UPDATED,
                );
            }
        });

        $request->session()->put('profile_read_only', true);

        return Redirect::route('profile.edit')
            ->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();
        DB::transaction(function () use ($user, $auditLogger): void {
            if ($user->role === 'user') {
                $auditLogger->record(
                    AuditAction::APPLICANT_DELETED,
                    'Applicant Information',
                    $user,
                    $user,
                    $user->applications()->latest('created_at')->first(),
                    ['description' => 'Applicant deleted their account'],
                );
            }
            $user->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
