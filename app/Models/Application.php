<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class Application extends Model
{
    use HasFactory;

    public const GRADE_YEAR_LEVELS = [
        'Grade 7',
        'Grade 8',
        'Grade 9',
        'Grade 10',
        'Grade 11',
        'Grade 12',
        '1st year',
        '2nd year',
        '3rd year',
        '4th year',
        '5th year',
    ];

    protected $fillable = [
        'ref_id', 'user_id',
        // Personal
        'full_name', 'surname', 'first_name', 'middle_name', 'sex', 'birthday', 'age', 'barangay', 'civil_status',
        'parent_status', 'education', 'grade_year_level', 'spes_status', 'mother_name',
        'father_guardian_name', 'contact_no', 'messenger', 'facebook',
        'mother_occupation', 'father_occupation', 'mother_contact_no', 'father_contact_no',
        // Documents
        'birth_certificate', 'certificate_enrollment', 'certificate_grade', 'application_letter', 'indigency',
        'document_original_names',
        // Status
        'status', 'admin_comment',
        // Form 3
        'f3_beneficiary_name', 'f3_beneficiary_age', 'f3_beneficiary_years', 'f3_beneficiary_address',
        'f3_signed_day', 'f3_signed_month', 'f3_signed_year', 'f3_signed_city',
        'f3_parent_guardian_name', 'f3_beneficiary_signature', 'f3_witnessed_by',
        'f3_witnessed_date', 'f3_noted_by', 'f3_noted_date',
        'f3_employer_name', 'f3_employer_address', 'f3_position',
        'f3_start_date', 'f3_end_date', 'f3_work_days', 'f3_wage_rate',
        'f3_work_schedule', 'f3_supervisor_name', 'f3_supervisor_contact', 'f3_duties',
        // Form 4
        'f4_employer_name', 'f4_employer_address', 'f4_position',
        'f4_contract_start', 'f4_contract_end', 'f4_contract_days',
        'f4_wage_percent', 'f4_signed_date', 'f4_signed_place',
    ];

    protected $casts = [
        'birthday' => 'date',
        'f3_start_date' => 'date',
        'f3_end_date' => 'date',
        'f4_contract_start' => 'date',
        'f4_contract_end' => 'date',
        'f4_signed_date' => 'date',
        'document_original_names' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function ($application) {
            if (empty($application->ref_id)) {
                $application->ref_id = 'SPES-'.strtoupper(Str::random(8));
            }
        });

        static::deleting(function (Application $application): void {
            $paths = [
                $application->birth_certificate,
                $application->certificate_enrollment,
                $application->certificate_grade,
                $application->application_letter,
                $application->indigency,
                ...$application->additionalRequirementSubmissions()->pluck('file_path')->all(),
            ];

            DB::afterCommit(fn () => self::deleteLocalFiles($paths, $application->id));
        });
    }

    public static function deleteLocalFiles(array $paths, ?int $applicationId = null): void
    {
        foreach (array_unique(array_filter($paths, fn ($path): bool => is_string($path) && $path !== '')) as $path) {
            try {
                if (! Storage::disk('local')->delete($path)) {
                    Log::warning('Failed to delete applicant document from local storage.', [
                        'application_id' => $applicationId,
                        'path' => $path,
                    ]);
                }
            } catch (Throwable $exception) {
                Log::warning('Failed to delete applicant document from local storage.', [
                    'application_id' => $applicationId,
                    'path' => $path,
                    'exception' => $exception,
                ]);
            }
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function additionalRequirementSubmissions(): HasMany
    {
        return $this->hasMany(ApplicationAdditionalRequirement::class);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'approved' => 'badge-approved',
            'denied' => 'badge-denied',
            default => 'badge-pending',
        };
    }
}
