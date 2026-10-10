@extends('layouts.admin')

@section('title', $appointment->exists ? 'Edit Appointment' : 'New Appointment')
@section('page-title', $appointment->exists ? 'Edit Appointment' : 'New Appointment')
@section('page-sub', 'Add the schedule details applicants will see in their portal')

@section('content')
@php
    $selectedApplicantIds = old(
        'target_user_ids',
        $appointment->exists && $appointment->target_audience === 'multiple_applicants'
            ? $appointment->targetApplicants->pluck('id')->all()
            : [],
    );
    $selectedApplicantIds = is_array($selectedApplicantIds)
        ? array_map('strval', $selectedApplicantIds)
        : [];
    $selectedSingleApplicantId = (string) old('target_user_id', $appointment->target_user_id ?? '');
    $selectedSingleApplicant = $applicants->firstWhere('id', (int) $selectedSingleApplicantId);
@endphp
<style>
    .appointment-card { overflow: hidden; border: 1px solid var(--border); border-radius: 14px; box-shadow: 0 12px 34px rgba(17,24,39,.08); }
    .appointment-header { display: flex; align-items: center; gap: 14px; padding: 22px 26px; background: linear-gradient(115deg, rgba(139,0,0,.07), transparent 72%); }
    .appointment-header-icon { display: grid; width: 46px; height: 46px; flex: 0 0 46px; place-items: center; border-radius: 13px; background: var(--primary); color: #fff; font-size: 1.15rem; box-shadow: 0 5px 14px rgba(139,0,0,.2); }
    .appointment-header h2 { color: var(--primary); font-size: 1.15rem; font-weight: 750; }
    .appointment-header p { margin-top: 4px; color: var(--text-muted); font-size: .86rem; }
    .appointment-body { padding: 26px; }
    .appointment-form { display: grid; gap: 25px; max-width: 1120px; margin: 0 auto; }
    .appointment-section { display: grid; gap: 17px; }
    .appointment-section-heading { display: flex; align-items: center; gap: 10px; padding-bottom: 11px; border-bottom: 1px solid var(--border); color: var(--text); font-size: .98rem; font-weight: 750; }
    .appointment-section-heading svg.icon { color: var(--primary); }
    .appointment-section-heading small { margin-left: auto; color: var(--text-muted); font-size: .75rem; font-weight: 500; }
    .appointment-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 17px; }
    .appointment-field { min-width: 0; }
    .appointment-field-wide { grid-column: 1 / -1; }
    .appointment-field label { display: block; margin-bottom: 7px; color: var(--text); font-size: .86rem; font-weight: 650; }
    .appointment-field label .required-mark { color: var(--danger); }
    .appointment-field input:not([type="hidden"]), .appointment-field select, .appointment-field textarea,
    .applicant-picker-search {
        display: block; width: 100%; min-height: 46px; padding: 11px 13px; border: 1px solid var(--border);
        border-radius: 9px; background: var(--white); color: var(--text); font: inherit; font-size: .91rem;
        transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
    }
    .appointment-field textarea { min-height: 118px; resize: vertical; line-height: 1.5; }
    .appointment-field input:focus, .appointment-field select:focus, .appointment-field textarea:focus,
    .applicant-picker-search:focus { border-color: var(--primary); outline: 0; box-shadow: 0 0 0 3px rgba(139,0,0,.12); }
    .appointment-field input::placeholder, .appointment-field textarea::placeholder, .applicant-picker-search::placeholder { color: var(--text-muted); opacity: .8; }
    .appointment-field-hint { display: block; margin-top: 6px; color: var(--text-muted); font-size: .78rem; line-height: 1.45; }
    .appointment-error { margin-top: 6px; color: var(--danger); font-size: .8rem; }
    .appointment-notice { display: flex; align-items: flex-start; gap: 11px; padding: 13px 15px; border: 1px solid rgba(21,101,192,.18); border-radius: 9px; background: rgba(21,101,192,.06); color: var(--text-muted); font-size: .82rem; line-height: 1.5; }
    .appointment-notice svg.icon { margin-top: 2px; color: var(--info); }
    .appointment-form-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 10px; padding-top: 18px; border-top: 1px solid var(--border); }
    .appointment-form-actions .btn { min-height: 42px; padding: 10px 18px; border-radius: 8px; font-weight: 700; }
    .applicant-picker { position: relative; }
    .applicant-picker-selected { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 9px; }
    .applicant-picker-chip { display: inline-flex; align-items: center; gap: 7px; max-width: 100%; padding: 6px 10px; border: 1px solid rgba(139,0,0,.12); border-radius: 99px; background: rgba(139,0,0,.07); color: var(--primary); font-size: .78rem; font-weight: 650; overflow-wrap: anywhere; }
    .applicant-picker-chip button { display: grid; width: 18px; height: 18px; place-items: center; padding: 0; border: 0; border-radius: 50%; background: rgba(139,0,0,.12); color: inherit; font: inherit; cursor: pointer; }
    .applicant-picker-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-top: 10px; }
    .applicant-picker-actions button { padding: 0; border: 0; background: transparent; color: var(--primary); font: inherit; font-size: .78rem; font-weight: 650; cursor: pointer; }
    .applicant-picker-actions button:hover { text-decoration: underline; }
    .applicant-picker-actions button:disabled { opacity: .45; cursor: not-allowed; }
    .applicant-picker-count { margin-left: auto; color: var(--text-muted); font-size: .78rem; }
    .applicant-picker-results { position: absolute; z-index: 20; top: calc(100% + 5px); right: 0; left: 0; max-height: 260px; overflow-y: auto; padding: 5px; border: 1px solid var(--border); border-radius: 10px; background: var(--white); box-shadow: 0 12px 28px rgba(17,24,39,.16); }
    .applicant-picker-results[hidden] { display: none; }
    .applicant-picker-option { display: flex; width: 100%; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 11px; border: 0; border-radius: 7px; background: transparent; color: var(--text); font: inherit; text-align: left; cursor: pointer; }
    .applicant-picker-option[hidden] { display: none; }
    .applicant-picker-option:hover,.applicant-picker-option:focus { outline: 0; background: rgba(139,0,0,.07); color: var(--primary); }
    .applicant-picker-option small { display: block; margin-top: 3px; color: var(--text-muted); font-size: .75rem; }
    .applicant-picker-option [data-option-state] { color: var(--success); font-size: .76rem; font-weight: 700; }
    .applicant-picker-empty { padding: 12px; color: var(--text-muted); font-size: .82rem; }
    @media(max-width:700px) {
        .appointment-header { padding: 18px; }
        .appointment-body { padding: 19px; }
        .appointment-fields { grid-template-columns: 1fr; gap: 14px; }
        .appointment-field-wide { grid-column: auto; }
        .appointment-section-heading small { display: none; }
        .appointment-form-actions { justify-content: stretch; }
        .appointment-form-actions .btn { flex: 1; text-align: center; }
    }
