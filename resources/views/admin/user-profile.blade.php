@extends('layouts.admin')

@php
    $profile = $user->profile;
    $applications = $user->applications;
    $application = $applications->first();
    $statusLabel = $user->displayStatus($application?->status);
    $statusClass = match ($statusLabel) {
        'Active' => 'profile-status-active',
        'Pending' => 'profile-status-pending',
        default => 'profile-status-inactive',
    };
    $phone = filled($profile?->contact_number) ? $profile->contact_number : $user->contact_number;
    $presentAddress = filled($profile?->present_address) ? $profile->present_address : $user->present_address;
    $permanentAddress = filled($profile?->permanent_address) ? $profile->permanent_address : $user->permanent_address;
    $address = filled($presentAddress) ? $presentAddress : $permanentAddress;
    $profileName = collect([
        $profile?->first_name,
        $profile?->middle_name,
        $profile?->last_name,
    ])->filter(fn ($part) => filled($part))->implode(' ');
    $fullName = $profileName ?: $user->name;
    $dateOfBirth = $profile?->date_of_birth ?: $user->date_of_birth ?: $application?->birthday;
    $age = $dateOfBirth ? \Illuminate\Support\Carbon::parse($dateOfBirth)->age : null;
    $storedEducation = $profile?->education_history ?? $user->education_history;
    $educationHistory = is_array($storedEducation)
        ? collect($storedEducation)
            ->filter(fn ($row) => is_array($row) && collect($row)->except('level')->contains(fn ($value) => filled($value)))
            ->flatMap(fn ($row) => collect($row)->except('level')->filter(fn ($value) => filled($value)))
            ->implode(', ')
        : null;
    $educationLevels = ['Elementary', 'Secondary', 'Tertiary', 'Tech-Voc'];
    $educationRows = collect($educationLevels)->map(function ($level, $index) use ($storedEducation) {
        $rows = is_array($storedEducation) ? $storedEducation : [];
        $row = collect($rows)->first(fn ($item) => is_array($item) && ($item['level'] ?? null) === $level)
            ?? ($rows[$index] ?? []);
        $row = is_array($row) ? $row : [];

        return [
            'level' => $level,
            'school' => $row['school'] ?? null,
            'course' => $row['course'] ?? null,
            'year_level' => $row['year_level'] ?? null,
            'date_attended' => $row['date_attended'] ?? null,
        ];
    })->filter(fn ($row) => collect($row)->except('level')->contains(fn ($value) => filled($value)));
    $parentStatusOptions = ['Living Together', 'Solo Parent', 'Orphan', 'Guardian'];
    $storedParentStatuses = $profile?->parent_status_details ?? $user->parent_status_details ?? [];
    $storedParentStatuses = is_array($storedParentStatuses) ? $storedParentStatuses : [];
    $selectedParentStatuses = array_is_list($storedParentStatuses)
        ? $storedParentStatuses
        : collect($parentStatusOptions)->filter(fn ($status) => (bool) ($storedParentStatuses[$status] ?? false))->values()->all();
    $personalRows = [
        ['Full Name', $fullName],
        ['Sex', $profile?->sex ?: $user->sex],
        ['Birthday', $dateOfBirth ? app(\App\Support\AdminDateFormatter::class)->format($dateOfBirth) . ($age !== null ? " ({$age} years old)" : '') : null],
        ['Place of Birth', $profile?->place_of_birth ?: $user->place_of_birth],
        ['Civil Status', $profile?->status ?: $user->status],
        ['Citizenship', $profile?->citizenship ?: $user->citizenship],
        ['Present Address', $profile?->present_address ?: $user->present_address],
        ['Permanent Address', $profile?->permanent_address ?: $user->permanent_address],
        ['Contact Number', $phone],
        ['Applicant\'s Category', \App\Models\User::applicantCategoryLabel($profile?->applicant_category ?: $user->applicant_category)],
    ];
    $fatherName = $profile?->father_name ?: $user->father_name;
    $fatherContact = $profile?->father_contact_number ?: $user->father_contact_number;
    $fatherOccupation = $profile?->father_occupation ?: $user->father_occupation;
    $motherName = $profile?->mother_name ?: $user->mother_name;
    $motherContact = $profile?->mother_contact_number ?: $user->mother_contact_number;
    $motherOccupation = $profile?->mother_occupation ?: $user->mother_occupation;
    $specialSkills = $profile?->special_skills ?: $user->special_skills;
    $programRows = [
        ['SPES Beneficiary Status', $application?->spes_status ?: \App\Models\User::applicantCategoryLabel($profile?->applicant_category ?: $user->applicant_category)],
        ['Interested Position / Track', $application?->f3_position],
        ['Tertiary / TVET', $educationHistory ?: $application?->education],
        ['Soft Skills', $profile?->special_skills],
    ];
    $additionalRows = [
        ['Social Media Account', $profile?->social_media ?: $user->social_media],
        ['GSIS Beneficiary / Relationship', $profile?->gsis_beneficiary ?: $user->gsis_beneficiary],
    ];
    $formatProfileValue = fn ($value) => filled($value) ? $value : 'Not provided';
