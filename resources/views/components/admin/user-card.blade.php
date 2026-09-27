@props(['user'])

@php
    $application = $user->applications->first();
    $statusLabel = $user->displayStatus($application?->status);
    $statusClass = match ($statusLabel) {
        'Active' => 'user-status-active',
        'Pending' => 'user-status-pending',
        default => 'user-status-inactive',
    };
    $profile = $user->profile;
    $category = $profile?->applicant_category ?: $user->applicant_category;
    $role = \App\Models\User::applicantCategoryLabel($category) ?: 'Applicant';
    $searchText = strtolower($user->id . ' ' . $user->name . ' ' . $user->email);
    $phone = filled($profile?->contact_number) ? $profile->contact_number : $user->contact_number;
    $presentAddress = filled($profile?->present_address) ? $profile->present_address : $user->present_address;
    $permanentAddress = filled($profile?->permanent_address) ? $profile->permanent_address : $user->permanent_address;
    $address = filled($presentAddress) ? $presentAddress : $permanentAddress;
@endphp

<article class="user-card" data-user-card data-search="{{ $searchText }}">
    <div class="user-card-top">
        <x-admin.user-avatar :user="$user" />
        <div class="user-card-heading">
            <div class="user-card-name">{{ $user->name }}</div>
            <span class="user-role">{{ $role }}</span>
        </div>
        <div class="user-card-status">
            <span class="user-status {{ $statusClass }}">{{ $statusLabel }}</span>
            @if($user->last_active_at)
                <small>{{ $user->activityDescription() }}</small>
            @endif
        </div>
    </div>

    <div class="user-contact-list">
        <div class="user-contact-row"><i class="fa-solid fa-envelope" aria-hidden="true"></i><span>{{ $user->email }}</span></div>
        <div class="user-contact-row"><i class="fa-solid fa-phone" aria-hidden="true"></i><span>{{ filled($phone) ? $phone : 'No phone provided' }}</span></div>
        <div class="user-contact-row"><i class="fa-solid fa-location-dot" aria-hidden="true"></i><span>{{ filled($address) ? $address : 'No address provided' }}</span></div>
    </div>

    <div class="user-card-footer">
        <span class="user-registered">Registered {{ $user->created_at->format('M d, Y') }}</span>
        <div class="user-actions">
            <a class="user-action" data-user-view href="{{ route('admin.users.show', ['user' => $user->id]) }}">
                <i class="fa-solid fa-eye" aria-hidden="true"></i> View
            </a>
        </div>
    </div>
</article>