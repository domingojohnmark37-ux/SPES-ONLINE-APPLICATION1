<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemographicReportArchive extends Model
{
    protected $fillable = [
        'start_date',
        'end_date',
        'program_year',
        'version',
        'total_received',
        'outside_date_range',
        'not_approved',
        'duplicates_removed',
        'total_valid',
        'requested_by',
        'status',
        'pdf_path',
        'report_data',
        'generated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'report_data' => 'array',
    ];

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
