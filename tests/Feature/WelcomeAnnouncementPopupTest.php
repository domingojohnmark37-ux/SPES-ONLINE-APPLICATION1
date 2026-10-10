<?php

namespace Tests\Feature;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomeAnnouncementPopupTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_announcements_render_in_a_floating_popup(): void
    {
        News::create([
            'title' => 'SPES application update',
            'content' => '<p>Applications open soon.</p>',
            'display_on' => 'landing',
            'is_published' => true,
            'published_at' => now(),
        ]);
        News::create([
            'title' => 'Applicant-only update',
            'content' => 'This should not appear on the landing page.',
            'display_on' => 'portal',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('dole-spes-link', false)
            ->assertSee('dole.gov.ph/special-program-for-the-employment-of-students-spes/')
            ->assertSee('<svg viewBox="0 0 24 24"', false)
            ->assertSee('<svg class="icon"', false)
            ->assertDontSee('cdnjs.cloudflare.com/ajax/libs/font-awesome')
            ->assertSee('id="announcement-launcher"', false)
            ->assertSee('id="announcement-popup"', false)
            ->assertSee('data-auto-open="true"', false)
            ->assertSee('Latest Updates &amp; Announcements', false)
            ->assertSee('SPES application update')
            ->assertSee('Applications open soon.')
            ->assertDontSee('Applicant-only update');
    }

    public function test_empty_landing_announcements_popup_still_auto_opens(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-auto-open="true"', false)
            ->assertSee('No announcements at this time.');
    }
}
