<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\ApplicationsExport;
use App\Models\AuditAction;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    /**
     * Export applications to Excel with filters
     */
    public function masterList(Request $request, AuditLogger $auditLogger)
    {
        $filters = $request->only(['status', 'barangay', 'spes_status', 'search']);

        $response = Excel::download(
            new ApplicationsExport($filters),
            'spes-applications-' . now()->format('Y-m-d-His') . '.xlsx'
        );

        $auditLogger->record(
            AuditAction::APPLICANT_REPORT_GENERATED,
            'Application Report',
            Auth::user(),
            null,
            null,
            ['description' => 'Application report exported'],
        );

        return $response;
    }
}