@endphp

@section('title', 'User Profile — ' . $user->name)
@section('page-title', 'User Profile')
@section('page-sub', $user->name)

@section('styles')
<style>
    .user-profile-back { margin-bottom:16px; }
    .user-profile-layout { display:grid; grid-template-columns:minmax(0, 2fr) minmax(240px, 1fr); gap:16px; align-items:start; }
    .user-profile-main { display:grid; gap:16px; min-width:0; }
    .user-profile-side { display:grid; gap:16px; min-width:0; }
    .user-profile-identity { display:flex; align-items:center; gap:16px; min-width:0; }
    .user-profile-avatar { display:grid; place-items:center; flex:0 0 76px; width:76px; height:76px; border-radius:50%; object-fit:cover; background:#e3f2fd; color:#1565c0; font-size:1.55rem; font-weight:800; }
    .user-profile-name { min-width:0; flex:1; }
    .user-profile-name h2 { overflow-wrap:anywhere; color:var(--text); font-size:1.25rem; }
    .user-profile-email { overflow-wrap:anywhere; color:var(--text-muted); margin-top:5px; font-size:.88rem; }
    .user-profile-role { display:inline-flex; margin-top:7px; padding:4px 9px; border-radius:999px; background:#e3f2fd; color:#1565c0; font-size:.72rem; font-weight:700; }
    .profile-status { display:inline-flex; width:max-content; padding:5px 10px; border-radius:999px; font-size:.75rem; font-weight:700; }
    .profile-status-active { background:#e8f5e9; color:#2e7d32; }
    .profile-status-pending { background:#fff3e0; color:#e65100; }
    .profile-status-inactive { background:#f1f3f5; color:#59636e; }
    .user-profile-meta { display:grid; gap:10px; padding:16px 20px; border-top:1px solid var(--border); color:var(--text-muted); font-size:.82rem; }
    .user-profile-meta div { display:flex; gap:8px; align-items:flex-start; }
    .user-profile-meta svg.icon { width:16px; margin-top:2px; color:var(--info); text-align:center; }
    .user-profile-card-header { display:flex; align-items:center; gap:10px; }
    .user-profile-card-header svg.icon { color:var(--info); }
    .user-profile-rows { padding:8px 20px 18px; }
    .user-profile-row { display:grid; grid-template-columns:minmax(130px, .8fr) minmax(0, 1.2fr); gap:12px; padding:9px 0; border-bottom:1px solid #edf0f2; font-size:.84rem; }
    .user-profile-row:last-child { border-bottom:0; }
    .user-profile-label { color:var(--text-muted); }
    .user-profile-value { overflow-wrap:anywhere; color:var(--text); }
    .user-profile-empty { padding:14px 0; color:var(--text-muted); font-size:.85rem; }
    .education-table-wrap { overflow-x:auto; padding:0 16px 16px; }
    .education-table { width:100%; border-collapse:collapse; font-size:.78rem; }
    .education-table th, .education-table td { padding:9px 8px; border-bottom:1px solid #edf0f2; text-align:left; vertical-align:top; }
    .education-table th { color:var(--text-muted); font-size:.68rem; font-weight:700; }
    .education-table td { overflow-wrap:anywhere; }
    .parent-details-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:12px; padding:16px; }
    .parent-details-card { min-width:0; padding:14px; border:1px solid var(--border); border-radius:8px; }
    .parent-details-card h3 { margin-bottom:8px; color:var(--primary); font-size:.9rem; }
    .parent-details-card .user-profile-row { grid-template-columns:minmax(85px,.7fr) minmax(0,1.3fr); font-size:.78rem; }
    .parent-status-tags { display:flex; flex-wrap:wrap; gap:8px; padding:16px 20px; }
    .parent-status-tag { padding:6px 10px; border-radius:999px; background:#e3f2fd; color:#1565c0; font-size:.78rem; font-weight:700; }
    .special-skills-text { padding:16px 20px; color:var(--text); font-size:.88rem; line-height:1.6; white-space:pre-line; overflow-wrap:anywhere; }
    .profile-history { padding:6px 20px 12px; }
    .profile-history-item { display:grid; grid-template-columns:14px minmax(0,1fr) auto; gap:10px; align-items:start; padding:11px 0; border-bottom:1px solid #edf0f2; }
    .profile-history-item:last-child { border-bottom:0; }
    .profile-history-dot { width:10px; height:10px; margin-top:5px; border-radius:50%; background:var(--info); }
    .profile-history-title { font-size:.85rem; font-weight:600; }
    .profile-history-date { margin-top:4px; color:var(--text-muted); font-size:.75rem; }
    .profile-history-status { padding:4px 8px; border-radius:999px; background:#e3f2fd; color:#1565c0; font-size:.7rem; white-space:nowrap; }
    .user-profile-actions { display:grid; gap:9px; }
    .user-profile-actions .btn { justify-content:center; }
    @media (max-width: 767.98px) {
        .user-profile-layout { grid-template-columns:minmax(0,1fr); }
        .user-profile-identity { gap:12px; }
        .user-profile-avatar { flex-basis:60px; width:60px; height:60px; font-size:1.25rem; }
        .user-profile-row { grid-template-columns:minmax(100px,.75fr) minmax(0,1.25fr); gap:8px; }
        .user-profile-meta, .user-profile-rows { padding-left:16px; padding-right:16px; }
        .profile-history { padding-left:16px; padding-right:16px; }
        .profile-history-item { grid-template-columns:12px minmax(0,1fr); }
        .profile-history-status { grid-column:2; justify-self:start; }
        .parent-details-grid { grid-template-columns:minmax(0,1fr); padding:12px; }
        .education-table-wrap { padding:0 12px 12px; }
        .education-table { min-width:560px; }
        .parent-status-tags, .special-skills-text { padding-left:16px; padding-right:16px; }
    }
</style>
@endsection

@section('content')
<div class="user-profile-back">
    <a class="btn btn-outline btn-sm" href="{{ route('admin.users', array_filter(['search' => $returnSearch])) }}">
        <x-icon class="fa-solid fa-arrow-left" /> Back to Users
    </a>
</div>

<div class="user-profile-layout">
    <div class="user-profile-main">
        <section class="card">
            <div class="card-body user-profile-identity">
                <x-admin.user-avatar :user="$user" class="user-profile-avatar" />
                <div class="user-profile-name">
                    <h2>{{ $fullName }}</h2>
                    <div class="user-profile-email">{{ $user->email }}</div>
                    <span class="user-profile-role">{{ \App\Models\User::applicantCategoryLabel($profile?->applicant_category ?: $user->applicant_category) ?: 'Applicant' }}</span>
                </div>
                <span class="profile-status {{ $statusClass }}">{{ $statusLabel }}</span>
            </div>
            <div class="user-profile-meta">
                <div><x-icon class="fa-solid fa-fingerprint" /><span>User ID: #USR-{{ str_pad((string) $user->id, 3, '0', STR_PAD_LEFT) }}</span></div>
                <div><x-icon class="fa-solid fa-calendar-days" /><span>Registered @adminDate($user->created_at)</span></div>
                <div><x-icon class="fa-solid fa-phone" /><span>{{ filled($phone) ? $phone : 'No phone provided' }}</span></div>
                <div><x-icon class="fa-solid fa-location-dot" /><span>{{ filled($address) ? $address : 'No address provided' }}</span></div>
                <div><x-icon class="fa-solid fa-clock" /><span>{{ $user->activityDescription() }}</span></div>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="user-profile-card-header"><x-icon class="fa-solid fa-user" /> Personal Information</h2></div>
            <div class="user-profile-rows">
                @if(collect($personalRows)->contains(fn ($row) => filled($row[1])))
                    @foreach($personalRows as [$label, $value])
                        <div class="user-profile-row"><span class="user-profile-label">{{ $label }}</span><span class="user-profile-value">{{ $formatProfileValue($value) }}</span></div>
                    @endforeach
                @else
                    <div class="user-profile-empty">No information on file</div>
                @endif
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="user-profile-card-header"><x-icon class="fa-solid fa-graduation-cap" /> Education Background</h2></div>
            @if($educationRows->isEmpty())
                <div class="user-profile-rows"><div class="user-profile-empty">No information on file</div></div>
            @else
                <div class="education-table-wrap">
                    <table class="education-table">
                        <thead><tr><th>Education</th><th>Name of School</th><th>Course</th><th>Year Level</th><th>Date of Attendance</th></tr></thead>
                        <tbody>
                            @foreach($educationRows as $row)
                                <tr>
                                    <td>{{ $row['level'] }}</td>
                                    <td>{{ $formatProfileValue($row['school']) }}</td>
                                    <td>{{ $formatProfileValue($row['course']) }}</td>
                                    <td>{{ $formatProfileValue($row['year_level']) }}</td>
                                    <td>{{ $formatProfileValue($row['date_attended']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="card">
            <div class="card-header"><h2 class="user-profile-card-header"><x-icon class="fa-solid fa-people-group" /> Parents Information</h2></div>
            @if(collect([$fatherName, $fatherContact, $fatherOccupation, $motherName, $motherContact, $motherOccupation])->contains(fn ($value) => filled($value)))
                <div class="parent-details-grid">
                    <div class="parent-details-card">
                        <h3>Father</h3>
                        @foreach([['Name', $fatherName], ['Contact No.', $fatherContact], ['Occupation', $fatherOccupation]] as [$label, $value])
                            <div class="user-profile-row"><span class="user-profile-label">{{ $label }}</span><span class="user-profile-value">{{ $formatProfileValue($value) }}</span></div>
                        @endforeach
                    </div>
                    <div class="parent-details-card">
                        <h3>Mother</h3>
                        @foreach([['Name', $motherName], ['Contact No.', $motherContact], ['Occupation', $motherOccupation]] as [$label, $value])
                            <div class="user-profile-row"><span class="user-profile-label">{{ $label }}</span><span class="user-profile-value">{{ $formatProfileValue($value) }}</span></div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="user-profile-rows"><div class="user-profile-empty">No information on file</div></div>
            @endif
        </section>

        <section class="card">
            <div class="card-header"><h2 class="user-profile-card-header"><x-icon class="fa-solid fa-people-roof" /> Current Status of Parents</h2></div>
            @if(count($selectedParentStatuses))
                <div class="parent-status-tags">
                    @foreach($selectedParentStatuses as $status)
                        <span class="parent-status-tag">{{ $status }}</span>
                    @endforeach
                </div>
            @else
                <div class="user-profile-rows"><div class="user-profile-empty">Not specified</div></div>
            @endif
        </section>

        <section class="card">
            <div class="card-header"><h2 class="user-profile-card-header"><x-icon class="fa-solid fa-wand-magic-sparkles" /> Special Skills</h2></div>
            @if(filled($specialSkills))
                <p class="special-skills-text">{{ $specialSkills }}</p>
            @else
                <div class="user-profile-rows"><div class="user-profile-empty">No information on file</div></div>
            @endif
        </section>

        <section class="card">
            <div class="card-header"><h2 class="user-profile-card-header"><x-icon class="fa-solid fa-briefcase" /> Program Information</h2></div>
            <div class="user-profile-rows">
                @if(collect($programRows)->contains(fn ($row) => filled($row[1])))
                    @foreach($programRows as [$label, $value])
                        @if(filled($value))
                            <div class="user-profile-row"><span class="user-profile-label">{{ $label }}</span><span class="user-profile-value">{{ $value }}</span></div>
                        @endif
                    @endforeach
                @else
                    <div class="user-profile-empty">No information on file</div>
                @endif
            </div>
        </section>
    </div>

    <div class="user-profile-side">
        <section class="card">
            <div class="card-header"><h2 class="user-profile-card-header"><x-icon class="fa-solid fa-shield-halved" /> Additional Details</h2></div>
            <div class="user-profile-rows">
                @if(collect($additionalRows)->contains(fn ($row) => filled($row[1])))
                    @foreach($additionalRows as [$label, $value])
                        <div class="user-profile-row"><span class="user-profile-label">{{ $label }}</span><span class="user-profile-value">{{ $formatProfileValue($value) }}</span></div>
                    @endforeach
                @else
                    <div class="user-profile-empty">No information on file</div>
                @endif
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="user-profile-card-header"><x-icon class="fa-solid fa-rectangle-list" /> Application History</h2></div>
            <div class="profile-history">
                @forelse($applications as $historyItem)
                    <div class="profile-history-item">
                        <span class="profile-history-dot"></span>
                        <div>
                            <div class="profile-history-title">Application {{ ucfirst($historyItem->status) }}</div>
                            <div class="profile-history-date">@adminDate($historyItem->created_at, true)</div>
                        </div>
                        <span class="profile-history-status">{{ ucfirst($historyItem->status) }}</span>
                    </div>
                @empty
                    <div class="user-profile-empty">No information on file</div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection