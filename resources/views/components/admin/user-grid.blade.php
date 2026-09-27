@props(['users'])

<div class="user-grid" id="userGrid">
    @forelse($users as $user)
        <x-admin.user-card :user="$user" />
    @empty
        <div class="users-empty">No users registered yet.</div>
    @endforelse
</div>