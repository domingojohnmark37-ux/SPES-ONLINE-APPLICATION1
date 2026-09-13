@extends('layouts.admin')

@section('title', 'News Management')
@section('page-title', 'News & Announcements')
@section('page-sub', 'Create, edit, and manage announcements for the public portal')

@section('content')

<div class="card">
    <div class="card-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2><i class="fa-solid fa-newspaper"></i> All News</h2>
            <a href="{{ route('admin.news.create') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus"></i> New Announcement
            </a>
        </div>
    </div>
    <div class="card-body">
        @if($news->isEmpty())
            <p style="color:var(--text-muted);text-align:center;padding:40px 0;">No news articles found.</p>
        @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Display On</th>
                        <th>Published</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($news as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->title }}</strong>
                            <div style="font-size:.75rem;color:var(--text-muted); margin-top: 3px;">
                                {{ Str::limit($item->content, 60) }}
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $item->is_published ? 'badge-approved' : 'badge-pending' }}">
                                <i class="fa-solid {{ $item->is_published ? 'fa-circle-check' : 'fa-circle-pause' }}"></i>
                                {{ $item->is_published ? 'Published' : 'Draft' }}
                            </span>
                        </td>
                        <td>
                            @if(($item->display_on ?? 'both') === 'landing')
                                Landing Page
                            @elseif(($item->display_on ?? 'both') === 'portal')
                                SPES Portal
                            @else
                                Both
                            @endif
                        </td>
                        <td style="font-size:.78rem;color:var(--text-muted)">
                            {{ $item->published_at ? $item->published_at->format('M d, Y H:i') : '—' }}
                        </td>
                        <td style="font-size:.78rem;color:var(--text-muted)">{{ $item->created_at->format('M d, Y') }}</td>
                        <td>
                            <div style="display: flex; gap: 6px;">
                                <a href="{{ route('admin.news.edit', $item) }}" class="btn btn-info btn-sm">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </a>
                                <form method="POST" action="{{ route('admin.news.toggle', $item) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn {{ $item->is_published ? 'btn-warning' : 'btn-success' }} btn-sm">
                                        <i class="fa-solid {{ $item->is_published ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                        {{ $item->is_published ? 'Unpublish' : 'Publish' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.news.destroy', $item) }}" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this news? This action cannot be undone.')">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div style="margin-top: 20px; display: flex; justify-content: center;">
            {{ $news->links() }}
        </div>
        @endif
    </div>
</div>

<style>
    .badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: .8rem;
        font-weight: 600;
        text-align: center;
    }

    .badge-approved {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .badge-pending {
        background: #fff3e0;
        color: #e65100;
    }
</style>

@endsection