</style>
<div class="card appointment-card">
    <div class="card-header appointment-header">
        <span class="appointment-header-icon" aria-hidden="true"><x-icon class="fa-solid fa-calendar-plus" /></span>
        <div>
            <h2>{{ $appointment->exists ? 'Update appointment details' : 'Create an appointment' }}</h2>
            <p>Set when applicants need to attend and who should receive the reminder.</p>
        </div>
    </div>
    <div class="card-body appointment-body">
        <form class="appointment-form" method="POST" action="{{ $appointment->exists ? route('admin.appointments.update', $appointment) : route('admin.appointments.store') }}">
            @csrf
            @if($appointment->exists)
                @method('PUT')
            @endif

            <section class="appointment-section" aria-labelledby="appointment-details-heading">
                <div class="appointment-section-heading" id="appointment-details-heading">
                    <x-icon class="fa-regular fa-clipboard" aria-hidden="true" />
                    <span>Appointment details</span>
                    <small>Required fields are marked <span class="required-mark">*</span></small>
                </div>
                <div class="appointment-fields">
                    <div class="appointment-field">
                        <label for="title">Appointment title <span class="required-mark">*</span></label>
                        <input id="title" name="title" type="text" required maxlength="255" placeholder="e.g. Document verification" value="{{ old('title', $appointment->title) }}">
                        @error('title')<div class="appointment-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="appointment-field">
                        <label for="location">Location</label>
                        <input id="location" name="location" type="text" maxlength="255" placeholder="e.g. PESO Office, Lal-lo" value="{{ old('location', $appointment->location) }}">
                        <small class="appointment-field-hint">Add the office or venue where the applicant should go.</small>
                        @error('location')<div class="appointment-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="appointment-field appointment-field-wide">
                        <label for="starts_at">Required attendance date and time <span class="required-mark">*</span></label>
                        <input id="starts_at" name="starts_at" type="datetime-local" required value="{{ old('starts_at', $appointment->starts_at?->format('Y-m-d\TH:i')) }}">
                        <small class="appointment-field-hint">This is the exact date and time shown in the applicant's appointment reminder.</small>
                        @error('starts_at')<div class="appointment-error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="appointment-field">
                    <label for="description">Instructions <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
                    <textarea id="description" name="description" rows="4" maxlength="5000" placeholder="Tell applicants what to bring or any steps they should follow.">{{ old('description', $appointment->description) }}</textarea>
                    @error('description')<div class="appointment-error">{{ $message }}</div>@enderror
                </div>
            </section>
            <section class="appointment-section" aria-labelledby="appointment-recipients-heading">
                <div class="appointment-section-heading" id="appointment-recipients-heading">
                    <x-icon class="fa-solid fa-users" aria-hidden="true" />
                    <span>Choose recipients</span>
                </div>
                <div class="appointment-fields">
                    <div class="appointment-field appointment-field-wide">
                        <label for="target_audience">Who should receive this appointment? <span class="required-mark">*</span></label>
                        <select id="target_audience" name="target_audience" required>
                        <option value="all_applicants" @selected(old('target_audience', $appointment->target_audience) === 'all_applicants')>All applicants</option>
                        <option value="approved_applicants" @selected(old('target_audience', $appointment->target_audience) === 'approved_applicants')>Approved applicants</option>
                        <option value="pending_applicants" @selected(old('target_audience', $appointment->target_audience) === 'pending_applicants')>Pending — not yet approved</option>
                        <option value="denied_applicants" @selected(old('target_audience', $appointment->target_audience) === 'denied_applicants')>Denied applicants</option>
                        <option value="specific_applicant" @selected(old('target_audience', $appointment->target_audience) === 'specific_applicant')>One specific applicant</option>
                        <option value="multiple_applicants" @selected(old('target_audience', $appointment->target_audience) === 'multiple_applicants')>Multiple specific applicants</option>
                    </select>
                    <small class="appointment-field-hint">Choose everyone, applicants by status, or specific people.</small>
                    @error('target_audience')<div class="appointment-error">{{ $message }}</div>@enderror
                </div>
                <div class="appointment-field appointment-field-wide" id="appointment-applicant-field" @if(old('target_audience', $appointment->target_audience) !== 'specific_applicant') hidden @endif>
                    <label for="target-user-search">Select applicant <span class="required-mark">*</span></label>
                    <div class="applicant-picker" data-applicant-picker data-mode="single">
                        <input id="target-user-search" class="applicant-picker-search" type="search" placeholder="Search by name" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="single-applicant-results">
                        <input id="target_user_id" type="hidden" name="target_user_id" value="{{ $selectedSingleApplicantId }}">
                        <div class="applicant-picker-selected" data-selected-list>
                            @if($selectedSingleApplicant)
                                <span class="applicant-picker-chip" data-selected-id="{{ $selectedSingleApplicant->id }}">
                                    {{ $selectedSingleApplicant->name }}
                                    <button type="button" aria-label="Remove {{ $selectedSingleApplicant->name }}" data-remove-applicant>&times;</button>
                                </span>
                            @endif
                        </div>
                        <div id="single-applicant-results" class="applicant-picker-results" role="listbox" hidden>
                            @foreach($applicants as $applicant)
                                <button type="button" class="applicant-picker-option" role="option" data-applicant-option data-id="{{ $applicant->id }}" data-name="{{ $applicant->name }}" data-email="{{ $applicant->email }}">
                                    <span>{{ $applicant->name }}<small>{{ $applicant->email }}</small></span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div id="target-user-error">
                        @error('target_user_id')<div class="appointment-error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="appointment-field appointment-field-wide" id="appointment-applicants-field" @if(old('target_audience', $appointment->target_audience) !== 'multiple_applicants') hidden @endif>
                    <label for="target-users-search">Select applicants <span class="required-mark">*</span></label>
                    <div class="applicant-picker" data-applicant-picker data-mode="multiple" data-minimum="2">
                        <input id="target-users-search" class="applicant-picker-search" type="search" placeholder="Search by name" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="multiple-applicant-results">
                        <div class="applicant-picker-selected" data-selected-list>
                            @foreach($applicants->whereIn('id', array_map('intval', $selectedApplicantIds)) as $applicant)
                                <span class="applicant-picker-chip" data-selected-id="{{ $applicant->id }}">
                                    {{ $applicant->name }}
                                    <button type="button" aria-label="Remove {{ $applicant->name }}" data-remove-applicant>&times;</button>
                                </span>
                                <input type="hidden" name="target_user_ids[]" value="{{ $applicant->id }}" data-selected-input>
                            @endforeach
                        </div>
                        <div class="applicant-picker-actions">
                            <button type="button" data-select-all>Select all applicants</button>
                            <button type="button" data-clear-selection>Clear selection</button>
                            <span class="applicant-picker-count" data-selection-count aria-live="polite"></span>
                        </div>
                        <div id="multiple-applicant-results" class="applicant-picker-results" role="listbox" aria-multiselectable="true" hidden>
                            @foreach($applicants as $applicant)
                                <button type="button" class="applicant-picker-option" role="option" data-applicant-option data-id="{{ $applicant->id }}" data-name="{{ $applicant->name }}" data-email="{{ $applicant->email }}">
                                    <span>{{ $applicant->name }}<small>{{ $applicant->email }}</small></span>
                                    <span data-option-state aria-hidden="true"></span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <small class="appointment-field-hint">Select at least two applicants. Search by name, or use Select all.</small>
                    <div id="target-users-error">
                        @error('target_user_ids')<div class="appointment-error">{{ $message }}</div>@enderror
                        @error('target_user_ids.*')<div class="appointment-error">{{ $message }}</div>@enderror
                    </div>
                </div>
                @if($applicants->isEmpty())
                    <small class="appointment-field-hint" style="grid-column:1 / -1;">There are no applicant accounts to select.</small>
                @endif
                </div>
                <div class="appointment-notice">
                    <x-icon class="fa-solid fa-circle-info" aria-hidden="true" />
                    <span>Status-based appointments go to applicants whose latest application matches the selected status. New appointments are saved as drafts; publish them from the appointment list when they are ready.</span>
                </div>
            </section>
            <div class="appointment-form-actions">
                <button type="submit" class="btn btn-primary">{{ $appointment->exists ? 'Save Changes' : 'Save Draft' }}</button>
                <a href="{{ route('admin.appointments.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
