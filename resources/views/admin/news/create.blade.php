@extends('layouts.admin')

@section('title', 'Create News')
@section('page-title', 'Create New Announcement')
@section('page-sub', 'Write and publish a new news article')

@section('content')

<div class="card">
    <div class="card-header">
        <h2><i class="fa-solid fa-pen-to-square"></i> New Announcement</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.news.store') }}">
            @csrf

            {{-- Title Field --}}
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text); font-size: .95rem;">
                    Title <span style="color: var(--danger);">*</span>
                </label>
                <input type="text" 
                       name="title"
                       placeholder="Enter announcement title"
                       value="{{ old('title') }}"
                       style="width: 100%; padding: 10px 13px; border: 1.5px solid {{ $errors->has('title') ? 'var(--danger)' : 'var(--border)' }}; border-radius: 7px; font-size: .95rem; outline: none; transition: border-color .2s;" 
                       onfocus="this.style.borderColor='var(--primary)'" 
                       onblur="this.style.borderColor='{{ $errors->has('title') ? 'var(--danger)' : 'var(--border)' }}'">
                @error('title')
                    <div style="color: var(--danger); font-size: .8rem; margin-top: 4px;"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                @enderror
            </div>

            {{-- Content Field --}}
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text); font-size: .95rem;">
                    Content <span style="color: var(--danger);">*</span>
                </label>
                <textarea name="content" 
                          placeholder="Write your announcement content here..."
                          rows="10"
                          style="width: 100%; padding: 10px 13px; border: 1.5px solid {{ $errors->has('content') ? 'var(--danger)' : 'var(--border)' }}; border-radius: 7px; font-size: .95rem; font-family: inherit; outline: none; transition: border-color .2s; resize: vertical;"
                          onfocus="this.style.borderColor='var(--primary)'" 
                          onblur="this.style.borderColor='{{ $errors->has('content') ? 'var(--danger)' : 'var(--border)' }}'">{{ old('content') }}</textarea>
                @error('content')
                    <div style="color: var(--danger); font-size: .8rem; margin-top: 4px;"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                @enderror
                <small style="color: var(--text-muted); display: block; margin-top: 6px;">
                    You can format your text with HTML or plain text. The article will be saved as a draft first.
                </small>
            </div>

                {{-- Announcement Destination Field --}}
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text); font-size: .95rem;">
                        Show Announcement On <span style="color: var(--danger);">*</span>
                    </label>
                    <select name="display_on" style="width: 100%; padding: 10px 13px; border: 1.5px solid {{ $errors->has('display_on') ? 'var(--danger)' : 'var(--border)' }}; border-radius: 7px; font-size: .95rem; background: #fff;">
                        <option value="both" {{ old('display_on', 'both') === 'both' ? 'selected' : '' }}>Web Page and Student Portal</option>
                        <option value="landing" {{ old('display_on') === 'landing' ? 'selected' : '' }}>Web Page Only</option>
                        <option value="portal" {{ old('display_on') === 'portal' ? 'selected' : '' }}>Student Portal Only</option>
                    </select>
                    @error('display_on')
                        <div style="color: var(--danger); font-size: .8rem; margin-top: 4px;"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                    @enderror
                </div>

            {{-- Form Actions --}}
            <div style="display: flex; gap: 12px; align-items: center;">
                <button type="submit" class="btn btn-primary" style="display: flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-floppy-disk"></i> Save as Draft
                </button>
                <a href="{{ route('admin.news.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection
