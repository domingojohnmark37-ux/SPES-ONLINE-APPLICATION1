<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ApplicationExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_the_application_spreadsheet(): void
    {
        Excel::fake();
        $this->travelTo(now()->setDate(2026, 10, 18)->setTime(17, 0, 0));
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.applications.export'))
            ->assertOk();

        Excel::assertDownloaded('spes-applications-2026-10-18-170000.xlsx');
    }

    public function test_non_admin_gets_forbidden_from_the_application_export(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('admin.applications.export'))
            ->assertForbidden();
    }
}
