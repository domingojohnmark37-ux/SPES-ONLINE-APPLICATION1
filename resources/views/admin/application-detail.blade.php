@extends('layouts.admin')

@section('title', 'Application — '.$application->full_name)
@section('page-title', 'Application Detail')
@section('page-sub', $application->ref_id)

@section('content')

<div style="margin-bottom:16px;">
    <a href="{{ route('admin.applications.index') }}" class="btn btn-outline btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Back to List
    </a>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;">

    {{-- Left: Application Form Data --}}
    <div>
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <h2><i class="fa-solid fa-id-card"></i> Personal Information</h2>
                <span class="badge badge-{{ $application->status }}">{{ ucfirst($application->status) }}</span>
            </div>
            <div class="detail-grid">
                <div class="detail-item"><div class="detail-label">Full Name</div><div class="detail-value">{{ $application->full_name }}</div></div>
                <div class="detail-item"><div class="detail-label">Sex</div><div class="detail-value">{{ $application->sex }}</div></div>
                <div class="detail-item"><div class="detail-label">Birthday</div><div class="detail-value">{{ $application->birthday->format('F d, Y') }}</div></div>
                <div class="detail-item"><div class="detail-label">Age</div><div class="detail-value">{{ $application->age }} years old</div></div>
                <div class="detail-item"><div class="detail-label">Barangay</div><div class="detail-value">{{ $application->barangay }}</div></div>
                <div class="detail-item"><div class="detail-label">Civil Status</div><div class="detail-value">{{ $application->civil_status }}</div></div>
                <div class="detail-item"><div class="detail-label">Parent Status</div><div class="detail-value">{{ $application->parent_status }}</div></div>
                <div class="detail-item"><div class="detail-label">Educational Attainment</div><div class="detail-value">{{ $application->education }}</div></div>
                <div class="detail-item"><div class="detail-label">SPES Beneficiary Type</div>
                    <div class="detail-value">
                        <span class="badge {{ $application->spes_status === 'new' ? 'badge-new' : 'badge-baby' }}">
                            {{ $application->spes_status === 'new' ? 'New (1st time)' : 'SPES Baby (2nd/3rd time)' }}
                        </span>
                    </div>
                </div>
                <div class="detail-item"><div class="detail-label">Facebook Profile</div><div class="detail-value">{{ $application->facebook ?? '—' }}</div></div>
                <div class="detail-item"><div class="detail-label">Mother's Name</div><div class="detail-value">{{ $application->mother_name }}</div></div>
                <div class="detail-item"><div class="detail-label">Mother's Occupation</div><div class="detail-value">{{ $application->mother_occupation }}</div></div>
                <div class="detail-item"><div class="detail-label">Mother's Contact Number</div><div class="detail-value">{{ $application->mother_contact_no }}</div></div>
                <div class="detail-item"><div class="detail-label">Father / Guardian</div><div class="detail-value">{{ $application->father_guardian_name }}</div></div>
                <div class="detail-item"><div class="detail-label">Father's Occupation</div><div class="detail-value">{{ $application->father_occupation }}</div></div>
                <div class="detail-item"><div class="detail-label">Father's Contact Number</div><div class="detail-value">{{ $application->father_contact_no }}</div></div>
            </div>
        </div>

        {{-- Uploaded Documents --}}
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header"><h2>Uploaded Documents</h2></div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;">
                    @foreach([
                        ['label'=>'Birth Certificate','key'=>'resume'],
                        ['label'=>'Certificate of Enrollment','key'=>'certificate_enrollment']
                    ] as $doc)
                        <div style="border:1.5px solid var(--border);border-radius:10px;padding:16px;text-align:center;">
                            <div style="font-size:.82rem;font-weight:600;margin-bottom:8px;">{{ $doc['label'] }}</div>
                            @if($application->{$doc['key']})
                                <button type="button" onclick="openDocumentModal('{{ route('applications.document.view', ['application' => $application->id, 'document' => $doc['key']]) }}', '{{ $doc['label'] }}')"
                                   class="btn btn-primary btn-sm" style="display:inline-flex; align-items:center; gap:6px;">
                                    View
                                </button>
                            @else
                                <span style="font-size:.78rem;color:var(--text-muted);">Not uploaded</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

