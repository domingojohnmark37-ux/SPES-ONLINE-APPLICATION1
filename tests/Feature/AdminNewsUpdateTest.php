<?php

namespace Tests\Feature;

use App\Jobs\NotifyApplicantsJob;
use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminNewsUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_published_news_does_not_notify_without_opt_in(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $news = $this->publishedNews($admin);

        $this->actingAs($admin)
            ->get(route('admin.news.edit', $news))
            ->assertOk()
            ->assertSee('name="notify_applicants" value="1"', false)
            ->assertDontSee('name="notify_applicants" value="1" checked', false);

        $this->put(route('admin.news.update', $news), [
            'title' => 'Corrected announcement',
            'content' => $news->content,
            'display_on' => 'portal',
        ])->assertRedirect(route('admin.news.index'));

        Queue::assertNotPushed(NotifyApplicantsJob::class);
    }

    public function test_published_news_can_be_resent_after_explicit_opt_in(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $news = $this->publishedNews($admin);

        $this->actingAs($admin)
            ->put(route('admin.news.update', $news), [
                'title' => 'Updated announcement',
                'content' => $news->content,
                'display_on' => 'portal',
                'notify_applicants' => '1',
            ])
            ->assertRedirect(route('admin.news.index'));

        Queue::assertPushed(NotifyApplicantsJob::class, fn (NotifyApplicantsJob $job): bool =>
            $job->audience === 'all'
            && str_starts_with($job->eventKey, "announcement:{$news->id}:")
        );
    }

    private function publishedNews(User $admin): News
    {
        return News::create([
            'title' => 'Original announcement',
            'content' => 'Original announcement content.',
            'is_published' => true,
            'published_at' => now(),
            'display_on' => 'portal',
            'created_by' => $admin->id,
        ]);
    }
}
