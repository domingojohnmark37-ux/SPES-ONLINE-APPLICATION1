<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNewsRequest;
use App\Http\Requests\UpdateNewsRequest;
use App\Models\News;
use App\Notifications\ApplicantPortalUpdate;
use App\Services\ApplicantNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NewsController extends Controller
{
    /**
     * Display a listing of all news (published + drafts) for admin
     */
    public function index()
    {
        $news = News::with('author')
            ->orderBy('published_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.news.index', compact('news'));
    }

    /**
     * Show the form for creating a new announcement
     */
    public function create()
    {
        return view('admin.news.create');
    }

    /**
     * Store a newly created announcement
     */
    public function store(StoreNewsRequest $request)
    {
        $validated = $request->validated();
        $validated['created_by'] = Auth::id();

        News::create($validated);

        return redirect()->route('admin.news.index')
            ->with('success', 'News article created successfully.');
    }

    /**
     * Show the form for editing the specified news
     */
    public function edit(News $news)
    {
        return view('admin.news.edit', compact('news'));
    }

    /**
     * Update the specified news
     */
    public function update(
        UpdateNewsRequest $request,
        News $news,
        ApplicantNotificationService $notifications,
    )
    {
        $validated = $request->validated();
        $news->update($validated);

        if ($news->is_published && $news->wasChanged(['title', 'content'])) {
            $this->notifyApplicantsAboutAnnouncement($news, $notifications);
        }

        return redirect()->route('admin.news.index')
            ->with('success', 'News article updated successfully.');
    }

    /**
     * Delete the specified news
     */
    public function destroy(News $news)
    {
        $news->delete();

        return redirect()->route('admin.news.index')
            ->with('success', 'News article deleted successfully.');
    }

    /**
     * Toggle publish status of a news article
     */
    public function togglePublish(News $news, ApplicantNotificationService $notifications)
    {
        if ($news->is_published) {
            $news->update([
                'is_published' => false,
                'published_at' => null,
            ]);
            $message = 'News article unpublished.';
        } else {
            $news->update([
                'is_published' => true,
                'published_at' => now(),
            ]);
            $this->notifyApplicantsAboutAnnouncement($news, $notifications);
            $message = 'News article published.';
        }

        return redirect()->route('admin.news.index')
            ->with('success', $message);
    }

    private function notifyApplicantsAboutAnnouncement(
        News $news,
        ApplicantNotificationService $notifications,
    ): void {
        $notifications->notifyApplicants(new ApplicantPortalUpdate(
            'notify_announcements',
            'New SPES announcement: '.$news->title,
            $news->content,
            [
                'news_id' => $news->id,
                'announcement_title' => $news->title,
                'announcement_content' => $news->content,
            ],
        ), "announcement:{$news->id}:{$news->updated_at?->getTimestamp()}");
    }
}