<div id="documentModal" style="position:fixed; inset:0; background:rgba(0,0,0,.55); display:none; align-items:center; justify-content:center; z-index:2000; padding:24px;">
    <div style="width:min(1100px, 92vw); max-height:90vh; background:#fff; border-radius:14px; box-shadow:0 20px 60px rgba(0,0,0,.25); overflow:hidden; border:1px solid var(--border);">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-bottom:1px solid var(--border); background:#f8fafb;">
            <strong id="documentModalTitle" style="font-size:1rem; color:var(--primary);">Document Preview</strong>
            <button type="button" onclick="closeDocumentModal()" class="btn btn-outline btn-sm" aria-label="Close preview">
                <i class="fa-solid fa-xmark"></i> Close
            </button>
        </div>
        <div style="padding:12px; background:#f5f5f5; height:80vh;">
            <iframe id="documentFrame" title="Document Preview" style="width:100%; height:100%; border:none; background:#fff; border-radius:8px;"></iframe>
        </div>
    </div>
</div>

<script>
    function openDocumentModal(url, title) {
        const modal = document.getElementById('documentModal');
        const frame = document.getElementById('documentFrame');
        const titleEl = document.getElementById('documentModalTitle');

        titleEl.textContent = title + ' Preview';
        modal.style.display = 'flex';

        fetch(url)
            .then(response => response.blob())
            .then(blob => {
                const objectUrl = URL.createObjectURL(blob);
                frame.src = objectUrl;
            })
            .catch(() => {
                frame.src = url;
            });
    }

    function closeDocumentModal() {
        const modal = document.getElementById('documentModal');
        const frame = document.getElementById('documentFrame');
        modal.style.display = 'none';
        if (frame.src && frame.src.startsWith('blob:')) {
            URL.revokeObjectURL(frame.src);
        }
        frame.src = 'about:blank';
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeDocumentModal();
        }
    });
</script>

    {{-- Right: Actions & Comments --}}
    <div>
        {{-- Submission info --}}
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header"><h2><i class="fa-solid fa-info-circle"></i> Submission Info</h2></div>
            <div class="card-body" style="font-size:.875rem;">
                <p><strong>Reference ID:</strong><br><code>{{ $application->ref_id }}</code></p>
                <p style="margin-top:10px;"><strong>Submitted:</strong><br>{{ $application->created_at->format('F d, Y \a\t g:i A') }}</p>
                <p style="margin-top:10px;"><strong>Applicant Email:</strong><br>{{ $application->user->email ?? '—' }}</p>
            </div>
        </div>

        {{-- Actions --}}
        @if($application->status === 'pending')
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header"><h2><i class="fa-solid fa-gavel"></i> Decision</h2></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:10px;">
                <form method="POST" action="{{ route('admin.applications.approve', $application) }}">
                    @csrf
                    <button type="submit" class="btn btn-success" style="width:100%;" onclick="return confirm('Approve this application?')">
                        <i class="fa-solid fa-circle-check"></i> Approve Application
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.applications.deny', $application) }}">
                    @csrf
                    <button type="submit" class="btn btn-danger" style="width:100%;" onclick="return confirm('Deny this application?')">
                        <i class="fa-solid fa-circle-xmark"></i> Deny Application
                    </button>
                </form>
            </div>
        </div>
        @elseif($application->status === 'approved')
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header"><h2><i class="fa-solid fa-file-circle-check"></i> Employment Forms</h2></div>
            <div class="card-body">
                <a href="{{ route('admin.applications.forms', $application) }}" class="btn btn-primary" style="width:100%;text-align:center;">
                    <i class="fa-solid fa-eye"></i> View Application Forms
                </a>
            </div>
        </div>
        @endif

        {{-- Admin Comment --}}
        <div class="card">
            <div class="card-header"><h2><i class="fa-solid fa-comment-dots"></i> Admin Comment / Feedback</h2></div>
            <div class="card-body">
                @if($application->admin_comment)
                    <div style="background:#f5f7fa;border-left:4px solid var(--primary);padding:12px;border-radius:6px;margin-bottom:14px;font-size:.875rem;">
                        {{ $application->admin_comment }}
                    </div>
                @endif
                <form method="POST" action="{{ route('admin.applications.comment', $application) }}">
                    @csrf
                    <textarea name="admin_comment" rows="4" placeholder="Write feedback or notes for the applicant…"
                        style="width:100%;padding:10px;border:1.5px solid var(--border);border-radius:7px;font-size:.875rem;resize:vertical;font-family:inherit;outline:none;"
                    >{{ old('admin_comment', $application->admin_comment) }}</textarea>
                    @error('admin_comment')
                        <div style="color:var(--danger);font-size:.78rem;margin-top:4px;">{{ $message }}</div>
                    @enderror
                    <button type="submit" class="btn btn-primary" style="margin-top:10px;width:100%;">
                        <i class="fa-solid fa-floppy-disk"></i> Save Comment
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
