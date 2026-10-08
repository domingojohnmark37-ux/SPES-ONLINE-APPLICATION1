<?php

namespace App\Services;

use App\Models\Application;
use App\Models\SystemSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use ZipArchive;

class FinalListExportService
{
    public function createArchive(
        string $name,
        int $batchYear,
        Collection $applications,
        array $filters,
        array $placementOverrides = [],
    ): string {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP ZIP extension is required to create final-list packages.');
        }

        $archivePath = tempnam(sys_get_temp_dir(), 'spes-final-list-');
        if ($archivePath === false) {
            throw new RuntimeException('Unable to allocate temporary storage for the final-list package.');
        }

        $zip = new ZipArchive;
        $zipOpened = false;
        try {
            if ($zip->open($archivePath, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to create the final-list archive.');
            }
            $zipOpened = true;

            $this->addPlacementReport($zip, $applications, $placementOverrides);

            foreach ($applications->values() as $index => $application) {
                $folder = sprintf(
                    'Applicant Documents/%03d - %s',
                    $index + 1,
                    $this->safeName($application->full_name ?: 'Applicant '.$application->id),
                );

                $this->addPdf(
                    $zip,
                    "{$folder}/Applicant Details.pdf",
                    'admin.exports.applicant-details',
                    [
                        'application' => $application,
                        'fields' => $this->applicantFieldRows($application),
                    ],
                );

                foreach ([
                    'resume' => 'Birth Certificate',
                    'certificate_enrollment' => 'Certificate of Enrollment',
                    'certificate_grade' => 'Certificate of Grades',
                    'application_letter' => 'Application Letter',
                    'indigency' => 'Certificate of Indigency',
                ] as $attribute => $label) {
                    if (filled($application->{$attribute})) {
                        $this->addStoredDocument(
                            $zip,
                            $folder,
                            $label,
                            (string) $application->{$attribute},
                            'public',
                        );
                    }
                }

                foreach ($application->additionalRequirementSubmissions as $requirementIndex => $submission) {
                    $requirementName = $submission->requirement?->name ?? 'Additional Requirement';
                    $this->addStoredDocument(
                        $zip,
                        $folder.'/Additional Requirements/'.sprintf(
                            '%03d - %s',
                            $requirementIndex + 1,
                            $this->safeName($requirementName),
                        ),
                        $requirementName,
                        $submission->file_path,
                        'local',
                        $submission->original_name,
                    );
                }
            }

            if (! $zip->addFromString(
                'README.txt',
                "SPES {$name}\n"
                ."Contains an Excel placement report and one folder per applicant.\n"
                ."Each applicant folder contains an applicant-details PDF and the original submitted documents.\n"
                ."Applicant information and submitted documents are confidential. Store and share this package securely.\n",
            )) {
                throw new RuntimeException('Unable to add the package information file.');
            }

            if (! $zip->close()) {
                throw new RuntimeException('Unable to finalize the final-list archive.');
            }
            $zipOpened = false;

            return $archivePath;
        } catch (\Throwable $exception) {
            if ($zipOpened) {
                $zip->close();
            }
            @unlink($archivePath);
            throw $exception;
        }
    }

    public function storeArchive(string $temporaryPath, string $name): string
    {
        $relativePath = 'final-lists/'.Str::uuid().'-'.$this->safeName($name).'.zip';
        $stream = fopen($temporaryPath, 'rb');

        if ($stream === false) {
            throw new RuntimeException('Unable to read the generated final-list archive.');
        }

        try {
            if (! Storage::disk('local')->put($relativePath, $stream)) {
                throw new RuntimeException('Unable to save the generated final-list archive.');
            }
        } finally {
            fclose($stream);
        }

        return $relativePath;
    }

    private function addPdf(ZipArchive $zip, string $archiveName, string $view, array $data): void
    {
        $contents = Pdf::loadView($view, $data)->setPaper('a4')->output();
        if (! $zip->addFromString($archiveName, $contents)) {
            throw new RuntimeException("Unable to add PDF [{$archiveName}] to the final-list archive.");
        }
    }

