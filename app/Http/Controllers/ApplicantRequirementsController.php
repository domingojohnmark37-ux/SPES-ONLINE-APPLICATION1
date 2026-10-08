<?php

namespace App\Http\Controllers;

use App\Models\AdditionalRequirement;
use App\Models\AdditionalRequirementTemplate;
use App\Models\Application;
use App\Models\ApplicationAdditionalRequirement;
use App\Models\AuditAction;
use App\Models\SystemSetting;
use App\Services\ApplicationApprovalCapacity;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class ApplicantRequirementsController extends Controller
{
    public function index(ApplicationApprovalCapacity $capacity): View
    {
        $application = Application::where('user_id', Auth::id())->latest('created_at')->first();
        $additionalRequirements = AdditionalRequirement::query()
            ->where('is_active', true)
            ->orWhereHas('submissions', fn ($query) => $query->where('application_id', $application?->id ?? 0))
            ->with(['submissions' => fn ($query) => $query->where('application_id', $application?->id ?? 0)])
            ->with('templates')
            ->orderBy('name')
            ->get()
            ->filter(fn (AdditionalRequirement $requirement) => $requirement->isAvailableTo($application))
            ->values();
        $submittedRequirements = $application
            ? $additionalRequirements->filter(fn ($requirement) => $requirement->submissions->isNotEmpty())->count()
            : 0;
        $totalRequirements = $additionalRequirements->count();
        $hasLockedRequirements = $application !== null
            && $application->status !== 'approved'
            && AdditionalRequirement::where('is_active', true)
                ->where('audience', 'approved_applicants')
                ->exists();
        $approvalCapacityClosed = $application?->status !== 'approved'
            && $capacity->isFull(SystemSetting::current());

        return view('applicant.requirements', compact(
            'application',
            'additionalRequirements',
            'submittedRequirements',
            'totalRequirements',
            'hasLockedRequirements',
            'approvalCapacityClosed',
        ));
    }

    public function downloadTemplate(AdditionalRequirement $additionalRequirement)
    {
        $application = Application::where('user_id', Auth::id())->latest('created_at')->first();
        abort_unless($additionalRequirement->isAvailableTo($application), 403);
        abort_unless($additionalRequirement->is_active && $additionalRequirement->template_path, 404);
        abort_unless(Storage::disk('local')->exists($additionalRequirement->template_path), 404);

        $filename = basename($additionalRequirement->template_original_name ?: $additionalRequirement->name);
        $filename = str_replace(['"', "\r", "\n"], '', $filename);

        return Storage::disk('local')->download($additionalRequirement->template_path, $filename);
    }

    public function downloadTemplateFile(
        AdditionalRequirement $additionalRequirement,
        AdditionalRequirementTemplate $template,
    ) {
        $application = Application::where('user_id', Auth::id())->latest('created_at')->first();
        abort_unless($additionalRequirement->isAvailableTo($application), 403);
        abort_unless($additionalRequirement->is_active, 404);
        abort_unless($template->additional_requirement_id === $additionalRequirement->id, 404);
        abort_unless(Storage::disk('local')->exists($template->file_path), 404);

        $filename = str_replace(['"', "\r", "\n"], '', basename($template->original_name));

        return Storage::disk('local')->download($template->file_path, $filename);
    }

    public function upload(
        Request $request,
        AdditionalRequirement $additionalRequirement,
        AuditLogger $auditLogger,
        ApplicationApprovalCapacity $capacity,
    ): RedirectResponse
    {
        abort_unless($additionalRequirement->is_active, 404);
        $application = Application::where('user_id', Auth::id())->latest('created_at')->firstOrFail();
        abort_unless($additionalRequirement->isAvailableTo($application), 403);
        if ($application->status !== 'approved' && $capacity->isFull(SystemSetting::current())) {
            return redirect()->route('applicant.requirements')
                ->with('approval_capacity_closed', true);
        }
        $validated = $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
        ]);

        $existingSubmission = ApplicationAdditionalRequirement::where('application_id', $application->id)
            ->where('additional_requirement_id', $additionalRequirement->id)
            ->first();
        $file = $validated['document'];
        $path = $file->store("applications/{$application->id}/additional-requirements", 'local');
        if (!is_string($path)) {
            throw new RuntimeException('Unable to store the uploaded application requirement.');
        }

        DB::transaction(function () use ($existingSubmission, $application, $additionalRequirement, $path, $file, $auditLogger): void {
            if ($existingSubmission) {
                Storage::disk('local')->delete($existingSubmission->file_path);
                $existingSubmission->update([
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                ]);
            } else {
                ApplicationAdditionalRequirement::create([
                    'application_id' => $application->id,
                    'additional_requirement_id' => $additionalRequirement->id,
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }

            $auditLogger->record(
                AuditAction::DOCUMENT_SUBMITTED,
                'Application Documents',
                Auth::user(),
                Auth::user(),
                $application,
                ['description' => $additionalRequirement->name.' submitted'],
            );
        });

        return redirect()->route('applicant.requirements')
            ->with('success', "{$additionalRequirement->name} uploaded successfully.");
    }

    public function viewDocument(Application $application, AdditionalRequirement $additionalRequirement)
    {
        $user = Auth::user();
        abort_unless($user->role === 'admin' || $application->user_id === $user->id, 403);
        abort_unless(
            $user->role === 'admin' || $additionalRequirement->isAvailableTo($application),
            403,
        );

        $submission = ApplicationAdditionalRequirement::where('application_id', $application->id)
            ->where('additional_requirement_id', $additionalRequirement->id)
            ->firstOrFail();
        abort_unless(Storage::disk('local')->exists($submission->file_path), 404);

        return response()->file(Storage::disk('local')->path($submission->file_path), [
            'Content-Disposition' => 'inline; filename="'.str_replace(['"', "\r", "\n"], '', basename($submission->original_name)).'"',
        ]);
    }
}
