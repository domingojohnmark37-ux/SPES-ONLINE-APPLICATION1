@extends('layouts.admin')

@section('title', "Final List of Batch {$batchYear}")
@section('page-title', "Final List of Batch {$batchYear}")
@section('page-sub', 'Review, filter, and finalize the approved SPES beneficiaries list')

@section('styles')
<style>
    .placement-sheet-wrap {
        width: 100%;
        max-height: min(68vh, 720px);
        overflow: auto;
        border: 1px solid #aab7c4;
        background: #fff;
        scrollbar-gutter: stable;
    }

    .placement-sheet {
        width: 100%;
        min-width: 1600px;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
        color: #1f2937;
        font-size: 10px;
        line-height: 1.25;
    }

    .placement-sheet th,
    .placement-sheet td {
        padding: 5px 4px;
        border-right: 1px solid #c7d0d9;
        border-bottom: 1px solid #c7d0d9;
        overflow-wrap: anywhere;
        white-space: normal;
    }

    .placement-sheet thead th {
        position: sticky;
        z-index: 2;
        text-align: center;
        vertical-align: middle;
        background: #dceaf5;
        color: #17365d;
        font-size: 9px;
        font-weight: 700;
        letter-spacing: 0;
        text-transform: none;
    }

    .placement-sheet thead tr:first-child th {
        top: 0;
        height: 29px;
        border-top: 0;
        background: #c9dff0;
    }

    .placement-sheet thead tr:nth-child(2) th {
        top: 28px;
        height: 39px;
    }

    .placement-sheet tbody td {
        height: 29px;
        vertical-align: middle;
    }

    .placement-sheet tbody tr:nth-child(even) td {
        background: #f6f9fc;
    }

    .placement-sheet tbody tr:hover td {
        background: #eaf3fb;
    }

    .placement-sheet td:first-child {
        text-align: center;
        color: #64748b;
    }

    .placement-sheet .sheet-editable {
        width: 100%;
        min-width: 90px;
        box-sizing: border-box;
        padding: 4px;
        border: 1px solid #aab7c4;
        border-radius: 2px;
        background: #fff;
        color: #1f2937;
        font: inherit;
    }

    .placement-sheet .sheet-editable:focus {
        outline: 2px solid #4a90e2;
        outline-offset: -1px;
    }

    .placement-sheet .sheet-number {
        min-width: 82px;
        text-align: right;
    }

    @media (max-width: 1100px) {
        .placement-sheet {
            min-width: 1600px;
        }
    }

    @media (max-width: 640px) {
        .placement-sheet-wrap {
            max-height: 60vh;
        }
    }
</style>
@endsection

@section('content')

