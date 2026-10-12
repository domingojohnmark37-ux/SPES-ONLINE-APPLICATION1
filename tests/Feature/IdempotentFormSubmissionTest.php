<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdempotentFormSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_replaying_a_create_form_token_does_not_create_a_duplicate_record(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = str_repeat('a', 40);
        $payload = [
            '_idempotency_token' => $token,
            'title' => 'One announcement',
            'content' => 'Announcement content.',
            'display_on' => 'portal',
        ];

        $this->actingAs($admin)
            ->post(route('admin.news.store'), $payload)
            ->assertRedirect(route('admin.news.index'));

        $this->post(route('admin.news.store'), $payload)
            ->assertRedirect(route('admin.news.index'))
            ->assertSessionHas('info');

        $this->assertSame(1, News::where('title', 'One announcement')->count());
    }
}
