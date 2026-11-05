<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationIndexFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_array_application_filters_do_not_cause_a_server_error(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        foreach ([
            '/admin/applications?search%5B%5D=a',
            '/admin/applications?status%5B%5D=a',
            '/admin/applications?barangay%5B%5D=a',
        ] as $url) {
            $response = $this->get($url);

            $this->assertContains($response->status(), [200, 302], $url);
        }
    }
}
