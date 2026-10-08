<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdditionalRequirement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class AdditionalRequirementController extends Controller
{
    public function index(): View
    {
        $requirements = AdditionalRequirement::with(['templates'])->withCount('submissions')
            ->orderBy('name')
            ->get();

        return view('admin.additional-requirements.index', compact('requirements'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateRequirement($request);
        $template = $validated['template'] ?? null;
        $templates = $validated['templates'] ?? [];
        unset($validated['template'], $validated['templates'], $validated['remove_template']);

        if ($template instanceof UploadedFile) {
            $validated = array_merge($validated, $this->storeTemplate($template));
        }

        $requirement = AdditionalRequirement::create($validated);
        $this->storeRequirementTemplates($requirement, $templates);

        return redirect()->route('admin.additional-requirements.index')
            ->with('success', 'Additional requirement added to the applicant checklist.');
    }

    public function update(Request $request, AdditionalRequirement $additionalRequirement): RedirectResponse
    {
        $validated = $this->validateRequirement($request, $additionalRequirement);
        $template = $validated['template'] ?? null;
        $templates = $validated['templates'] ?? [];
        $removeTemplateIds = $validated['remove_template_files'] ?? [];
        $removeTemplate = (bool) ($validated['remove_template'] ?? false);
        unset($validated['template'], $validated['templates'], $validated['remove_template'], $validated['remove_template_files']);

        $templatesToRemove = $additionalRequirement->templates()
            ->whereIn('id', $removeTemplateIds)
            ->get();
        if ($templatesToRemove->count() !== count($removeTemplateIds)) {
            throw ValidationException::withMessages([
                'remove_template_files' => ['One or more selected files do not belong to this requirement.'],
            ]);
        }

        $previousTemplatePath = $additionalRequirement->template_path;
        if ($template instanceof UploadedFile) {
            $validated = array_merge($validated, $this->storeTemplate($template));
        } elseif ($removeTemplate) {
            $validated['template_path'] = null;
            $validated['template_original_name'] = null;
        }

        $additionalRequirement->update($validated);

        foreach ($templatesToRemove as $requirementTemplate) {
            Storage::disk('local')->delete($requirementTemplate->file_path);
            $requirementTemplate->delete();
        }
        $this->storeRequirementTemplates($additionalRequirement, $templates);

        if ($previousTemplatePath && $previousTemplatePath !== $additionalRequirement->template_path) {
            Storage::disk('local')->delete($previousTemplatePath);
        }

        return redirect()->route('admin.additional-requirements.index')
            ->with('success', 'Additional requirement updated.');
    }

    public function destroy(AdditionalRequirement $additionalRequirement): RedirectResponse
    {
        if ($additionalRequirement->template_path) {
            Storage::disk('local')->delete($additionalRequirement->template_path);
        }

        foreach ($additionalRequirement->templates as $template) {
            Storage::disk('local')->delete($template->file_path);
        }

        foreach ($additionalRequirement->submissions()->get() as $submission) {
            Storage::disk('local')->delete($submission->file_path);
            $submission->delete();
        }

        $additionalRequirement->delete();

        return redirect()->route('admin.additional-requirements.index')
            ->with('success', 'Additional requirement and its uploaded files deleted.');
    }

    private function validateRequirement(Request $request, ?AdditionalRequirement $requirement = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:additional_requirements,name'.($requirement ? ','.$requirement->id : '')],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_required' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'audience' => ['required', 'in:all_applicants,approved_applicants'],
            'due_at' => ['nullable', 'date'],
            'template' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'templates' => ['nullable', 'array', 'max:20'],
            'templates.*' => ['file', 'mimes:pdf,doc,docx', 'max:10240'],
            'remove_template' => ['nullable', 'boolean'],
            'remove_template_files' => ['nullable', 'array'],
            'remove_template_files.*' => ['required', 'integer'],
        ]);

        return $validated;
    }

    private function storeTemplate(UploadedFile $template): array
    {
        $path = $template->storeAs(
            'requirements/templates',
            Str::uuid().'.'.$template->getClientOriginalExtension(),
            'local',
        );

        if (! is_string($path)) {
            throw new RuntimeException('Unable to store the downloadable requirement template.');
        }

        return [
            'template_path' => $path,
            'template_original_name' => $template->getClientOriginalName(),
        ];
    }

    /**
     * @param array<int, UploadedFile> $templates
     */
    private function storeRequirementTemplates(AdditionalRequirement $requirement, array $templates): void
    {
        foreach ($templates as $template) {
            if (! $template instanceof UploadedFile) {
                continue;
            }

            $path = $template->storeAs(
                'requirements/templates',
                Str::uuid().'.'.$template->getClientOriginalExtension(),
                'local',
            );

            if (! is_string($path)) {
                throw new RuntimeException('Unable to store an additional requirement file.');
            }

            $originalName = basename(str_replace('\\', '/', $template->getClientOriginalName()));
            $requirement->templates()->create([
                'file_path' => $path,
                'original_name' => $originalName,
            ]);
        }
    }
}
