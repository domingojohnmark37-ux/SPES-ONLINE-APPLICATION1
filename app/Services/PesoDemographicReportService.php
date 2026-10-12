<?php

namespace App\Services;

use App\Models\Application;
use App\Models\DemographicReportArchive;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PesoDemographicReportService
{
    private const AGE_GROUPS = ['15–17', '18–20', '21–24', '25–30'];

    private const SOURCE = 'SPES application system (approved applications)';

    private const ROUNDING_NOTE = '*Total may differ slightly from 100% because of rounding.';

    private const FONT = 'Arial';

    private const BLACK = '000000';

    private const ARCHIVE_DIR = 'SPES Demographic Reports Archive';

    public function generate(
        Carbon $startDate,
        Carbon $endDate,
        int $programYear,
        int $adminId,
        ?string $requestedBy,
    ): DemographicReportArchive {
        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->endOfDay();
        $data = $this->buildReportData($start, $end, $programYear);
        $data['requested_by'] = $requestedBy;
        $report = DB::transaction(function () use ($start, $end, $programYear, $adminId, $requestedBy, $data): DemographicReportArchive {
            $version = (int) DemographicReportArchive::query()
                ->whereDate('start_date', $start->toDateString())
                ->whereDate('end_date', $end->toDateString())
                ->lockForUpdate()
                ->max('version') + 1;

            return DemographicReportArchive::create([
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'program_year' => $programYear,
                'version' => $version,
                'total_received' => $data['total_received'],
                'outside_date_range' => $data['outside_date_range'],
                'not_approved' => $data['not_approved'],
                'duplicates_removed' => $data['duplicates_removed'],
                'total_valid' => $data['total_valid'],
                'requested_by' => $requestedBy,
                'status' => 'Draft',
                'report_data' => $data,
                'generated_by' => $adminId,
            ]);
        });

        $baseName = sprintf(
            'SPES_Demographics_%s_to_%s_v%d',
            $start->format('Y-m-d'),
            $end->format('Y-m-d'),
            $report->version,
        );
        $pdfPath = self::ARCHIVE_DIR."/{$baseName}.pdf";

        try {
            Storage::disk('local')->makeDirectory(self::ARCHIVE_DIR);
            $this->writePdfDocument($data, $baseName, $pdfPath);
            $report->update(['pdf_path' => $pdfPath]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($pdfPath);
            $report->delete();
            throw $exception;
        }

        return $report->fresh();
    }

    private function buildReportData(Carbon $start, Carbon $end, int $programYear): array
    {
        $allApplications = Application::query();
        $totalReceived = (clone $allApplications)->count();
        $inRange = (clone $allApplications)
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get();
        $outsideDateRange = $totalReceived - $inRange->count();
        $approved = $inRange->where('status', 'approved')->values();
        $notApproved = $inRange->count() - $approved->count();
        [$uniqueApproved, $duplicatesRemoved] = $this->uniqueApplicants($approved);
        $previousPeriodDays = $start->diffInDays($end) + 1;
        $previousPeriodEnd = $start->copy()->subSecond();
        $previousPeriodStart = $start->copy()->subDays($previousPeriodDays);
        $previousApproved = Application::query()
            ->whereBetween('created_at', [$previousPeriodStart, $previousPeriodEnd])
            ->where('status', 'approved')
            ->orderBy('created_at')
            ->get();
        [$previousValidApplicants] = $this->uniqueApplicants($previousApproved);

        $totalValid = $uniqueApproved->count();
        $ageValues = $uniqueApproved->map(fn (Application $application) => $this->ageLabel($application->age));
        $sexValues = $uniqueApproved->map(fn (Application $application) => $application->sex);
        $civilStatusValues = $uniqueApproved->map(fn (Application $application) => $application->civil_status);
        $barangayValues = $uniqueApproved->map(fn (Application $application) => $application->barangay);
        $educationValues = $uniqueApproved->map(fn (Application $application) => $application->education);
        $yearLevelValues = $uniqueApproved->map(fn (Application $application) => $this->yearLevel($application));
        $ageCounts = array_fill_keys(self::AGE_GROUPS, 0);
        foreach ($ageValues as $label) {
            if (array_key_exists($label, $ageCounts)) {
                $ageCounts[$label]++;
            }
        }
        $ageItems = array_map(fn ($label) => ['label' => $label, 'count' => $ageCounts[$label]], self::AGE_GROUPS);
        foreach (['Outside age groups', 'Not provided'] as $label) {
            $count = $ageValues->filter(fn ($value) => $value === $label)->count();
            if ($count > 0) {
                $ageItems[] = ['label' => $label, 'count' => $count];
            }
        }

        $demographics = [
            'age' => $this->distribution($ageItems, $totalValid),
            'sex' => $this->distributionFromValues($sexValues, $totalValid),
            'civil_status' => $this->distributionFromValues($civilStatusValues, $totalValid),
            'barangay' => $this->barangayDistribution($barangayValues, $totalValid),
            'education' => $this->distributionFromValues($educationValues, $totalValid),
            'year_level' => $this->distributionFromValues($yearLevelValues, $totalValid),
        ];
        foreach ($demographics as $label => $distribution) {
            if (array_sum(array_column($distribution['items'], 'count')) !== $totalValid) {
                throw new RuntimeException("The {$label} demographic table does not match the total valid applicant count.");
            }
        }
        $missing = [
            'Age group' => $ageValues->filter(fn ($value) => $value === 'Not provided')->count(),
            'Sex' => $sexValues->filter(fn ($value) => blank($value))->count(),
            'Civil status' => $civilStatusValues->filter(fn ($value) => blank($value))->count(),
            'Barangay / residence' => $barangayValues->filter(fn ($value) => blank($value))->count(),
            'Educational level' => $educationValues->filter(fn ($value) => blank($value))->count(),
            'Year level' => $yearLevelValues->filter(fn ($value) => blank($value))->count(),
        ];
        $missing = array_filter($missing, fn (int $count) => $count > 0);
        $comparison = [
            'start_date' => $previousPeriodStart->toDateString(),
            'end_date' => $previousPeriodEnd->toDateString(),
            'total_valid' => $previousValidApplicants->count(),
            'difference' => $totalValid - $previousValidApplicants->count(),
            'percentage_change' => $previousValidApplicants->count() > 0
                ? round(($totalValid - $previousValidApplicants->count()) / $previousValidApplicants->count() * 100, 1)
                : null,
        ];

        return [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'program_year' => $programYear,
            'generated_at' => now()->format('F j, Y g:i A'),
            'office' => 'Public Employment Service Office, Lal-lo, Cagayan',
            'program' => 'Special Program for Employment of Students (SPES)',
            'source' => self::SOURCE,
            'date_basis' => 'Application submission date (applications.created_at)',
            'program_year_note' => 'Program year is the year selected for this report; applications do not store a separate program-year field.',
            'total_received' => $totalReceived,
            'approved_in_period' => $approved->count(),
            'outside_date_range' => $outsideDateRange,
            'not_approved' => $notApproved,
            'duplicates_removed' => $duplicatesRemoved,
            'comparison' => $comparison,
            'other_excluded' => 0,
            'total_valid' => $totalValid,
            'demographics' => $demographics,
            'missing' => $missing,
            'rounding_note' => self::ROUNDING_NOTE,
            'summary' => $this->summary($demographics, $totalValid, $start, $end, $comparison),
            'key_findings' => $this->keyFindings($demographics, $totalValid),
            'recommendations' => $this->recommendations($demographics, $totalValid, $missing),
            'conclusion' => $this->conclusion($demographics, $totalValid, $start, $end),
        ];
    }

    private function uniqueApplicants(Collection $applications): array
    {
        $unique = collect();
        $seen = [];
        $duplicates = 0;

        foreach ($applications as $application) {
            $fullName = filled($application->full_name)
                ? $application->full_name
                : trim(implode(' ', array_filter([$application->first_name, $application->middle_name, $application->surname])));
            $name = Str::lower(preg_replace('/\s+/u', ' ', trim((string) $fullName)) ?? '');
            $birthday = $application->birthday?->format('Y-m-d');
            $identity = $name !== '' && $birthday !== null ? $name.'|'.$birthday : null;
            if ($identity !== null && isset($seen[$identity])) {
                $duplicates++;

                continue;
            }
            if ($identity !== null) {
                $seen[$identity] = true;
            }
            $unique->push($application);
        }

        return [$unique, $duplicates];
    }

    private function ageLabel(mixed $age): string
    {
        if ($age === null || $age === '') {
            return 'Not provided';
        }
        $age = (int) $age;

        return match (true) {
            $age >= 15 && $age <= 17 => '15–17',
            $age >= 18 && $age <= 20 => '18–20',
            $age >= 21 && $age <= 24 => '21–24',
            $age >= 25 && $age <= 30 => '25–30',
            default => 'Outside age groups',
        };
    }

    private function yearLevel(Application $application): ?string
    {
        if (Str::contains(Str::lower((string) $application->education), 'out-of-school youth')) {
            return 'Not applicable (out-of-school)';
        }

        return filled($application->grade_year_level)
            ? trim((string) $application->grade_year_level)
            : null;
    }

    private function distributionFromValues(iterable $values, int $total): array
    {
        $counts = [];
        foreach ($values as $value) {
            $label = filled($value) ? trim((string) $value) : 'Not provided';
            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }
        arsort($counts);

        $items = [];
        foreach ($counts as $label => $count) {
            $items[] = ['label' => $label, 'count' => $count];
        }

        return $this->distribution($items, $total);
    }

    private function barangayDistribution(iterable $values, int $total): array
    {
        $counts = [];
        foreach ($values as $value) {
            $label = filled($value) ? trim((string) $value) : 'Not provided';
            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }
        $notProvided = $counts['Not provided'] ?? 0;
        unset($counts['Not provided']);
        arsort($counts);
        $top = array_slice($counts, 0, 5, true);
        $rest = array_slice($counts, 5, null, true);
        $items = [];
        foreach ($top as $label => $count) {
            $items[] = ['label' => $label, 'count' => $count];
        }
        if ($rest !== []) {
            $items[] = ['label' => 'All other barangays', 'count' => array_sum($rest)];
        }
        if ($notProvided > 0) {
            $items[] = ['label' => 'Not provided', 'count' => $notProvided];
        }

        return $this->distribution($items, $total);
    }

    private function distribution(array $items, int $total): array
    {
        foreach ($items as &$item) {
            $item['percentage'] = $total > 0 ? round($item['count'] / $total * 100, 1) : 0.0;
            $item['interpretation'] = match (true) {
                $item['label'] === 'Not provided' => 'No value recorded for this category.',
                $item['label'] === 'All other barangays' => 'Combined count for barangays outside the top five.',
                $item['label'] === 'Not applicable (out-of-school)' => 'Not applicable to out-of-school youth.',
                default => $item['count'] === 0 ? 'No applicants recorded in this category.' : 'Approved applicants recorded in this category.',
            };
        }
        unset($item);

        return ['items' => $items, 'total' => $total];
    }

    private function summary(array $demographics, int $total, Carbon $start, Carbon $end, array $comparison): string
    {
        if ($total === 0) {
            return sprintf(
                'No valid approved applicants were recorded from %s to %s. No demographic pattern can be interpreted for this period; confirm the selected dates and approval statuses before drawing conclusions.',
                $start->format('F j, Y'),
                $end->format('F j, Y'),
            );
        }

        $age = $this->largest($demographics['age']['items']);
        $sex = $this->largest($demographics['sex']['items']);
        $barangay = $this->largest($demographics['barangay']['items']);
        $education = $this->largest($demographics['education']['items']);

        $summary = sprintf(
            'The analysis includes %s unique approved applicants submitted from %s to %s. The largest recorded age group was %s (%s; %s%%), the most represented sex category was %s (%s; %s%%), the leading listed barangay was %s (%s; %s%%), and the most common education level was %s (%s; %s%%). These figures describe only approved application records and do not establish the level of interest or access among all eligible residents.',
            number_format($total),
            $start->format('F j, Y'),
            $end->format('F j, Y'),
            $age['label'] ?? 'no recorded age group',
            $age['count'] ?? 0,
            number_format($age['percentage'] ?? 0, 1),
            $sex['label'] ?? 'no recorded sex category',
            $sex['count'] ?? 0,
            number_format($sex['percentage'] ?? 0, 1),
            $barangay['label'] ?? 'No barangay',
            $barangay['count'] ?? 0,
            number_format($barangay['percentage'] ?? 0, 1),
            $education['label'] ?? 'no recorded educational-level category',
            $education['count'] ?? 0,
            number_format($education['percentage'] ?? 0, 1),
        );

        $comparisonText = $comparison['total_valid'] > 0
            ? sprintf(
                ' In the equivalent preceding period (%s to %s), %s unique approved applicants were recorded, a change of %s (%s%%).',
                Carbon::parse($comparison['start_date'])->format('F j, Y'),
                Carbon::parse($comparison['end_date'])->format('F j, Y'),
                number_format($comparison['total_valid']),
                ($comparison['difference'] > 0 ? '+' : '').number_format($comparison['difference']),
                number_format($comparison['percentage_change'], 1),
            )
            : ' No unique approved applicants were recorded in the equivalent preceding period, so a percentage change cannot be calculated.';

        return $summary.$comparisonText;
    }

    private function keyFindings(array $demographics, int $total): array
    {
        if ($total === 0) {
            return ['No approved applications matched the selected reporting period.'];
        }

        $findings = [];
        foreach ([
            'age' => 'Age group',
            'sex' => 'Sex category',
            'civil_status' => 'Civil-status category',
            'barangay' => 'Listed barangay',
            'education' => 'Educational level',
            'year_level' => 'Year level',
        ] as $key => $label) {
            $top = $this->largest($demographics[$key]['items']);
            if ($top && $top['count'] > 0) {
                $findings[] = sprintf(
                    '%s with the highest count: %s (%s applicants; %s%%).',
                    $label,
                    $top['label'],
                    number_format($top['count']),
                    number_format($top['percentage'], 1),
                );
            }

            $lowest = $this->leastRepresented($demographics[$key]['items']);
            if ($lowest) {
                $findings[] = sprintf(
                    '%s with the lowest non-zero count: %s (%s applicants; %s%%).',
                    $label,
                    $lowest['label'],
                    number_format($lowest['count']),
                    number_format($lowest['percentage'], 1),
                );
            }
        }

        return $findings;
    }

    private function recommendations(array $demographics, int $total, array $missing): array
    {
        if ($total === 0) {
            return ['Confirm the requested date range and application approval statuses, then regenerate the report before using it for planning.'];
        }

        $recommendations = [
            'Use the recorded age, education, and barangay distribution to plan clear SPES information and application-assistance activities; the counts alone do not explain why representation differs.',
            'Review the listed barangay counts alongside local outreach coverage before deciding where additional information sessions may be useful.',
        ];
        if ($missing !== []) {
            $recommendations[] = 'Review application-form guidance and staff data-entry checks for fields with missing values: '.implode(', ', array_keys($missing)).'.';
        }

        return $recommendations;
    }

    private function conclusion(array $demographics, int $total, Carbon $start, Carbon $end): string
    {
        if ($total === 0) {
            return sprintf('No approved applicants were recorded from %s to %s.', $start->format('F j, Y'), $end->format('F j, Y'));
        }

        $age = $this->largest($demographics['age']['items']);
        $sex = $this->largest($demographics['sex']['items']);

        return sprintf(
            'For %s to %s, the report summarizes %s unique approved applicants. The largest recorded age group was %s and the most represented recorded sex category was %s. These descriptive results can inform outreach planning but should not be interpreted as evidence of cause, eligibility, or program outcomes.',
            $start->format('F j, Y'),
            $end->format('F j, Y'),
            number_format($total),
            $age['label'] ?? 'not available',
            $sex['label'] ?? 'not available',
        );
    }

    private function largest(array $items): ?array
    {
        return collect($items)
            ->reject(fn (array $item) => in_array($item['label'], [
                'Not provided',
                'Outside age groups',
                'All other barangays',
                'Not applicable (out-of-school)',
            ], true))
            ->sortByDesc('count')
            ->first();
    }

    private function leastRepresented(array $items): ?array
    {
        $eligible = collect($items)
            ->reject(fn (array $item) => in_array($item['label'], [
                'Not provided',
                'Outside age groups',
                'Outside application age range',
                'All other barangays',
                'Not applicable (out-of-school)',
            ], true))
            ->filter(fn (array $item) => $item['count'] > 0)
            ->sortBy('count');

        return $eligible->count() > 1 ? $eligible->first() : null;
    }

    private function writePdfDocument(array $data, string $baseName, string $path): void
    {
        $pdf = Pdf::loadView('admin.reports.demographic-pdf', compact('data'))
            ->setPaper('a4', 'portrait');
        $pdf->render();
        $canvas = $pdf->getDomPDF()->getCanvas();
        $canvas->page_text(
            40,
            $canvas->get_height() - 30,
            'PESO Lal-lo, Cagayan | SPES Applicant Demographics | '.$data['start_date'].' to '.$data['end_date'].' | Page {PAGE_NUM} of {PAGE_COUNT}',
            'Arial',
            7,
            [0, 0, 0],
        );
        if (! Storage::disk('local')->put($path, $pdf->output())) {
            throw new RuntimeException("Unable to save PDF report [{$baseName}.pdf].");
        }
    }
}
