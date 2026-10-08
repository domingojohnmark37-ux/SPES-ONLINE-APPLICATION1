<?php

namespace App\Services;

use App\Models\Application;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatisticsReportService
{
    private const STATUS_LABELS = [
        'approved' => 'Approved',
        'pending' => 'Pending Review',
        'denied' => 'Rejected',
    ];

    public function periodOptions(): array
    {
        $options = ['all' => 'All application records'];
        $settings = SystemSetting::query()->first();

        if ($settings?->application_start_date && $settings?->application_end_date) {
            $options['configured'] = 'Configured application window ('
                .$settings->application_start_date->format('M j, Y').' – '
                .$settings->application_end_date->format('M j, Y').')';
        }

        return $options;
    }

    public function statusOptions(): array
    {
        return Application::query()
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->mapWithKeys(fn (string $status) => [$status => $this->statusLabel($status)])
            ->all();
    }

    public function barangayOptions(): array
    {
        return Application::query()
            ->whereNotNull('barangay')
            ->where('barangay', '<>', '')
            ->distinct()
            ->orderBy('barangay')
            ->pluck('barangay')
            ->all();
    }

    public function report(array $filters): array
    {
        $interval = $this->selectedInterval($filters);
        $query = $this->filteredQuery($filters, $interval);
        $countsByStatus = (clone $query)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($row) => [(string) $row->status => (int) $row->total]);
        $total = (int) $countsByStatus->sum();
        $statusCounts = $this->statusCounts($countsByStatus);

        $comparison = $this->previousPeriodComparison($filters, $interval, $statusCounts, $total);
        $chart = $this->activityChart($query, $interval);
        $periods = $this->yearComparison($query);
        $demographics = $this->demographics($query, $total);
        $insights = $this->insights($chart, $demographics, $total);

        return [
            'total' => $total,
            'statusCounts' => $statusCounts,
            'approvalRate' => $this->rate($statusCounts['approved'], $total),
            'rejectionRate' => $this->rate($statusCounts['denied'], $total),
            'pendingRate' => $this->rate($statusCounts['pending'], $total),
            'otherStatusCount' => $statusCounts['other'],
            'comparison' => $comparison,
            'chart' => $chart,
            'periods' => $periods,
            'demographics' => $demographics,
            'insights' => $insights,
            'summary' => $this->summary($total, $statusCounts),
            'conclusion' => $this->conclusion($total, $statusCounts, $insights),
            'hasData' => $total > 0,
            'applicationPeriodAvailable' => isset($this->periodOptions()['configured']),
            'programYearAvailable' => false,
            'municipalityAvailable' => false,
        ];
    }

    private function selectedInterval(array $filters): array
    {
        $start = null;
        $end = null;

        if (($filters['period'] ?? 'all') === 'configured') {
            $settings = SystemSetting::query()->first();
            $start = $settings?->application_start_date?->copy();
            $end = $settings?->application_end_date?->copy();
        }

        $now = now();
        [$rangeStart, $rangeEnd] = match ($filters['date_range'] ?? 'last12') {
            'last30' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'last90' => [$now->copy()->subDays(89)->startOfDay(), $now->copy()->endOfDay()],
            'last12' => [$now->copy()->subMonths(11)->startOfMonth(), $now->copy()->endOfDay()],
            'custom' => [
                isset($filters['date_from']) ? Carbon::parse($filters['date_from'])->startOfDay() : null,
                isset($filters['date_to']) ? Carbon::parse($filters['date_to'])->endOfDay() : null,
            ],
            default => [null, null],
        };

        if ($rangeStart && (!$start || $rangeStart->greaterThan($start))) {
            $start = $rangeStart;
        }
        if ($rangeEnd && (!$end || $rangeEnd->lessThan($end))) {
            $end = $rangeEnd;
        }

        return ['start' => $start, 'end' => $end];
    }

    private function filteredQuery(array $filters, array $interval, bool $includeInterval = true): Builder
    {
        $query = Application::query();

        if (filled($filters['barangay'] ?? null)) {
            $query->where('barangay', $filters['barangay']);
        }
        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }
        if ($includeInterval) {
            if ($interval['start']) {
                $query->where('created_at', '>=', $interval['start']);
            }
            if ($interval['end']) {
                $query->where('created_at', '<=', $interval['end']);
            }
        }

        return $query;
    }

    private function statusCounts(Collection $countsByStatus): array
    {
        $counts = ['approved' => 0, 'pending' => 0, 'denied' => 0, 'other' => 0];

        foreach ($countsByStatus as $status => $count) {
            if (array_key_exists($status, self::STATUS_LABELS)) {
                $counts[$status] = (int) $count;
            } else {
                $counts['other'] += (int) $count;
            }
        }

        return $counts;
    }

    private function previousPeriodComparison(
        array $filters,
        array $interval,
        array $current,
        int $currentTotal,
    ): array {
        if (!$interval['start'] || !$interval['end'] || $interval['end']->lessThan($interval['start'])) {
            return ['available' => false, 'counts' => null, 'changes' => []];
        }

        $duration = $interval['start']->diffInSeconds($interval['end']) + 1;
        $previousEnd = $interval['start']->copy()->subSecond();
        $previousStart = $previousEnd->copy()->subSeconds(max(0, $duration - 1));
        $previousInterval = ['start' => $previousStart, 'end' => $previousEnd];
        $previousRows = $this->filteredQuery($filters, $previousInterval)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($row) => [(string) $row->status => (int) $row->total]);
        $previousTotal = (int) $previousRows->sum();
        $previousCounts = $this->statusCounts($previousRows);

        $currentValues = [
            'total' => $currentTotal,
            'approved' => $current['approved'],
            'pending' => $current['pending'],
            'denied' => $current['denied'],
        ];
        $previousValues = [
            'total' => $previousTotal,
            'approved' => $previousCounts['approved'],
            'pending' => $previousCounts['pending'],
            'denied' => $previousCounts['denied'],
        ];

        $changes = [];
        foreach ($currentValues as $key => $value) {
            $baseline = $previousValues[$key];
            $changes[$key] = [
                'difference' => $value - $baseline,
                'percentage' => $baseline > 0 ? (($value - $baseline) / $baseline) * 100 : null,
                'baseline' => $baseline,
            ];
        }

        $previousApprovalRate = $this->rate($previousCounts['approved'], $previousTotal);
        $previousRejectionRate = $this->rate($previousCounts['denied'], $previousTotal);
        $changes['approval_rate'] = [
            'difference' => $previousApprovalRate === null || $this->rate($current['approved'], $currentTotal) === null
                ? null : $this->rate($current['approved'], $currentTotal) - $previousApprovalRate,
            'percentage' => null,
            'baseline' => $previousApprovalRate,
        ];
        $changes['rejection_rate'] = [
            'difference' => $previousRejectionRate === null || $this->rate($current['denied'], $currentTotal) === null
                ? null : $this->rate($current['denied'], $currentTotal) - $previousRejectionRate,
            'percentage' => null,
            'baseline' => $previousRejectionRate,
        ];

        return [
            'available' => true,
            'start' => $previousStart,
            'end' => $previousEnd,
            'counts' => array_merge(['total' => $previousTotal], $previousCounts),
            'changes' => $changes,
        ];
    }

    private function activityChart(Builder $query, array $interval): array
    {
        $earliest = $interval['start']?->copy();
        $latest = $interval['end']?->copy();

        if (!$earliest) {
            $minimum = (clone $query)->min('created_at');
            $earliest = $minimum ? Carbon::parse($minimum) : null;
        }
        if (!$latest) {
            $latest = now();
        }

        if (!$earliest || $latest->lessThan($earliest)) {
            return ['grouping' => 'month', 'labels' => [], 'series' => [], 'rows' => collect()];
        }

        $grouping = $earliest->diffInMonths($latest) > 24 ? 'year' : 'month';
        $keyExpression = $this->periodKeyExpression($grouping);
        $rows = (clone $query)
            ->selectRaw("{$keyExpression} as period_key, status, COUNT(*) as total")
            ->groupBy('period_key', 'status')
            ->orderBy('period_key')
            ->get();

        $grouped = $rows->groupBy('period_key')->map(function (Collection $periodRows): array {
            $values = ['total' => 0, 'approved' => 0, 'pending' => 0, 'denied' => 0, 'other' => 0];
            foreach ($periodRows as $row) {
                $count = (int) $row->total;
                $values['total'] += $count;
                if (array_key_exists((string) $row->status, self::STATUS_LABELS)) {
                    $values[(string) $row->status] += $count;
                } else {
                    $values['other'] += $count;
                }
            }

            return $values;
        });

        $first = $grouping === 'year' ? $earliest->copy()->startOfYear() : $earliest->copy()->startOfMonth();
        $last = $grouping === 'year' ? $latest->copy()->startOfYear() : $latest->copy()->startOfMonth();
        $periods = CarbonPeriod::create($first, $grouping === 'year' ? '1 year' : '1 month', $last);
        $labels = [];
        $series = ['total' => [], 'approved' => [], 'pending' => [], 'denied' => []];

        foreach ($periods as $date) {
            $key = $grouping === 'year' ? $date->format('Y') : $date->format('Y-m');
            $values = $grouped->get($key, ['total' => 0, 'approved' => 0, 'pending' => 0, 'denied' => 0]);
            $labels[] = [
                'key' => $key,
                'label' => $grouping === 'year' ? $date->format('Y') : $date->format('M Y'),
                'total' => $values['total'],
                'approved' => $values['approved'],
                'pending' => $values['pending'],
                'denied' => $values['denied'],
            ];
            foreach ($series as $seriesKey => $valuesArray) {
                $series[$seriesKey][] = $values[$seriesKey];
            }
        }

        return [
            'grouping' => $grouping,
            'labels' => $labels,
            'series' => $series,
            'rows' => collect($labels),
        ];
    }

    private function yearComparison(Builder $query): Collection
    {
        $rows = (clone $query)
            ->selectRaw($this->periodKeyExpression('year').' as period_key, status, COUNT(*) as total')
            ->groupBy('period_key', 'status')
            ->orderBy('period_key')
            ->get();

        return $rows->groupBy('period_key')->map(function (Collection $periodRows, string $year): array {
            $counts = ['total' => 0, 'approved' => 0, 'pending' => 0, 'denied' => 0, 'other' => 0];
            foreach ($periodRows as $row) {
                $count = (int) $row->total;
                $counts['total'] += $count;
                if (array_key_exists((string) $row->status, self::STATUS_LABELS)) {
                    $counts[(string) $row->status] += $count;
                } else {
                    $counts['other'] += $count;
                }
            }

            return array_merge(['period' => $year], $counts, [
                'approval_rate' => $this->rate($counts['approved'], $counts['total']),
                'rejection_rate' => $this->rate($counts['denied'], $counts['total']),
            ]);
        })->values();
    }

    private function demographics(Builder $query, int $total): array
    {
        return [
            'age' => $this->ageDistribution($query, $total),
            'sex' => $this->groupedDistribution($query, 'sex', $total),
            'barangay' => $this->groupedDistribution($query, 'barangay', $total),
            'education' => $this->groupedDistribution($query, 'education', $total),
        ];
    }

    private function groupedDistribution(Builder $query, string $column, int $total): array
    {
        $rows = (clone $query)
            ->select($column)
            ->selectRaw('COUNT(*) as total')
            ->groupBy($column)
            ->orderByDesc('total')
            ->get();

        return $this->distribution($rows->map(fn ($row) => [
            'label' => filled($row->{$column}) ? (string) $row->{$column} : 'Not provided',
            'count' => (int) $row->total,
        ])->all(), $total);
    }

    private function ageDistribution(Builder $query, int $total): array
    {
        $ages = (clone $query)
            ->select('age')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('age')
            ->get();
        $counts = [];

        foreach ($ages as $row) {
            if ($row->age === null) {
                $label = 'Not provided';
            } else {
                $age = (int) $row->age;
                $label = match (true) {
                    $age < 15 => 'Outside application age range',
                    $age <= 17 => '15–17',
                    $age <= 20 => '18–20',
                    $age <= 23 => '21–23',
                    $age <= 26 => '24–26',
                    $age <= 30 => '27–30',
                    default => 'Outside application age range',
                };
            }
            $counts[$label] = ($counts[$label] ?? 0) + (int) $row->total;
        }

        $items = collect($counts)->map(fn ($count, $label) => ['label' => $label, 'count' => $count])
            ->sortByDesc('count')
            ->values()
            ->all();

        return $this->distribution($items, $total);
    }

    private function distribution(array $items, int $total): array
    {
        return [
            'available' => $items !== [],
            'items' => array_map(fn (array $item) => $item + [
                'percentage' => $total > 0 ? round($item['count'] / $total * 100, 1) : null,
            ], $items),
            'total' => $total,
        ];
    }

    private function insights(array $chart, array $demographics, int $total): array
    {
        $insights = [];
        $periods = collect($chart['labels'])->filter(fn (array $item) => $item['total'] > 0);

        if ($periods->isNotEmpty()) {
            $highest = $periods->sortByDesc('total')->first();
            $lowest = $periods->sortBy('total')->first();
            $insights[] = ['label' => 'Highest activity', 'value' => $highest['label'].' ('.$highest['total'].' applications)'];
            if ($periods->count() > 1) {
                $insights[] = ['label' => 'Lowest active period', 'value' => $lowest['label'].' ('.$lowest['total'].' applications)'];
            }
            $insights[] = [
                'label' => 'Average per '.($chart['grouping'] === 'year' ? 'year' : 'month'),
                'value' => number_format($total / max(1, count($chart['labels'])), 1).' applications',
            ];
        }

        $topBarangay = $demographics['barangay']['items'][0] ?? null;
        if ($topBarangay && $topBarangay['label'] !== 'Not provided') {
            $insights[] = ['label' => 'Most applications by barangay', 'value' => $topBarangay['label'].' ('.$topBarangay['count'].')'];
        }
        $topAgeGroup = collect($demographics['age']['items'])
            ->first(fn (array $item) => $item['label'] !== 'Not provided' && $item['label'] !== 'Outside application age range');
        if ($topAgeGroup) {
            $insights[] = ['label' => 'Most common age group', 'value' => $topAgeGroup['label'].' ('.$topAgeGroup['count'].')'];
        }

        return $insights;
    }

    private function summary(int $total, array $counts): string
    {
        if ($total === 0) {
            return 'No application data is available for the selected filters.';
        }

        $summary = "The selected period contains {$total} application records: {$counts['approved']} approved, {$counts['pending']} pending review, and {$counts['denied']} rejected.";
        if ($counts['other'] > 0) {
            $summary .= " {$counts['other']} records have another or unrecognized status.";
        }

        return $summary.' Approval rate: '.$this->formatRate($this->rate($counts['approved'], $total))
            .'; rejection rate: '.$this->formatRate($this->rate($counts['denied'], $total)).'.';
    }

    private function conclusion(int $total, array $counts, array $insights): string
    {
        if ($total === 0) {
            return 'No conclusion can be drawn because no application records match the selected filters.';
        }

        $text = "The selected reporting period contains {$total} applications, with an approval rate of "
            .$this->formatRate($this->rate($counts['approved'], $total))
            .' and a rejection rate of '.$this->formatRate($this->rate($counts['denied'], $total))
            .". {$counts['pending']} applications are pending review.";
        $highest = collect($insights)->firstWhere('label', 'Highest activity');
        if ($highest) {
            $text .= ' Highest activity was recorded in '.$highest['value'].'.';
        }

        return $text;
    }

    private function periodKeyExpression(string $grouping): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => $grouping === 'year'
                ? "strftime('%Y', created_at)"
                : "strftime('%Y-%m', created_at)",
            'pgsql' => $grouping === 'year'
                ? "TO_CHAR(created_at, 'YYYY')"
                : "TO_CHAR(created_at, 'YYYY-MM')",
            default => $grouping === 'year'
                ? 'DATE_FORMAT(created_at, "%Y")'
                : 'DATE_FORMAT(created_at, "%Y-%m")',
        };
    }

    private function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? ucfirst(str_replace(['_', '-'], ' ', $status));
    }

    private function rate(int $count, int $total): ?float
    {
        return $total > 0 ? round($count / $total * 100, 1) : null;
    }

    private function formatRate(?float $rate): string
    {
        return $rate === null ? 'Data not available' : number_format($rate, 1).'%';
    }
}
