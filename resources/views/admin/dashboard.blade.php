@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Good morning, Admin! 👋')
@section('page-sub', "Here's what's happening with SPES today.")

@section('styles')
<style>
    .dashboard-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 18px;
        margin-bottom: 26px;
    }
    .dashboard-card {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 18px 45px rgba(17, 24, 39, 0.05);
        padding: 22px 24px;
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .dashboard-card .stat-icon {
        width: 56px;
        height: 56px;
        border-radius: 16px;
        display: grid;
        place-items: center;
        font-size: 1.25rem;
    }
    .dashboard-card .stat-icon.teal { background: #e6fffa; color: #00695c; }
    .dashboard-card .stat-icon.orange { background: #fff8e1; color: #e65100; }
    .dashboard-card .stat-icon.green { background: #e8f5e9; color: #2e7d32; }
    .dashboard-card .stat-icon.red { background: #ffebee; color: #c62828; }
    .dashboard-card .stat-icon.blue { background: #e3f2fd; color: #1565c0; }
    .dashboard-card .stat-value { font-size: 2.05rem; font-weight: 800; color: var(--primary); }
    .dashboard-card .stat-note { font-size: .78rem; color: var(--text-muted); margin-top: 6px; }

    .dashboard-panel {
        display: grid;
        grid-template-columns: 1.6fr 1fr;
        gap: 20px;
    }
    .panel-card {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 18px 45px rgba(17, 24, 39, 0.05);
        overflow: hidden;
    }
    .panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 22px 24px;
        border-bottom: 1px solid #f3f6f8;
    }
    .panel-header h3 {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: var(--primary);
    }
    .panel-header p { margin: 6px 0 0; color: var(--text-muted); font-size: .88rem; }
    .view-all-link {
        color: var(--primary);
        font-size: .82rem;
        font-weight: 700;
        text-decoration: none;
    }
    .activity-list {
        display: flex;
        flex-direction: column;
        gap: 0;
    }
    .activity-item {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 18px 24px;
        border-bottom: 1px solid #f3f6f8;
    }
    .activity-item:last-child { border-bottom: none; }
    .activity-avatar {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        font-size: 1.05rem;
        background: #f3f6f8;
        color: var(--primary);
        flex-shrink: 0;
    }
    .activity-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .activity-title { font-size: .93rem; font-weight: 700; color: #111827; }
    .activity-meta { font-size: .8rem; color: #6b7280; }
    .activity-action { display: flex; justify-content: flex-end; margin-top: 6px; }
    .panel-footer {
        padding: 18px 24px;
        background: #fcfcfd;
        color: var(--text-muted);
        font-size: .9rem;
    }

    @media (max-width: 1080px) {
        .dashboard-panel { grid-template-columns: 1fr; }
    }
    @media (max-width: 600px) {
        .dashboard-cards {
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 18px;
        }
        .dashboard-card {
            gap: 14px;
            padding: 16px;
            border-radius: 14px;
            box-shadow: 0 8px 24px rgba(17, 24, 39, 0.06);
        }
        .dashboard-card .stat-icon {
            width: 46px;
            height: 46px;
            flex: 0 0 46px;
            border-radius: 13px;
        }
        .dashboard-card > div:last-child { min-width: 0; }
        .dashboard-panel { gap: 14px; }
        .panel-card { border-radius: 14px; }
        .panel-header { gap: 12px; padding: 16px; }
        .panel-header > div { min-width: 0; }
        .panel-header p { line-height: 1.4; }
        .view-all-link { flex: 0 0 auto; white-space: nowrap; }
        .activity-item { gap: 12px; padding: 14px 16px; }
        .activity-title { line-height: 1.35; }
        .activity-meta { line-height: 1.45; overflow-wrap: anywhere; }
    }
</style>
@endsection

@section('content')

<div class="dashboard-cards">
    <div class="dashboard-card">
        <div class="stat-icon teal"><i class="fa-solid fa-file-lines"></i></div>
        <div>
            <div class="stat-value">{{ $stats['total'] }}</div>
            <div class="stat-note">Total Applications<br><small>All time</small></div>
        </div>
    </div>
    <div class="dashboard-card">
        <div class="stat-icon orange"><i class="fa-solid fa-clock"></i></div>
        <div>
            <div class="stat-value">{{ $stats['pending'] }}</div>
            <div class="stat-note">Pending<br><small>Needs review</small></div>
        </div>
    </div>
    <div class="dashboard-card">
        <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div>
            <div class="stat-value">{{ $stats['approved'] }}</div>
            <div class="stat-note">Approved<br><small>This is good!</small></div>
        </div>
    </div>
    <div class="dashboard-card">
        <div class="stat-icon red"><i class="fa-solid fa-circle-xmark"></i></div>
        <div>
            <div class="stat-value">{{ $stats['denied'] }}</div>
            <div class="stat-note">Denied<br><small>This is bad!</small></div>
        </div>
    </div>
    <div class="dashboard-card">
        <div class="stat-icon blue"><i class="fa-solid fa-users"></i></div>
        <div>
            <div class="stat-value">{{ $stats['users'] }}</div>
            <div class="stat-note">Registered Users<br><small>Total users</small></div>
        </div>
    </div>
</div>

<div class="dashboard-panel">
    <div class="panel-card">
        <div class="panel-header">
            <div>
                <h3>Pending Applications</h3>
                <p>Review the latest submissions that still need action.</p>
            </div>
            <a href="{{ route('admin.applications.index', ['status' => 'pending']) }}" class="view-all-link">View All</a>
        </div>
        <div class="activity-list">
            @forelse($pendingApplications as $application)
                <div class="activity-item">
                    <div class="activity-avatar">{{ strtoupper(substr($application->user->name, 0, 1)) }}</div>
                    <div class="activity-content">
                        <div class="activity-title">{{ $application->user->name }}</div>
                        <div class="activity-meta">Submitted @adminDate($application->created_at, true)</div>
                        <div class="activity-action">
                            <a href="{{ route('admin.applications.show', $application) }}" class="btn btn-sm btn-outline">Review</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="activity-item">
                    <div class="activity-avatar"><i class="fa-solid fa-hourglass-half"></i></div>
                    <div class="activity-content">
                        <div class="activity-title">No pending applications yet.</div>
                        <div class="activity-meta">Once users submit applications, they will appear here.</div>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <div class="panel-card">
        <div class="panel-header">
            <div>
                <h3>Recent User Activity</h3>
                <p>Track the latest actions from applicants and registered users.</p>
            </div>
        </div>
        <div class="activity-list">
            @forelse($recentActivities as $activity)
                <div class="activity-item">
                    <div class="activity-avatar"><i class="{{ $activity['icon'] }}"></i></div>
                    <div class="activity-content">
                        <div class="activity-title">{!! $activity['message'] !!}</div>
                        <div class="activity-meta">{{ $activity['time'] }}</div>
                    </div>
                </div>
            @empty
                <div class="activity-item">
                    <div class="activity-avatar"><i class="fa-solid fa-clock"></i></div>
                    <div class="activity-content">
                        <div class="activity-title">No recent activity available.</div>
                        <div class="activity-meta">Activity updates will appear as users interact with the portal.</div>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>

@endsection
