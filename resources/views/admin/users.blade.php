@extends('layouts.admin')

@section('title', 'User Management')
@section('page-title', 'User Management')
@section('page-sub', 'All registered applicant accounts')

@section('styles')
<style>
    .users-toolbar {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:14px;
        margin-bottom:18px;
    }
    .users-search {
        display:flex;
        align-items:center;
        gap:10px;
        width:min(100%, 520px);
        min-width:0;
        padding:0 14px;
        border:1px solid var(--border);
        border-radius:8px;
        background:#fff;
        color:var(--text-muted);
    }
    .users-search input {
        width:100%;
        min-width:0;
        height:42px;
        border:0;
        outline:0;
        background:transparent;
        color:var(--text);
        font:inherit;
    }
    .user-grid {
        display:grid;
        grid-template-columns:repeat(3, minmax(0, 1fr));
        gap:16px;
    }
    .user-card {
        min-width:0;
        padding:18px;
        border-radius:12px;
        background:#fff;
        box-shadow:var(--shadow);
        border:1px solid rgba(139,0,0,.05);
    }
    .user-card-top {
        display:flex;
        align-items:flex-start;
        gap:12px;
    }
    .user-avatar {
        display:grid;
        place-items:center;
        flex:0 0 48px;
        width:48px;
        height:48px;
        border-radius:50%;
        object-fit:cover;
        background:#f8e8e8;
        color:var(--primary);
        font-size:1rem;
        font-weight:800;
    }
    .user-card-heading { min-width:0; flex:1; }
    .user-card-name { overflow-wrap:anywhere; font-size:.98rem; font-weight:700; color:var(--text); }
    .user-status, .user-role {
        display:inline-flex;
        align-items:center;
        width:max-content;
        max-width:100%;
        padding:4px 9px;
        border-radius:999px;
        font-size:.72rem;
        font-weight:700;
        white-space:nowrap;
    }
    .user-status { flex:0 0 auto; }
    .user-card-status { display:flex; flex-direction:column; align-items:flex-end; gap:4px; }
    .user-card-status small { color:var(--text-muted); font-size:.66rem; text-align:right; }
    .user-status-active { background:#e8f5e9; color:#2e7d32; }
    .user-status-pending { background:#fff3e0; color:#e65100; }
    .user-status-inactive { background:#f1f3f5; color:#59636e; }
    .user-role { margin-top:6px; background:#f8e8e8; color:var(--primary); }
    .user-contact-list { display:grid; gap:9px; margin-top:16px; }
    .user-contact-row {
        display:flex;
        align-items:flex-start;
        gap:9px;
        min-width:0;
        color:#4b5563;
        font-size:.82rem;
        line-height:1.4;
    }
    .user-contact-row i { width:15px; margin-top:2px; color:var(--primary); text-align:center; flex:0 0 15px; }
    .user-contact-row span { min-width:0; overflow-wrap:anywhere; }
    .user-card-footer {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        margin-top:16px;
        padding-top:14px;
        border-top:1px solid var(--border);
    }
    .user-registered { color:var(--text-muted); font-size:.75rem; }
    .user-actions { display:flex; justify-content:flex-end; flex-wrap:wrap; gap:6px; }
    .user-action {
        padding:6px 9px;
        border:1px solid #1565c0;
        border-radius:6px;
        background:#fff;
        color:#1565c0;
        font-size:.72rem;
        font-weight:700;
        line-height:1.2;
        text-decoration:none;
        cursor:pointer;
    }
    .user-action:hover, .user-action:focus-visible { background:#eff6ff; color:#0d47a1; }
    .user-card[hidden] { display:none; }
    .users-empty { grid-column:1 / -1; padding:36px 16px; color:var(--text-muted); text-align:center; }
    @media (max-width:1100px) {
        .user-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width:767.98px) {
        .users-toolbar { align-items:stretch; flex-direction:column; }
        .users-search { width:100%; }
        .user-grid { grid-template-columns:minmax(0, 1fr); gap:12px; }
        .user-card { padding:16px; }
        .user-card-footer { align-items:flex-start; flex-direction:column; }
        .user-actions { width:100%; justify-content:flex-end; }
    }
</style>
@endsection

@section('content')

<div class="users-toolbar">
    <x-admin.search-add-bar />
</div>

<x-admin.user-grid :users="$users" />

<div class="pagination">
    {{ $users->links('pagination::simple-bootstrap-4') }}
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const search = document.getElementById('userSearch');
        const cards = [...document.querySelectorAll('[data-user-card]')];
        const viewLinks = [...document.querySelectorAll('[data-user-view]')];
        const queryParams = new URLSearchParams(window.location.search);

        function applySearch(query, updateUrl = false) {
            const normalizedQuery = query.trim().toLocaleLowerCase();
            cards.forEach(card => {
                card.hidden = !card.dataset.search.includes(normalizedQuery);
            });
            viewLinks.forEach(link => {
                const url = new URL(link.href);
                if (query.trim()) url.searchParams.set('search', query.trim());
                else url.searchParams.delete('search');
                link.href = url.pathname + url.search;
            });
            if (updateUrl) {
                if (query.trim()) queryParams.set('search', query.trim());
                else queryParams.delete('search');
                const suffix = queryParams.toString();
                window.history.replaceState({}, '', window.location.pathname + (suffix ? '?' + suffix : ''));
            }
        }

        if (search) {
            search.value = queryParams.get('search') || '';
            applySearch(search.value);
            search.addEventListener('input', function () { applySearch(this.value, true); });
        }

    });
</script>
@endsection
