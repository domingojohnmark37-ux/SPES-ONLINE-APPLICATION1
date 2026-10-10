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
use Throwable;

class ApplicantRequirementsController extends Controller
{
    public function index(ApplicationApprovalCapacity $capacity): View
    {
        $application = Application::where('user_id', Auth::id())->latest('created_at')->first();
        $additionalRequirements = AdditionalRequirement::query()
            ->where('is_active', true)
            ->orWhereHas('submissions', fn ($query) => $query->where('application_id', $application?->id ?? 0))
            ->with([
                'submissions' => fn ($query) => $query
                    ->where('application_id', $application?->id ?? 0)
                    ->orderBy('file_number'),
            ])
            ->with('templates')
            ->orderBy('name')
            ->get()
            ->filter(fn (AdditionalRequirement $requirement) => $requirement->isAvailableTo($application))
            ->values();
        $submittedFiles = $additionalRequirements->sum(fn ($requirement) => $requirement->submissions->count());
        $requiredFiles = $additionalRequirements->sum(fn ($requirement) => $requirement->expectedSubmissionCount(
            $requirement->submissions->max('file_number'),
        ));
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
            'submittedFiles',
            'requiredFiles',
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
        $storedPaths = [];
        try {
            DB::transaction(function () use ($request, $application, $additionalRequirement, $auditLogger, &$storedPaths): void {
                $application = Application::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
                $requirement = AdditionalRequirement::query()
                    ->with('templates')
                    ->whereKey($additionalRequirement->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $existingSlots = ApplicationAdditionalRequirement::query()
                    ->where('application_id', $application->id)
                    ->where('additional_requirement_id', $requirement->id)
                    ->pluck('file_number')
                    ->map(fn ($number): int => (int) $number)
                    ->all();
                $lastSubmittedFileNumber = $existingSlots === [] ? null : max($existingSlots);
                $missingSlots = array_values(array_diff(
                    range(1, $requirement->expectedSubmissionCount($lastSubmittedFileNumber)),
                    $existingSlots,
                ));

                if ($missingSlots === []) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'documents' => ['All files for this requirement have already been submitted.'],
                    ]);
                }

                $validated = $request->validate([
                    'documents' => ['required', 'array', 'size:'.count($missingSlots)],
                    'documents.*' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
                ]);
                $submittedSlots = array_map('intval', array_keys($validated['documents']));
                sort($submittedSlots);
                $expectedSlots = $missingSlots;
                sort($expectedSlots);
                if ($submittedSlots !== $expectedSlots) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'documents' => ['Select one file for each remaining requirement template.'],
                    ]);
                }

                foreach ($missingSlots as $slot) {
                    $file = $validated['documents'][$slot];
                    $path = $file->store("applications/{$application->id}/additional-requirements", 'local');
                    if (!is_string($path)) {
                        throw new RuntimeException('Unable to store an uploaded application requirement.');
                    }
                    $storedPaths[] = $path;

                    ApplicationAdditionalRequirement::create([
                        'application_id' => $application->id,
                        'additional_requirement_id' => $requirement->id,
                        'file_number' => $slot,
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
                    ['description' => $requirement->name.' submitted ('.count($missingSlots).' files)'],
                );
            });
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('local')->delete($storedPaths);
            }
            throw $exception;
        }

        return redirect()->route('applicant.requirements')
            ->with('success', "{$additionalRequirement->name} files uploaded successfully.");
    }

    public function viewSubmission(
        Application $application,
        AdditionalRequirement $additionalRequirement,
        ApplicationAdditionalRequirement $submission,
    ) {
        $user = Auth::user();
        abort_unless($user->role === 'admin' || $application->user_id === $user->id, 403);
        abort_unless(
            $user->role === 'admin' || $additionalRequirement->isAvailableTo($application),
            403,
        );
        abort_unless(
            $submission->application_id === $application->id
                && $submission->additional_requirement_id === $additionalRequirement->id,
            404,
        );
        abort_unless(Storage::disk('local')->exists($submission->file_path), 404);

        return response()->file(Storage::disk('local')->path($submission->file_path), [
            'Content-Disposition' => 'inline; filename="'.str_replace(['"', "\r", "\n"], '', basename($submission->original_name)).'"',
        ]);
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
