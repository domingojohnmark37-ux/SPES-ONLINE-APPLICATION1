@extends('layouts.admin')

@section('title', 'Additional Requirements')
@section('page-title', 'Additional Requirements')
@section('page-sub', 'Choose which additional documents applicants must submit')

@section('styles')
<style>
    .requirement-create-card { margin-bottom: 22px; overflow: hidden; border: 1px solid rgba(139,0,0,.1); box-shadow: 0 8px 28px rgba(17,24,39,.07); }
    .requirement-create-card .card-header { padding: 20px 26px; background: linear-gradient(110deg, #fff 0%, #fffafa 100%); }
    .requirement-create-heading { display: flex; align-items: center; gap: 14px; }
    .requirement-create-icon { display: grid; width: 46px; height: 46px; flex: 0 0 46px; place-items: center; border-radius: 14px; background: #fce8e8; color: var(--primary); font-size: 1.1rem; }
    .requirement-create-heading h2 { color: var(--primary); font-size: 1.06rem; font-weight: 750; }
    .requirement-create-heading p { margin-top: 5px; color: var(--text-muted); font-size: .84rem; }
    .requirement-create-card .card-body { padding: 26px; }
    .requirement-create-grid { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 20px 18px; align-items: end; }
    .requirement-field { display: flex; min-width: 0; flex-direction: column; gap: 7px; }
    .requirement-field > label { color: var(--text); font-size: .82rem; font-weight: 650; }
    .requirement-field > label span { color: var(--text-muted); font-size: .75rem; font-weight: 400; }
    .requirement-field input:not([type="file"]),
    .requirement-field select { width: 100%; min-height: 46px; padding: 10px 13px; border: 1px solid var(--border); border-radius: 9px; background: var(--white); color: var(--text); font: inherit; font-size: .88rem; transition: border-color .15s ease, box-shadow .15s ease; }
    .requirement-field input:focus,
    .requirement-field select:focus { border-color: var(--primary); outline: 0; box-shadow: 0 0 0 3px rgba(139,0,0,.1); }
    .requirement-field input[type="file"] { width: 100%; min-height: 42px; padding: 8px; border: 1px dashed var(--border); border-radius: 8px; background: var(--white); color: var(--text-muted); font: inherit; font-size: .8rem; }
    .requirement-field small { color: var(--text-muted); font-size: .74rem; line-height: 1.4; }
    .requirement-field-name,.requirement-field-instructions { grid-column: span 6; }
    .requirement-field-deadline,.requirement-field-audience,.requirement-options { grid-column: span 4; }
    .requirement-field-template { grid-column: 1 / -1; }
    .requirement-upload-section { display: grid; gap: 14px; padding: 18px; border: 1px solid var(--border); border-radius: 12px; background: #fafbfc; }
    .requirement-upload-heading { display: flex; align-items: flex-start; gap: 12px; }
    .requirement-upload-icon { display: grid; width: 38px; height: 38px; flex: 0 0 38px; place-items: center; border-radius: 11px; background: #fce8e8; color: var(--primary); }
    .requirement-upload-heading strong { display: block; color: var(--text); font-size: .88rem; }
    .requirement-upload-heading p { margin-top: 4px; color: var(--text-muted); font-size: .77rem; line-height: 1.45; }
    .requirement-upload-options { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .requirement-upload-choice { display: grid; gap: 8px; min-width: 0; padding: 13px; border: 1px solid var(--border); border-radius: 9px; background: var(--white); }
    .requirement-upload-choice label { color: var(--text); font-size: .8rem; font-weight: 650; }
    .requirement-upload-choice small { color: var(--text-muted); font-size: .73rem; }
    .requirement-upload-choice input[type="file"] { min-height: 44px; }
    .requirement-file-list { display: grid; gap: 5px; overflow-wrap: anywhere; }
    .requirement-file-list a { color: var(--primary); text-decoration: underline; }
    .requirement-options { display: flex; min-height: 46px; align-items: center; padding: 0 4px; }
    .requirement-checkbox { display: inline-flex; align-items: center; gap: 10px; color: var(--text); font-size: .86rem; font-weight: 600; cursor: pointer; }
    .requirement-checkbox input { width: 18px; height: 18px; margin: 0; accent-color: var(--primary); }
    .requirement-checkbox span { display: grid; gap: 2px; }
    .requirement-checkbox small { color: var(--text-muted); font-size: .72rem; font-weight: 400; }
    .requirement-form-actions { display: flex; grid-column: 1 / -1; justify-content: flex-end; padding-top: 18px; border-top: 1px solid var(--border); }
    .requirement-form-actions .btn { min-height: 46px; padding: 0 22px; border-radius: 9px; box-shadow: 0 4px 10px rgba(139,0,0,.18); }
    .requirement-create-card .field-error { color: var(--danger); font-size: .76rem; }
    .requirements-table { min-width: 920px; }
    .requirements-card .card-body { padding: 0; }
    .requirements-table thead { background: #f6f7f9; }
    .requirements-table th { padding: 13px 16px; }
    .requirements-table td { padding: 17px 16px; }
    .requirements-table tbody tr { transition: background-color .16s ease; }
    .requirements-table tbody tr:hover td { background: #fcfcfd; }
    .requirements-table .requirement-name { color: var(--text); font-size: .92rem; font-weight: 700; line-height: 1.4; }
    .requirements-table .requirement-description { max-width: 300px; margin-top: 5px; color: var(--text-muted); font-size: .8rem; line-height: 1.5; overflow-wrap: anywhere; }
    .requirements-table .requirement-meta { display: grid; gap: 8px; min-width: 190px; color: var(--text-muted); font-size: .8rem; }
    .requirements-table .requirement-meta svg.icon { width: 16px; color: var(--primary); }
    .requirements-table .requirement-edit-fields { display: grid; gap: 9px; min-width: 210px; }
    .requirements-table .requirement-edit-fields input:not([type="checkbox"]):not([type="file"]),
    .requirements-table .requirement-edit-fields select { width: 100%; min-height: 38px; padding: 8px 10px; border: 1px solid var(--border); border-radius: 7px; background: var(--white); color: var(--text); font: inherit; font-size: .82rem; }
    .requirements-table .requirement-edit-fields input[type="file"] { max-width: 230px; color: var(--text-muted); font-size: .78rem; }
    .requirements-table .requirement-edit-fields label { display: grid; gap: 6px; color: var(--text-muted); font-size: .78rem; font-weight: 600; }
    .requirements-table .requirement-badges { display: flex; flex-wrap: wrap; gap: 6px; }
    .requirements-table .requirement-badge { display: inline-flex; align-items: center; min-height: 26px; padding: 4px 9px; border-radius: 99px; background: #f1f3f5; color: #4b5563; font-size: .74rem; font-weight: 700; white-space: nowrap; }
    .requirements-table .requirement-badge.is-required { background: #fff4d6; color: #795900; }
    .requirements-table .requirement-badge.is-approved { background: #fce8e8; color: #8b0000; }
    .requirements-table .submission-count { display: inline-grid; min-width: 32px; height: 32px; place-items: center; padding: 0 8px; border-radius: 10px; background: #f1f3f5; color: var(--text); font-weight: 700; }
    .requirements-table .requirement-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; min-width: 166px; }
    .requirements-table .requirement-actions form { display: inline-flex; }
    .requirements-table .requirement-actions .btn { min-height: 34px; justify-content: center; }
    .requirements-table .btn-cancel { border: 1px solid var(--border); background: var(--white); color: var(--text-muted); }
    .requirements-table [hidden] { display: none !important; }
    .requirements-card .checklist-header-copy p { margin-top: 4px; color: var(--text-muted); font-size: .8rem; }
    .requirements-card .checklist-total { display: inline-flex; align-items: center; gap: 7px; padding: 7px 10px; border-radius: 99px; background: #fce8e8; color: var(--primary); font-size: .78rem; font-weight: 700; white-space: nowrap; }
    @media (max-width: 700px) {
        .requirements-card .card-header { align-items: flex-start; padding: 16px; }
        .requirements-card .card-body { padding: 0; }
        .requirements-table { min-width: 860px; }
    }
    @media (max-width: 1100px) {
        .requirement-field-name,.requirement-field-instructions { grid-column: span 6; }
        .requirement-field-template { grid-column: 1 / -1; }
        .requirement-field-deadline,.requirement-field-audience,.requirement-options { grid-column: span 4; }
    }
    @media (max-width: 640px) {
        .requirement-create-card .card-header { padding: 16px; }
        .requirement-create-card .card-body { padding: 16px; }
        .requirement-create-grid { grid-template-columns: minmax(0, 1fr); gap: 16px; }
        .requirement-field-name,.requirement-field-instructions,.requirement-field-template,.requirement-field-deadline,
        .requirement-field-audience,.requirement-options,.requirement-form-actions { grid-column: 1 / -1; }
        .requirement-upload-options { grid-template-columns: minmax(0, 1fr); }
        .requirement-upload-section { padding: 14px; }
        .requirement-form-actions .btn { width: 100%; justify-content: center; }
    }
</style>
@endsection

@section('content')
@if($errors->any())
    <div class="alert alert-danger" role="alert" style="margin-bottom:16px;">
        <strong>Please correct the following:</strong>
        <ul style="margin:6px 0 0 20px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<div class="card requirement-create-card">
    <div class="card-header">
        <div class="requirement-create-heading">
            <span class="requirement-create-icon" aria-hidden="true"><x-icon class="fa-solid fa-folder-plus" /></span>
            <div>
                <h2>Decide What Applicants Must Submit</h2>
                <p>Create a checklist item and choose when applicants can access it.</p>
            </div>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.additional-requirements.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="requirement-create-grid">
                <div class="requirement-field requirement-field-name">
                    <label for="new-requirement-name">Requirement title</label>
                    <input id="new-requirement-name" name="name" value="{{ old('name') }}" maxlength="150" placeholder="e.g. Proof of residency" required>
                    @error('name')<div class="field-error" role="alert">{{ $message }}</div>@enderror
                </div>
                <div class="requirement-field requirement-field-instructions">
                    <label for="new-requirement-description">Applicant instructions <span>Optional</span></label>
                    <input id="new-requirement-description" name="description" value="{{ old('description') }}" maxlength="1000" placeholder="Explain what to submit">
                    @error('description')<div class="field-error" role="alert">{{ $message }}</div>@enderror
                </div>
                <div class="requirement-field requirement-field-audience">
                    <label for="new-requirement-audience">Applicant access</label>
                    <select id="new-requirement-audience" name="audience" required>
                        <option value="approved_applicants" @selected(old('audience', 'approved_applicants') === 'approved_applicants')>Approved applicants only</option>
                        <option value="all_applicants" @selected(old('audience') === 'all_applicants')>All applicants</option>
                    </select>
                    @error('audience')<div class="field-error" role="alert">{{ $message }}</div>@enderror
                </div>
                <div class="requirement-options">
                    <input type="hidden" name="is_required" value="0">
                    <label class="requirement-checkbox" for="new-requirement-required">
                        <input id="new-requirement-required" type="checkbox" name="is_required" value="1" @checked(old('is_required', '1') === '1')>
                        <span>Required<small>Applicants must submit this item</small></span>
                    </label>
                </div>
                <div class="requirement-field requirement-field-deadline">
                    <label for="new-requirement-due-at">Submission deadline <span>Optional</span></label>
                    <input id="new-requirement-due-at" type="datetime-local" name="due_at" value="{{ old('due_at') }}">
                    @error('due_at')<div class="field-error" role="alert">{{ $message }}</div>@enderror
                </div>
                <div class="requirement-field-template">
                    <div class="requirement-upload-section">
                        <div class="requirement-upload-heading">
                            <span class="requirement-upload-icon" aria-hidden="true"><x-icon class="fa-solid fa-paperclip" /></span>
                            <div>
                                <strong>Downloadable files <span style="color:var(--text-muted);font-weight:400;">Optional</span></strong>
                                <p>Files are grouped under this requirement for applicants. Add up to 20 PDF or Word files, up to 10 MB each.</p>
                            </div>
                        </div>
                        <div class="requirement-upload-options">
                            <div class="requirement-upload-choice">
                                <label for="new-requirement-templates">Choose multiple files</label>
                                <input id="new-requirement-templates" type="file" name="templates[]" accept=".pdf,.doc,.docx" multiple>
                                <small>Select individual files from your device.</small>
                            </div>
                            <div class="requirement-upload-choice">
                                <label for="new-requirement-folder">Choose a folder</label>
                                <input id="new-requirement-folder" type="file" name="templates[]" accept=".pdf,.doc,.docx" webkitdirectory directory multiple>
                                <small>Upload the supported files from a folder.</small>
                            </div>
                        </div>
                        @error('templates')<div class="field-error" role="alert">{{ $message }}</div>@enderror
                    </div>
                </div>
                <input type="hidden" name="is_active" value="1">
                <div class="requirement-form-actions">
                    <button class="btn btn-primary" type="submit"><x-icon class="fa-solid fa-plus" aria-hidden="true" /> Add requirement</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card requirements-card">
    <div class="card-header">
        <div class="checklist-header-copy">
            <h2><x-icon class="fa-solid fa-list-check" /> Applicant Checklist Additions</h2>
            <p>Manage the content, submission details, and applicant access for each requirement.</p>
        </div>
        <span class="checklist-total"><x-icon class="fa-solid fa-folder-open" aria-hidden="true" />{{ $requirements->count() }} {{ \Illuminate\Support\Str::plural('requirement', $requirements->count()) }}</span>
    </div>
    <div class="card-body">
        @if($requirements->isEmpty())
            <div style="padding:38px 20px;text-align:center;">
                <x-icon class="fa-regular fa-folder-open" aria-hidden="true" style="margin-bottom:10px;color:var(--text-muted);font-size:1.8rem;" />
                <p style="color:var(--text);font-weight:700;">No requirements yet</p>
                <p style="margin-top:5px;color:var(--text-muted);font-size:.85rem;">Use the form above to add the first applicant requirement.</p>
            </div>
        @else
            <div class="table-wrap">
                <table class="requirements-table">
                    <thead>
                        <tr>
                            <th scope="col">Requirement</th>
                            <th scope="col">Form and deadline</th>
                            <th scope="col">Submissions</th>
                            <th scope="col">Type</th>
                            <th scope="col">Access</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requirements as $requirement)
                            <tr class="requirement-row">
                                <td style="min-width:250px;">
                                    <form id="requirement-update-{{ $requirement->id }}" method="POST" action="{{ route('admin.additional-requirements.update', $requirement) }}" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')
                                        <div data-requirement-display class="requirement-name">{{ $requirement->name }}</div>
                                        <p data-requirement-display class="requirement-description">{{ $requirement->description ?: 'No instructions provided.' }}</p>
                                        <div class="requirement-edit-fields">
                                            <label class="requirement-edit-field" hidden>
                                                Requirement title
                                                <input form="requirement-update-{{ $requirement->id }}" data-requirement-edit-field name="name" value="{{ $requirement->name }}" maxlength="150" required>
                                            </label>
                                            <label class="requirement-edit-field" hidden>
                                                Applicant instructions
                                                <input form="requirement-update-{{ $requirement->id }}" data-requirement-edit-field name="description" value="{{ $requirement->description }}" maxlength="1000" placeholder="Optional instructions">
                                            </label>
                                        </div>
                                        <input type="hidden" name="is_required" value="1">
                                        <input type="hidden" name="is_active" value="1">
                                    </form>
                                </td>
                                <td style="min-width:230px;">
                                            <div data-requirement-display class="requirement-meta requirement-file-list">
                                                @if($requirement->template_original_name)
                                                    <span><x-icon class="fa-solid fa-file-arrow-down" aria-hidden="true" />{{ $requirement->template_original_name }}</span>
                                                @endif
                                                @foreach($requirement->templates as $template)
                                                    <span><x-icon class="fa-solid fa-file-arrow-down" aria-hidden="true" />{{ $template->original_name }}</span>
                                                @endforeach
                                                @unless($requirement->template_original_name || $requirement->templates->isNotEmpty())
                                                    <span>No downloadable files</span>
                                                @endunless
                                                <span><x-icon class="fa-regular fa-clock" aria-hidden="true" />{{ $requirement->due_at?->format('M j, Y g:i A') ?? 'No deadline' }}</span>
                                            </div>
                                            <div class="requirement-edit-fields requirement-edit-field" data-requirement-edit-field hidden>
                                        @if($requirement->template_original_name)
                                            <label style="display:flex;align-items:center;gap:7px;">
                                                <input form="requirement-update-{{ $requirement->id }}" type="checkbox" name="remove_template" value="1">
                                                Remove current file
                                            </label>
                                        @endif
                                        @foreach($requirement->templates as $template)
                                            <label style="display:flex;align-items:center;gap:7px;">
                                                <input form="requirement-update-{{ $requirement->id }}" type="checkbox" name="remove_template_files[]" value="{{ $template->id }}">
                                                Remove {{ $template->original_name }}
                                            </label>
                                        @endforeach
                                        <label>
                                            Add multiple files
                                            <input form="requirement-update-{{ $requirement->id }}" type="file" name="templates[]" accept=".pdf,.doc,.docx" multiple aria-label="Add downloadable files for {{ $requirement->name }}" style="display:block;max-width:220px;margin-top:4px;font-size:.78rem;">
                                        </label>
                                        <label>
                                            Or add a whole folder
                                            <input form="requirement-update-{{ $requirement->id }}" type="file" name="templates[]" accept=".pdf,.doc,.docx" webkitdirectory directory multiple aria-label="Add a folder of downloadable files for {{ $requirement->name }}" style="display:block;max-width:220px;margin-top:4px;font-size:.78rem;">
                                        </label>
                                        <label for="requirement-due-{{ $requirement->id }}">Submission deadline
                                            <input form="requirement-update-{{ $requirement->id }}" id="requirement-due-{{ $requirement->id }}" type="datetime-local" name="due_at" value="{{ $requirement->due_at?->format('Y-m-d\TH:i') }}">
                                        </label>
                                    </div>
                                </td>
                                <td><span class="submission-count" aria-label="{{ $requirement->submissions_count }} applicants submitted">{{ $requirement->submissions_count }}</span></td>
                                <td>
                                    <div data-requirement-display class="requirement-badges">
                                        <span class="requirement-badge {{ $requirement->is_required ? 'is-required' : '' }}">{{ $requirement->is_required ? 'Required' : 'Optional' }}</span>
                                    </div>
                                    <div class="requirement-edit-fields requirement-edit-field" data-requirement-edit-field hidden>
                                        <label>Requirement type
                                            <select form="requirement-update-{{ $requirement->id }}" data-requirement-edit-field name="is_required">
                                                <option value="1" @selected($requirement->is_required)>Required</option>
                                                <option value="0" @selected(!$requirement->is_required)>Optional</option>
                                            </select>
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <div data-requirement-display class="requirement-badges">
                                        <span class="requirement-badge {{ $requirement->audience === 'approved_applicants' ? 'is-approved' : '' }}">{{ $requirement->audience === 'approved_applicants' ? 'Approved only' : 'All applicants' }}</span>
                                    </div>
                                    <div class="requirement-edit-fields requirement-edit-field" data-requirement-edit-field hidden>
                                        <label>Applicant access
                                            <select form="requirement-update-{{ $requirement->id }}" data-requirement-edit-field name="audience" aria-label="Who can access {{ $requirement->name }}">
                                                <option value="approved_applicants" @selected($requirement->audience === 'approved_applicants')>Approved only</option>
                                                <option value="all_applicants" @selected($requirement->audience === 'all_applicants')>All applicants</option>
                                            </select>
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <div class="requirement-actions">
                                        <button class="btn btn-outline btn-sm" type="button" data-requirement-edit aria-expanded="false"><x-icon class="fa-solid fa-pen" aria-hidden="true" /> Edit</button>
                                        <button form="requirement-update-{{ $requirement->id }}" class="btn btn-primary btn-sm" type="submit" data-requirement-save hidden><x-icon class="fa-solid fa-check" aria-hidden="true" /> Save</button>
                                        <button class="btn btn-cancel btn-sm" type="button" data-requirement-cancel data-unsaved-cancel="requirement-update-{{ $requirement->id }}" hidden>Cancel</button>
                                        <form method="POST" action="{{ route('admin.additional-requirements.destroy', $requirement) }}" style="display:inline;" onsubmit="return confirm('Delete this requirement, all its downloadable files, and all applicant uploads? This cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm" type="submit"><x-icon class="fa-regular fa-trash-can" aria-hidden="true" /> Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
document.querySelectorAll('.requirement-row').forEach(function (row) {
    var editButton = row.querySelector('[data-requirement-edit]');
    var cancelButton = row.querySelector('[data-requirement-cancel]');
    var updateForm = row.querySelector('form[id^="requirement-update-"]');

    function setEditing(editing) {
        row.querySelectorAll('[data-requirement-display]').forEach(function (element) {
            element.hidden = editing;
        });
        row.querySelectorAll('[data-requirement-edit-field]').forEach(function (element) {
            element.hidden = !editing;
        });
        editButton.hidden = editing;
        editButton.setAttribute('aria-expanded', editing ? 'true' : 'false');
        cancelButton.hidden = !editing;
        row.querySelector('[data-requirement-save]').hidden = !editing;
    }

    editButton.addEventListener('click', function () {
        setEditing(true);
    });

    cancelButton.addEventListener('click', function () {
        updateForm.reset();
        setEditing(false);
    });
});
</script>
@endsection