<script>
    (() => {
        const audience = document.getElementById('target_audience');
        const singleApplicantField = document.getElementById('appointment-applicant-field');
        const multipleApplicantsField = document.getElementById('appointment-applicants-field');

        document.querySelectorAll('[data-applicant-picker]').forEach((picker) => {
            const mode = picker.dataset.mode;
            const search = picker.querySelector('.applicant-picker-search');
            const results = picker.querySelector('.applicant-picker-results');
            const selectedList = picker.querySelector('[data-selected-list]');
            const options = Array.from(picker.querySelectorAll('[data-applicant-option]'));
            const singleInput = picker.querySelector('input[name="target_user_id"]');
            const selectAllButton = picker.querySelector('[data-select-all]');
            const clearSelectionButton = picker.querySelector('[data-clear-selection]');
            const selectionCount = picker.querySelector('[data-selection-count]');
            const minimum = Number(picker.dataset.minimum || 0);
            let activeIndex = -1;

            const selectedIds = () => new Set(
                mode === 'single'
                    ? (singleInput.value ? [singleInput.value] : [])
                    : Array.from(selectedList.querySelectorAll('[data-selected-input]'), (input) => input.value),
            );

            const renderResults = () => {
                const query = search.value.trim().toLocaleLowerCase();
                const selected = selectedIds();
                let visibleCount = 0;
                if (selectionCount) {
                    selectionCount.textContent = `${selected.size} selected`;
                }
                if (selectAllButton) {
                    selectAllButton.disabled = selected.size === options.length;
                }
                if (clearSelectionButton) {
                    clearSelectionButton.disabled = selected.size === 0;
                }

                options.forEach((option) => {
                    const matches = option.dataset.name.toLocaleLowerCase().includes(query);
                    const isSelected = selected.has(option.dataset.id);
                    option.hidden = !matches;
                    option.setAttribute('aria-selected', String(isSelected));
                    const state = option.querySelector('[data-option-state]');
                    if (state) state.textContent = isSelected ? 'Selected' : '';
                    if (matches) visibleCount++;
                });

                let empty = results.querySelector('[data-empty-results]');
                if (visibleCount === 0) {
                    if (!empty) {
                        empty = document.createElement('div');
                        empty.className = 'applicant-picker-empty';
                        empty.dataset.emptyResults = '';
                        empty.textContent = 'No matching applicants found.';
                        results.append(empty);
                    }
                } else {
                    empty?.remove();
                }

                results.hidden = false;
                search.setAttribute('aria-expanded', 'true');
                activeIndex = -1;
            };

            const closeResults = () => {
                results.hidden = true;
                search.setAttribute('aria-expanded', 'false');
                activeIndex = -1;
            };

            const createChip = (id, name) => {
                const chip = document.createElement('span');
                chip.className = 'applicant-picker-chip';
                chip.dataset.selectedId = id;
                chip.append(document.createTextNode(name));
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.dataset.removeApplicant = '';
                remove.setAttribute('aria-label', `Remove ${name}`);
                remove.textContent = '×';
                chip.append(remove);
                return chip;
            };

            const selectOption = (option) => {
                const id = option.dataset.id;
                const name = option.dataset.name;
                const selected = selectedIds();

                if (mode === 'single') {
                    singleInput.value = id;
                    selectedList.replaceChildren(createChip(id, name));
                    search.value = '';
                    closeResults();
                    return;
                }

                if (selected.has(id)) {
                    selectedList.querySelector(`[data-selected-id="${CSS.escape(id)}"]`)?.remove();
                    selectedList.querySelector(`[data-selected-input][value="${CSS.escape(id)}"]`)?.remove();
                } else {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'target_user_ids[]';
                    hiddenInput.value = id;
                    hiddenInput.dataset.selectedInput = '';
                    selectedList.append(createChip(id, name), hiddenInput);
                }

                search.value = '';
                renderResults();
                search.focus();
            };

            search.addEventListener('focus', renderResults);
            search.addEventListener('input', renderResults);
            search.addEventListener('keydown', (event) => {
                const visibleOptions = options.filter((option) => !option.hidden);
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault();
                    renderResults();
                    if (visibleOptions.length === 0) return;
                    activeIndex = (activeIndex + (event.key === 'ArrowDown' ? 1 : -1) + visibleOptions.length) % visibleOptions.length;
                    visibleOptions[activeIndex].focus();
                } else if (event.key === 'Escape') {
                    closeResults();
                } else if (event.key === 'Enter' && visibleOptions.length === 1) {
                    event.preventDefault();
                    selectOption(visibleOptions[0]);
                }
            });

            options.forEach((option) => {
                option.addEventListener('click', () => selectOption(option));
                option.addEventListener('keydown', (event) => {
                    const visibleOptions = options.filter((candidate) => !candidate.hidden);
                    const index = visibleOptions.indexOf(option);
                    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                        event.preventDefault();
                        const offset = event.key === 'ArrowDown' ? 1 : -1;
                        visibleOptions[(index + offset + visibleOptions.length) % visibleOptions.length]?.focus();
                    } else if (event.key === 'Escape') {
                        closeResults();
                        search.focus();
                    }
                });
            });

            selectAllButton?.addEventListener('click', () => {
                selectedList.replaceChildren();
                options.forEach((option) => {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'target_user_ids[]';
                    hiddenInput.value = option.dataset.id;
                    hiddenInput.dataset.selectedInput = '';
                    selectedList.append(createChip(option.dataset.id, option.dataset.name), hiddenInput);
                });
                renderResults();
            });

            clearSelectionButton?.addEventListener('click', () => {
                selectedList.replaceChildren();
                renderResults();
            });

            selectedList.addEventListener('click', (event) => {
                const removeButton = event.target.closest('[data-remove-applicant]');
                if (!removeButton) return;

                const chip = removeButton.closest('[data-selected-id]');
                const id = chip.dataset.selectedId;
                chip.remove();
                if (mode === 'single') {
                    singleInput.value = '';
                } else {
                    selectedList.querySelector(`[data-selected-input][value="${CSS.escape(id)}"]`)?.remove();
                    renderResults();
                }
                search.focus();
            });

            document.addEventListener('click', (event) => {
                if (!picker.contains(event.target)) closeResults();
            });

            if (mode === 'multiple') {
                picker.closest('form').addEventListener('submit', (event) => {
                    if (!multipleApplicantsField.hidden && selectedIds().size < minimum) {
                        event.preventDefault();
                        search.setCustomValidity(`Select at least ${minimum} applicants.`);
                        search.reportValidity();
                    } else {
                        search.setCustomValidity('');
                    }
                });
                search.addEventListener('input', () => search.setCustomValidity(''));
            }
        });

        const updateApplicantFields = () => {
            const isSpecificApplicant = audience.value === 'specific_applicant';
            const areMultipleApplicants = audience.value === 'multiple_applicants';
            singleApplicantField.hidden = !isSpecificApplicant;
            multipleApplicantsField.hidden = !areMultipleApplicants;
        };

        audience.addEventListener('change', updateApplicantFields);
        updateApplicantFields();
    })();
</script>
@endsection
