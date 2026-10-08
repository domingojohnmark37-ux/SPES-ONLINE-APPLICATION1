<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StatisticsReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StatisticsReportController extends Controller
{
    public function __invoke(Request $request, StatisticsReportService $statistics): View
    {
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
        ));
    }
}
