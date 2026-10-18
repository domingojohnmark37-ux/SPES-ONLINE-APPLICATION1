<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Services\StatisticsReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsReportAgeInsightTest extends TestCase
{
    use RefreshDatabase;

    public function test_age_group_insight_is_omitted_when_no_applicants_have_an_in_range_age(): void
    {
        foreach ([10, 31] as $age) {
            Application::factory()->create([
                'age' => $age,
                'created_at' => now(),
            ]);
        }

        $report = app(StatisticsReportService::class)->report(['date_range' => 'all']);
        $insightLabels = collect($report['insights'])->pluck('label')->all();

        $this->assertNotContains('Most common age group', $insightLabels);
    }
}
