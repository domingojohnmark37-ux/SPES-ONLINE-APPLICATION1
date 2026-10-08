@extends('layouts.admin')

@section('title', 'Applicants Management')
@section('page-title', 'Applicants Management')
@section('page-sub', 'Review, approve or deny SPES applications')

@section('content')

{{-- Stats mini row --}}
<div class="stats-grid" style="margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fa-solid fa-file-lines"></i></div>
        <div><div class="stat-num">{{ $stats['total'] }}</div><div class="stat-label">Total</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fa-solid fa-clock"></i></div>
        <div><div class="stat-num">{{ $stats['pending'] }}</div><div class="stat-label">Pending</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div><div class="stat-num">{{ $stats['approved'] }}</div><div class="stat-label">Approved</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fa-solid fa-circle-xmark"></i></div>
        <div><div class="stat-num">{{ $stats['denied'] }}</div><div class="stat-label">Denied</div></div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fa-solid fa-list"></i> All Applications</h2>
    </div>
    <div class="card-body">
        @if($approvalCapacity['limit'])
            <div class="alert {{ $approvalCapacity['full'] ? 'alert-danger' : 'alert-info' }}" role="status">
                <i class="fa-solid {{ $approvalCapacity['full'] ? 'fa-lock' : 'fa-chart-simple' }}" aria-hidden="true"></i>
                <span>
                    <strong>{{ $approvalCapacity['approved'] }} of {{ $approvalCapacity['limit'] }} approved this season.</strong>
                    @if($approvalCapacity['full'])
                        The approval limit is full. Further approvals and new application submissions are disabled.
                    @else
                        {{ $approvalCapacity['limit'] - $approvalCapacity['approved'] }} approval {{ \Illuminate\Support\Str::plural('slot', $approvalCapacity['limit'] - $approvalCapacity['approved']) }} remaining.
                    @endif
                    <a href="{{ route('admin.settings') }}" style="color:inherit;font-weight:700;">Manage limit</a>
                </span>
            </div>
        @endif
        {{-- Filters --}}
        <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 20px; flex-wrap: wrap;">
            <form method="GET" class="search-bar" style="margin-bottom: 0; flex: 1; min-width: 300px;">
                <input type="text" name="search" placeholder="Search by name…" value="{{ request('search') }}" style="min-width:220px;">
                <select name="barangay">
                    <option value="">All Barangay</option>
                    @foreach(['Abagao','Alaguia','Bagumbayan','Bangag','Bical','Bicud','Binag','Cabayabasan (Capacuan)','Cagoran','Cambong','Catayauan','Catugan','Centro (Poblacion)','Cullit','Dagupan','Dalaya','Fabrica','Fusina','Jurisdiction','Lalafugan','Logac','Magallungon (Santa Teresa)','Magapit','Malanao','Maxingal','Naguilian','Paranum','Rosario','San Antonio (Lafu)','San Jose','San Juan','San Lorenzo','San Mariano','Santa Maria','Tucalana'] as $b)
                        <option value="{{ $b }}" {{ request('barangay')===$b ? 'selected':'' }}>{{ $b }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
                <a href="{{ route('admin.applications.index') }}" class="btn btn-outline btn-sm">Clear</a>
            </form>
        </div>

        @if($applications->isEmpty())
            <p style="color:var(--text-muted);text-align:center;padding:40px 0;">No applications found.</p>
        @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Ref ID</th>
                        <th>Full Name</th>
                        <th>Barangay</th>
                        <th>SPES Type</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($applications as $i => $app)
                    <tr>
                        <td style="color:var(--text-muted)">{{ $applications->firstItem() + $i }}</td>
                        <td><code style="font-size:.78rem;color:var(--primary)">{{ $app->ref_id }}</code></td>
                        <td><strong>{{ $app->full_name }}</strong>
                            <div style="font-size:.75rem;color:var(--text-muted)">{{ $app->sex }}, {{ $app->age }} yrs</div>
                        </td>
                        <td>{{ $app->barangay }}</td>
                        <td>
                            <span class="badge {{ $app->spes_status === 'new' ? 'badge-new' : 'badge-baby' }}">
                                {{ $app->spes_status === 'new' ? 'New' : 'SPES Baby' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-{{ $app->status }}">
                                <i class="fa-solid {{ $app->status === 'approved' ? 'fa-circle-check' : ($app->status === 'denied' ? 'fa-circle-xmark' : 'fa-clock') }}"></i>
                                {{ ucfirst($app->status) }}
                            </span>
                        </td>
                        <td style="font-size:.78rem;color:var(--text-muted)">@adminDate($app->created_at)</td>
                        <td>
                            <a href="{{ route('admin.applications.show', $app) }}" class="btn btn-info btn-sm" style="margin-bottom:4px;">
                                <i class="fa-solid fa-eye"></i> View
                            </a>
                            @if($app->status === 'pending' && !$approvalCapacity['full'])
                                <form method="POST" action="{{ route('admin.applications.approve', $app) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Approve this application?')" style="margin-bottom:4px;">
                                        <i class="fa-solid fa-check"></i> Approve
                                    </button>
                                </form>
                            @elseif($app->status === 'pending' && $approvalCapacity['full'])
                                <button type="button" class="btn btn-success btn-sm" disabled aria-disabled="true" title="Approval limit reached">
                                    <i class="fa-solid fa-lock" aria-hidden="true"></i> Approval limit reached
                                </button>
                            @endif
                            @if($app->status === 'pending')
                                <form method="POST" action="{{ route('admin.applications.deny', $app) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Deny this application?')">
                                        <i class="fa-solid fa-xmark"></i> Deny
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="pagination">
            {{ $applications->appends(request()->query())->links('pagination::simple-bootstrap-4') }}
        </div>
        @endif
    </div>
</div>
@endsection
