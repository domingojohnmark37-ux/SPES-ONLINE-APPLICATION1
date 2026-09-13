@extends('layouts.admin')

@section('title', 'Settings')
@section('page-title', 'Settings')
@section('page-sub', 'Manage admin preferences and portal settings')

@section('content')

{{-- Application Period Settings --}}
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <h2><i class="fa-solid fa-calendar-days"></i> Application Period</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            @method('PUT')
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text); font-size: .95rem;">
                        Start of Submission
                    </label>
                    <input type="datetime-local" 
                           name="application_start_date"
                           value="{{ $settings->application_start_date ? $settings->application_start_date->format('Y-m-d\TH:i') : '' }}"
                           style="width: 100%; padding: 10px 13px; border: 1.5px solid var(--border); border-radius: 7px; font-size: .95rem; outline: none; transition: border-color .2s;" 
                           onfocus="this.style.borderColor='var(--primary)'" 
                           onblur="this.style.borderColor='var(--border)'">
                    @error('application_start_date')
                        <div style="color: var(--danger); font-size: .8rem; margin-top: 4px;"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                    @enderror
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--text); font-size: .95rem;">
                        End of Submission
                    </label>
                    <input type="datetime-local" 
                           name="application_end_date"
                           value="{{ $settings->application_end_date ? $settings->application_end_date->format('Y-m-d\TH:i') : '' }}"
                           style="width: 100%; padding: 10px 13px; border: 1.5px solid var(--border); border-radius: 7px; font-size: .95rem; outline: none; transition: border-color .2s;" 
                           onfocus="this.style.borderColor='var(--primary)'" 
                           onblur="this.style.borderColor='var(--border)'">
                    @error('application_end_date')
                        <div style="color: var(--danger); font-size: .8rem; margin-top: 4px;"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="display: flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-floppy-disk"></i> Save Changes
            </button>
        </form>
    </div>
</div>

@endsection
