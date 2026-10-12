<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DemographicReportArchive;
use App\Services\PesoDemographicReportService;
use App\Services\StatisticsReportService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatisticsReportController extends Controller
{
    public function __invoke(
        Request $request,
        StatisticsReportService $statistics,
    ): View {
        $periodOptions = $statistics->periodOptions();
        $statusOptions = $statistics->statusOptions();
        $barangayOptions = $statistics->barangayOptions();

        $filters = $request->validate([
            'period' => ['nullable', Rule::in(array_keys($periodOptions))],
            'date_range' => ['nullable', Rule::in(['all', 'last30', 'last90', 'last12', 'custom'])],
            'date_from' => ['nullable', 'required_if:date_range,custom', 'date'],
            'date_to' => ['nullable', 'required_if:date_range,custom', 'date', 'after_or_equal:date_from'],
            'barangay' => ['nullable', Rule::in($barangayOptions)],
            'status' => ['nullable', Rule::in(array_keys($statusOptions))],
        ]);

        $filters = array_merge([
            'period' => 'all',
            'date_range' => 'last12',
            'date_from' => null,
            'date_to' => null,
            'barangay' => null,
            'status' => null,
        ], $filters);

        return view('admin.statistics-report', array_merge(
            $statistics->report($filters),
            compact('filters', 'periodOptions', 'statusOptions', 'barangayOptions'),
            ['archivedReports' => DemographicReportArchive::query()->latest()->paginate(10)],
        ));
    }

    public function store(Request $request, PesoDemographicReportService $reports): RedirectResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'program_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'requested_by' => ['nullable', 'string', 'max:160'],
        ]);
        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->endOfDay();

        $report = $reports->generate(
            $start,
            $end,
            (int) $validated['program_year'],
            (int) $request->user()->id,
            filled($validated['requested_by'] ?? null) ? trim($validated['requested_by']) : null,
        );

        return redirect()->route('admin.statistics-report')
            ->with('success', "Demographic report {$report->start_date->format('Y-m-d')} to {$report->end_date->format('Y-m-d')} v{$report->version} was generated and archived.")
            ->with('generated_demographic_report_id', $report->id);
    }

    public function download(DemographicReportArchive $report, string $format): StreamedResponse
    {
        abort_unless($format === 'pdf', 404);
        $path = $report->pdf_path;
        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download(
            $path,
            basename($path),
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function destroy(DemographicReportArchive $report): RedirectResponse
    {
        if (is_string($report->pdf_path)) {
            $disk = Storage::disk('local');
            if ($disk->exists($report->pdf_path) && ! $disk->delete($report->pdf_path)) {
                abort(500, 'The archived PDF could not be deleted. The report record was kept.');
            }
        }

        $report->delete();

        return redirect()->route('admin.statistics-report')
            ->with('success', 'The demographic report and its archived PDF were deleted.');
    }
}
