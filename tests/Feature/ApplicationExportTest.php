<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_applications_as_xlsx(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.applications.export'))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_non_admin_cannot_export_applications(): void
    {
        $user = User::factory()->create([]);

        $this->actingAs($user)
            ->get(route('admin.applications.export'))
            ->assertForbidden();
    }
}