    private function addPlacementReport(
        ZipArchive $zip,
        Collection $applications,
        array $placementOverrides,
    ): void {
        $templatePath = base_path('resources/exports/placement-report-template.xlsx');
        if (! is_file($templatePath)) {
            throw new RuntimeException('The placement report Excel template is missing.');
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();
        $settings = SystemSetting::current();
        $firstDataRow = 17;
        $lastDataRow = 117;
        $availableRows = $lastDataRow - $firstDataRow + 1;
        $extraRows = max(0, $applications->count() - $availableRows);

        if ($extraRows > 0) {
            $sheet->insertNewRowBefore(118, $extraRows);
            $rowHeight = $sheet->getRowDimension($lastDataRow)->getRowHeight();
            for ($row = 118; $row < 118 + $extraRows; $row++) {
                $sheet->duplicateStyle($sheet->getStyle("A{$lastDataRow}:R{$lastDataRow}"), "A{$row}:R{$row}");
                if ($rowHeight > 0) {
                    $sheet->getRowDimension($row)->setRowHeight($rowHeight);
                }
            }
        }

        foreach ($applications->values() as $index => $application) {
            $row = $firstDataRow + $index;
            $overrides = $placementOverrides[$application->id] ?? [];
            foreach (['wage_rate', 'company_share'] as $amountField) {
                if (isset($overrides[$amountField]) && $overrides[$amountField] !== '') {
                    $overrides[$amountField] = (int) $overrides[$amountField];
                }
            }
            $placement = array_replace(
                $this->placementReportFields($application, $settings),
                $overrides,
            );
            $values = [
                'A' => $index + 1,
                'B' => $placement['last_name'],
                'C' => $placement['first_name'],
                'D' => $placement['middle_initial'],
                'E' => $placement['sex'],
                'F' => $placement['age'],
                'G' => $placement['address'],
                'H' => $placement['parent_status'],
                'I' => $placement['applicant_type'],
                'J' => $placement['education_level'],
                'K' => $placement['grade_year_level'],
                'L' => $placement['spes_type'],
                'M' => $placement['nature_of_work'],
                'N' => $placement['place_of_assignment'],
                'O' => $placement['employment_start'],
                'P' => $placement['employment_end'],
                'Q' => $placement['wage_rate'],
                'R' => $placement['company_share'],
            ];

            foreach ($values as $column => $value) {
                $this->setReportCell($sheet, "{$column}{$row}", $value);
            }
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'spes-placement-report-');
        if ($temporaryPath === false) {
            throw new RuntimeException('Unable to allocate temporary storage for the placement report.');
        }

        try {
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save($temporaryPath);
            $contents = file_get_contents($temporaryPath);
            if ($contents === false) {
                throw new RuntimeException('Unable to read the generated placement report.');
            }
            if (! $zip->addFromString('Final List.xlsx', $contents)) {
                throw new RuntimeException('Unable to add the Excel placement report to the final-list archive.');
            }
        } finally {
            @unlink($temporaryPath);
        }
    }

    public function placementReportFields(Application $application, ?SystemSetting $settings = null): array
    {
        $middleName = trim((string) $application->middle_name);
        $middleLetters = preg_replace('/[^\p{L}]/u', '', $middleName) ?? '';
        $middleInitial = in_array(Str::lower($middleName), ['n/a', 'na'], true)
            ? ''
            : Str::upper(Str::substr($middleLetters, 0, 1));
        $education = trim((string) $application->education);
        $educationLower = Str::lower($education);
        $profile = $application->user?->profile;
        $settings ??= SystemSetting::current();

        $wageRate = trim((string) $application->f3_wage_rate);

        return [
            'last_name' => $application->surname,
            'first_name' => $application->first_name,
            'middle_initial' => $middleInitial,
            'sex' => $application->sex,
            'age' => $application->age,
            'address' => $profile?->permanent_address ?: ($profile?->present_address ?: $application->barangay),
            'parent_status' => $application->parent_status,
            'applicant_type' => $education === ''
                ? ''
                : (Str::contains($educationLower, 'out-of-school youth') ? 'OSY' : 'Student'),
            'education_level' => match (true) {
                Str::contains($educationLower, 'out-of-school youth') => '',
                Str::contains($educationLower, 'als') => 'ALS',
                Str::contains($educationLower, 'senior high') => 'SHS',
                Str::contains($educationLower, 'high school') => 'JHS',
                Str::contains($educationLower, 'college') => 'College',
                Str::contains($educationLower, ['vocational', 'tech-voc']) => 'Tech-voc',
                default => '',
            },
            'grade_year_level' => $application->grade_year_level,
            'spes_type' => match (Str::lower((string) $application->spes_status)) {
                'new' => 'New',
                'baby' => 'Baby',
                default => '',
            },
            'nature_of_work' => $application->f3_position,
            'place_of_assignment' => $application->f3_employer_address,
            'employment_start' => $settings->application_start_date?->format('M d, Y'),
            'employment_end' => $settings->application_end_date?->format('M d, Y'),
            'wage_rate' => ctype_digit($wageRate) && (int) $wageRate > 0 ? (int) $wageRate : '',
            'company_share' => '',
        ];
    }

    private function setReportCell(Worksheet $sheet, string $coordinate, mixed $value): void
    {
        if ($value === null || $value === '') {
            $sheet->setCellValue($coordinate, null);
        } elseif (is_int($value) || is_float($value)) {
            $sheet->setCellValue($coordinate, $value);
        } else {
            $sheet->setCellValueExplicit($coordinate, (string) $value, DataType::TYPE_STRING);
        }
    }

    private function addStoredDocument(
        ZipArchive $zip,
        string $folder,
        string $label,
        string $path,
        string $disk,
        ?string $originalName = null,
    ): void {
        $storage = Storage::disk($disk);
        if (! $storage->exists($path)) {
            throw new RuntimeException("A submitted document is missing from storage: [{$path}].");
        }

        $extension = strtolower(pathinfo($originalName ?: $path, PATHINFO_EXTENSION));
        if (preg_match('/^[a-z0-9]{1,10}$/', $extension) !== 1) {
            $extension = '';
        }
        $fileName = $this->safeName($label).($extension !== '' ? '.'.$extension : '');
        $contents = $storage->get($path);
        if (! $zip->addFromString("{$folder}/{$fileName}", $contents)) {
            throw new RuntimeException("Unable to add submitted document [{$fileName}] to the final-list archive.");
        }
    }

    public function applicantFieldRows(Application $application): array
    {
        $excluded = [
            'id',
            'user_id',
            'resume',
            'certificate_enrollment',
            'certificate_grade',
            'application_letter',
            'indigency',
            'document_original_names',
            'f3_beneficiary_signature',
            'admin_comment',
            'created_at',
            'updated_at',
        ];

        $fields = [];
        foreach ($application->getFillable() as $attribute) {
            if (in_array($attribute, $excluded, true)) {
                continue;
            }

            $value = $application->getAttribute($attribute);
            if ($value === null || $value === '') {
                continue;
            }
            if (is_array($value)) {
                $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } elseif ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d');
            } elseif (is_bool($value)) {
                $value = $value ? 'Yes' : 'No';
            }

            $fields[] = [
                'label' => Str::headline($attribute),
                'value' => (string) $value,
            ];
        }

        if (filled($application->user?->email)) {
            $fields[] = ['label' => 'Applicant Account Email', 'value' => $application->user->email];
        }

        if (filled($application->admin_comment)) {
            $fields[] = ['label' => 'Admin Feedback', 'value' => $application->admin_comment];
        }

        return $fields;
    }

    private function safeName(string $value): string
    {
        $value = Str::ascii($value);
        $value = preg_replace('/[^A-Za-z0-9_-]+/', '-', $value) ?? '';

        return trim($value, '-_') ?: 'document';
    }
}