<div class="card">
    <div class="card-header">
        <h2><x-icon class="fa-solid fa-list-check" /> Final List of Applicants</h2>
    </div>
    <div class="card-body">
        {{-- Filters --}}
        <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 20px; flex-wrap: wrap;">
            <form method="GET" class="search-bar" style="margin-bottom: 0; flex: 1; min-width: 300px;">
                <input
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search approved applicant by name"
                    aria-label="Search approved applicants by name"
                >
                <select name="barangay">
                    <option value="">All Barangay</option>
                    @foreach(['Abagao','Alaguia','Bagumbayan','Bangag','Bical','Bicud','Binag','Cabayabasan (Capacuan)','Cagoran','Cambong','Catayauan','Catugan','Centro (Poblacion)','Cullit','Dagupan','Dalaya','Fabrica','Fusina','Jurisdiction','Lalafugan','Logac','Magallungon (Santa Teresa)','Magapit','Malanao','Maxingal','Naguilian','Paranum','Rosario','San Antonio (Lafu)','San Jose','San Juan','San Lorenzo','San Mariano','Santa Maria','Tucalana'] as $b)
                        <option value="{{ $b }}" {{ request('barangay')===$b ? 'selected':'' }}>{{ $b }}</option>
                    @endforeach
                </select>
                <select name="spes_status">
                    <option value="">All SPES Type</option>
                    <option value="new" {{ request('spes_status')==='new' ? 'selected':'' }}>New</option>
                    <option value="baby" {{ request('spes_status')==='baby' ? 'selected':'' }}>SPES Baby</option>
                </select>
                <select name="sort">
                    <option value="name_asc" {{ request('sort', 'name_asc')==='name_asc' ? 'selected':'' }}>Name (A-Z)</option>
                    <option value="name_desc" {{ request('sort')==='name_desc' ? 'selected':'' }}>Name (Z-A)</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm"><x-icon class="fa-solid fa-filter" /> Filter</button>
                <a href="{{ route('admin.masterlist.index') }}" class="btn btn-outline btn-sm">Clear</a>
            </form>
        </div>

        @if($applications->isEmpty())
            <p style="color:var(--text-muted);text-align:center;padding:40px 0;">No approved applications match the filters.</p>
        @else
        <div style="margin-bottom: 20px;">
            <strong>Total Count:</strong> <span style="color: var(--primary); font-size: 1.1rem;">{{ $applications->total() }}</span>
        </div>

        <p style="margin:0 0 8px;color:var(--text-muted);font-size:.78rem;">
            Spreadsheet view · edit work details and integer amounts for this export, then save the list.
        </p>
        <form method="POST" action="{{ route('admin.masterlist.store') }}">
            @csrf
            <input type="hidden" name="barangay" value="{{ request('barangay') }}">
            <input type="hidden" name="spes_status" value="{{ request('spes_status') }}">
            <input type="hidden" name="search" value="{{ request('search') }}">
            <input type="hidden" name="sort" value="{{ request('sort', 'name_asc') }}">
        <div class="placement-sheet-wrap" role="region" aria-label="Final list spreadsheet" tabindex="0">
            <table class="placement-sheet">
                <colgroup>
                    <col style="width:3.5%">
                    <col style="width:6%">
                    <col style="width:6%">
                    <col style="width:3%">
                    <col style="width:3%">
                    <col style="width:3%">
                    <col style="width:9%">
                    <col style="width:5%">
                    <col style="width:5%">
                    <col style="width:6.5%">
                    <col style="width:4.5%">
                    <col style="width:5%">
                    <col style="width:7.5%">
                    <col style="width:8.5%">
                    <col style="width:5.5%">
                    <col style="width:5.5%">
                    <col style="width:6.5%">
                    <col style="width:7%">
                </colgroup>
                <thead>
                    <tr>
                        <th rowspan="2">Ref. No.</th>
                        <th colspan="3">Name of Students</th>
                        <th rowspan="2">Sex</th>
                        <th rowspan="2">Age</th>
                        <th rowspan="2">Address</th>
                        <th rowspan="2">Status of Parents</th>
                        <th rowspan="2">Student / OSY / Dependent</th>
                        <th colspan="2">Educational Attainment</th>
                        <th rowspan="2">New or SPES Baby</th>
                        <th rowspan="2">Nature of Work</th>
                        <th rowspan="2">Place of Work</th>
                        <th colspan="2">Employment Period</th>
                        <th rowspan="2">Wage Rate per Day</th>
                        <th rowspan="2">Company Share</th>
                    </tr>
                    <tr>
                        <th>Last Name</th>
                        <th>First Name</th>
                        <th>M.I.</th>
                        <th>Elem / ALS / JHS / SHS / College / Tech-voc</th>
                        <th>Grade / Year Level</th>
                        <th>Start</th>
                        <th>End</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($applications as $i => $app)
                        @php($placement = $app->placement_report_fields)
                    <tr>
                        <td style="color:var(--text-muted)">{{ $applications->firstItem() + $i }}</td>
                        <td>{{ $placement['last_name'] ?: '—' }}</td>
                        <td>{{ $placement['first_name'] ?: '—' }}</td>
                        <td>{{ $placement['middle_initial'] ?: '—' }}</td>
                        <td>{{ $placement['sex'] ?: '—' }}</td>
                        <td>{{ $placement['age'] ?: '—' }}</td>
                        <td>{{ $placement['address'] ?: '—' }}</td>
                        <td>{{ $placement['parent_status'] ?: '—' }}</td>
                        <td>{{ $placement['applicant_type'] ?: '—' }}</td>
                        <td>{{ $placement['education_level'] ?: '—' }}</td>
                        <td>{{ $placement['grade_year_level'] ?: '—' }}</td>
                        <td>{{ $placement['spes_type'] ?: '—' }}</td>
                        <td>
                            <input
                                class="sheet-editable"
                                type="text"
                                name="placement[{{ $app->id }}][nature_of_work]"
                                value="{{ old('placement.'.$app->id.'.nature_of_work', $placement['nature_of_work']) }}"
                                maxlength="255"
                                aria-label="Nature of work for {{ $placement['first_name'] }} {{ $placement['last_name'] }}"
                            >
                        </td>
                        <td>
                            <input
                                class="sheet-editable"
                                type="text"
                                name="placement[{{ $app->id }}][place_of_assignment]"
                                value="{{ old('placement.'.$app->id.'.place_of_assignment', $placement['place_of_assignment']) }}"
                                maxlength="255"
                                aria-label="Place of work for {{ $placement['first_name'] }} {{ $placement['last_name'] }}"
                            >
                        </td>
                        <td>{{ $placement['employment_start'] ?: '—' }}</td>
                        <td>{{ $placement['employment_end'] ?: '—' }}</td>
                        <td>
                            <input
                                class="sheet-editable sheet-number"
                                type="number"
                                name="placement[{{ $app->id }}][wage_rate]"
                                value="{{ old('placement.'.$app->id.'.wage_rate', $placement['wage_rate']) }}"
                                min="1"
                                step="1"
                                inputmode="numeric"
                                data-positive-integer
                                aria-label="Wage rate per day for {{ $placement['first_name'] }} {{ $placement['last_name'] }}"
                            >
                        </td>
                        <td>
                            <input
                                class="sheet-editable sheet-number"
                                type="number"
                                name="placement[{{ $app->id }}][company_share]"
                                value="{{ old('placement.'.$app->id.'.company_share', $placement['company_share']) }}"
                                min="1"
                                step="1"
                                inputmode="numeric"
                                data-positive-integer
                                aria-label="Company share for {{ $placement['first_name'] }} {{ $placement['last_name'] }}"
                            >
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div style="margin-top: 20px; display: flex; justify-content: center;">
            {{ $applications->links() }}
        </div>

        {{-- Save final list form --}}
        @if($applications->count() > 0)
        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border);">
            <p style="margin:0 0 12px;color:var(--text-muted);font-size:.85rem;">
                Saves a ZIP package with the placement report in Excel format and a separate folder for each applicant. Each folder contains the applicant details PDF and their submitted documents.
            </p>
            <div style="display: flex; gap: 12px; align-items: flex-end;">
                <div style="flex: 1;">
                    <label style="display: block; font-weight: 600; margin-bottom: 6px; color: var(--text); font-size: .9rem;">
                        Final List Name (Optional)
                    </label>
                    <input type="text" 
                           name="name"
                           value="{{ old('name', "Final List of Batch {$batchYear}") }}"
                           placeholder="Final List of Batch {{ $batchYear }}"
                           style="width: 100%; padding: 10px 13px; border: 1.5px solid var(--border); border-radius: 7px; font-size: .95rem;">
                </div>

                <button type="submit" class="btn btn-success" style="display: flex; align-items: center; gap: 6px; white-space: nowrap;">
                    <x-icon class="fa-solid fa-floppy-disk" /> Save Final List
                </button>
            </div>
        </div>
        @endif
        </form>
        @endif
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <div class="card-header">
        <h2><x-icon class="fa-solid fa-box-archive" /> Saved Final List Packages</h2>
    </div>
    <div class="card-body">
        @if(session('generated_final_list_id'))
            @php($generatedList = $savedLists->firstWhere('id', session('generated_final_list_id')))
            @if($generatedList)
                <p role="status" style="margin-bottom:14px;">
                    Your package is ready:
                    <a href="{{ route('admin.masterlist.download', $generatedList) }}">{{ $generatedList->name }}.zip</a>
                </p>
            @endif
        @endif
        @if($savedLists->isEmpty())
            <p style="color:var(--text-muted);">No final list packages have been saved yet.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Final List</th><th>Saved</th><th>Applicants</th><th>Package</th></tr></thead>
                    <tbody>
                        @foreach($savedLists as $savedList)
                            <tr>
                                <td>{{ $savedList->name }}</td>
                                <td>{{ $savedList->created_at->format('M j, Y g:i A') }}</td>
                                <td>{{ $savedList->applications()->count() }}</td>
                                <td>
                                    @if($savedList->archive_path)
                                        <a class="btn btn-primary btn-sm" href="{{ route('admin.masterlist.download', $savedList) }}">
                                            <x-icon class="fa-solid fa-download" /> Download ZIP
                                        </a>
                                    @else
                                        <span>Package unavailable</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<style>
    .badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: .8rem;
        font-weight: 600;
        text-align: center;
    }

    .badge-new {
        background: #e3f2fd;
        color: #1565c0;
    }

    .badge-baby {
        background: #f3e5f5;
        color: #6a1b9a;
    }

    .badge-approved {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .badge-denied {
        background: #ffebee;
        color: #c62828;
    }

    .badge-pending {
        background: #fff3e0;
        color: #e65100;
    }
</style>

@section('scripts')
<script>
    document.querySelectorAll('[data-positive-integer]').forEach(function (input) {
        input.addEventListener('input', function () {
            if (/^0+$/.test(input.value)) {
                input.value = '';
            } else if (/^0+\d/.test(input.value)) {
                input.value = input.value.replace(/^0+/, '');
            }
        });
    });
</script>
@endsection

@endsection
