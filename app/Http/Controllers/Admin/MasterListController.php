<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMasterListRequest;
use App\Models\Application;
use App\Models\AuditAction;
use App\Models\MasterList;
use App\Models\SystemSetting;
use App\Services\AuditLogger;
use App\Services\FinalListExportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MasterListController extends Controller
{
    /**
     * Display the master list filtering and results
     */
    public function index(Request $request, FinalListExportService $exportService)
    {
        $batchYear = SystemSetting::current()->application_start_date?->year ?? now()->year;
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'barangay' => $request->input('barangay'),
            'spes_status' => $request->input('spes_status'),
            'sort' => $request->input('sort', 'name_asc'),
        ];
        $applications = $this->filteredApplications($filters)
            ->with('user.profile')
            ->paginate(25)
            ->withQueryString();
        $settings = SystemSetting::current();
        $applications->getCollection()->each(function (Application $application) use ($exportService, $settings): void {
            $application->setAttribute(
                'placement_report_fields',
                $exportService->placementReportFields($application, $settings),
            );
        });

        // Get unique barangays for filter dropdown
        $barangays = Application::distinct()
            ->orderBy('barangay')
            ->pluck('barangay')
            ->filter();
        $savedLists = MasterList::query()->latest()->take(10)->get();

        return view('admin.master-list', compact('applications', 'barangays', 'batchYear', 'savedLists'));
    }

    /**
     * Save the finalized Master List
     */
    public function store(
        StoreMasterListRequest $request,
        AuditLogger $auditLogger,
        FinalListExportService $exportService,
    ) {
        $validated = $request->validated();
        $batchYear = SystemSetting::current()->application_start_date?->year ?? now()->year;
        $name = filled($validated['name'] ?? null)
            ? $validated['name']
            : "Final List of Batch {$batchYear}";

        $filters = [
            'search' => trim((string) ($validated['search'] ?? '')),
            'barangay' => $request->input('barangay'),
            'spes_status' => $request->input('spes_status'),
            'sort' => $request->input('sort', 'name_asc'),
        ];
        $applications = $this->filteredApplications($filters)->with([
            'user',
            'additionalRequirementSubmissions.requirement',
        ])->get();

        $temporaryArchive = $exportService->createArchive(
            $name,
            $batchYear,
            $applications,
            $filters,
            $validated['placement'] ?? [],
        );
        try {
            $archivePath = $exportService->storeArchive($temporaryArchive, $name);
        } finally {
            @unlink($temporaryArchive);
        }

        try {
            $masterList = DB::transaction(function () use ($filters, $auditLogger, $name, $batchYear, $archivePath, $applications): MasterList {
                $masterList = MasterList::create([
                    'name' => $name,
                    'filters_json' => $filters,
                    'archive_path' => $archivePath,
                    'generated_by' => Auth::id(),
                ]);

                $masterList->applications()->attach($applications->modelKeys());
                $auditLogger->record(
                    AuditAction::APPLICANT_REPORT_GENERATED,
                    'Final List',
                    Auth::user(),
                    null,
                    null,
                    ['description' => 'Final list "'.$masterList->name.'" generated as an archive for batch '.$batchYear],
                );

                return $masterList;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($archivePath);
            throw $exception;
        }

        return redirect()->route('admin.masterlist.index')
            ->with('success', "Final list '{$masterList->name}' saved as an Excel placement report with applicant details and submitted documents.")
            ->with('generated_final_list_id', $masterList->id);
    }

    public function download(MasterList $masterList, AuditLogger $auditLogger): StreamedResponse
    {
        abort_unless($masterList->archive_path && Storage::disk('local')->exists($masterList->archive_path), 404);

        $auditLogger->record(
            AuditAction::APPLICANT_REPORT_GENERATED,
            'Final List',
            Auth::user(),
            null,
            null,
            ['description' => 'Final list archive "'.$masterList->name.'" downloaded'],
        );

        return Storage::disk('local')->download(
            $masterList->archive_path,
            Str::slug($masterList->name).'.zip',
            ['Content-Type' => 'application/zip'],
        );
    }

    private function filteredApplications(array $filters): Builder
    {
        $query = Application::query()->where('status', 'approved');

        if (filled($filters['search'] ?? null)) {
            $query->where('full_name', 'like', '%'.trim($filters['search']).'%');
        }
        if (filled($filters['barangay'] ?? null)) {
            $query->where('barangay', $filters['barangay']);
        }
        if (filled($filters['spes_status'] ?? null)) {
            $query->where('spes_status', $filters['spes_status']);
        }

        return $query->orderBy(
            'full_name',
            ($filters['sort'] ?? 'name_asc') === 'name_desc' ? 'desc' : 'asc',
        );
    }
}
