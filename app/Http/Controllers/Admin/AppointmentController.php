<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use App\Notifications\ApplicantPortalUpdate;
use App\Services\ApplicantNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(): View
    {
        $appointments = Appointment::with(['targetUser', 'targetApplicants'])
            ->orderByDesc('starts_at')
            ->paginate(20);

        return view('admin.appointments.index', compact('appointments'));
    }

    public function create(): View
    {
        return view('admin.appointments.form', [
            'appointment' => new Appointment(['target_audience' => 'all_applicants']),
            'applicants' => $this->applicants(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateAppointment($request);
        $targetApplicantIds = $validated['target_user_ids'] ?? [];
        unset($validated['target_user_ids']);
        $validated['created_by'] = Auth::id();

        $appointment = Appointment::create($validated);
        $appointment->targetApplicants()->sync($targetApplicantIds);

        return redirect()->route('admin.appointments.index')
            ->with('success', 'Appointment created as a draft.');
    }

    public function edit(Appointment $appointment): View
    {
        $appointment->load('targetApplicants');

        return view('admin.appointments.form', [
            'appointment' => $appointment,
            'applicants' => $this->applicants(),
        ]);
    }

    public function update(Request $request, Appointment $appointment, ApplicantNotificationService $notifications): RedirectResponse
    {
        $wasPublished = $appointment->is_published;
        $previousTargetApplicantIds = $appointment->targetApplicants()->pluck('users.id')->all();
        $validated = $this->validateAppointment($request);
        $targetApplicantIds = $validated['target_user_ids'] ?? [];
        unset($validated['target_user_ids']);
        $appointment->update($validated);
        $appointment->targetApplicants()->sync($targetApplicantIds);
        $recipientsChanged = $previousTargetApplicantIds !== $appointment->targetApplicants()->pluck('users.id')->all();

        if (
            $wasPublished
            && $appointment->is_published
            && ($recipientsChanged || $appointment->wasChanged([
                'title', 'description', 'location', 'starts_at',
                'target_audience', 'target_user_id',
            ]))
        ) {
            $this->notifyApplicantsAboutAppointment($appointment, $notifications);
        }

        return redirect()->route('admin.appointments.index')
            ->with('success', 'Appointment updated.');
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        $appointment->delete();

        return redirect()->route('admin.appointments.index')
            ->with('success', 'Appointment deleted.');
    }

    public function togglePublish(Appointment $appointment, ApplicantNotificationService $notifications): RedirectResponse
    {
        $appointment->update(['is_published' => ! $appointment->is_published]);

        if ($appointment->is_published) {
            $this->notifyApplicantsAboutAppointment($appointment, $notifications);
        }

        return redirect()->route('admin.appointments.index')
            ->with('success', $appointment->is_published
                ? 'Appointment published to the applicant portal.'
                : 'Appointment unpublished.');
    }

    private function validateAppointment(Request $request): array
    {
        $request->merge([
            'target_audience' => $request->input('target_audience', 'all_applicants'),
        ]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'target_audience' => ['required', Rule::in([
                'all_applicants',
                'approved_applicants',
                'pending_applicants',
                'denied_applicants',
                'specific_applicant',
                'multiple_applicants',
            ])],
            'target_user_id' => [
                'nullable',
                'required_if:target_audience,specific_applicant',
                Rule::exists('users', 'id')->where('role', 'user'),
            ],
            'target_user_ids' => ['required_if:target_audience,multiple_applicants', 'array', 'min:2'],
            'target_user_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where('role', 'user'),
            ],
        ]);

        if ($validated['target_audience'] !== 'specific_applicant') {
            $validated['target_user_id'] = null;
        }
        if ($validated['target_audience'] !== 'multiple_applicants') {
            $validated['target_user_ids'] = [];
        }

        return $validated;
    }

    private function applicants()
    {
        return User::query()
            ->where('role', 'user')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    private function notifyApplicantsAboutAppointment(
        Appointment $appointment,
        ApplicantNotificationService $notifications,
    ): void {
        $notifications->notifyUsers(
            $appointment->recipientUsers(),
            $this->appointmentNotification($appointment),
            "appointment:{$appointment->id}:{$appointment->updated_at?->getTimestamp()}",
        );
    }

    private function appointmentNotification(Appointment $appointment): ApplicantPortalUpdate
    {
        $details = $appointment->starts_at->format('M j, Y g:i A');
        if ($appointment->location) {
            $details .= ' at '.$appointment->location;
        }

        return new ApplicantPortalUpdate(
            'notify_appointments',
            'Appointment reminder',
            "You have an appointment, \"{$appointment->title}\", on {$details}.",
            [
                'appointment_id' => $appointment->id,
                'appointment_date' => $appointment->starts_at->toIso8601String(),
                'appointment_location' => $appointment->location,
            ],
        );
    }
}
