<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'PESO LAL-LO') }} - Applicant Docs</title>
    <link rel="stylesheet" href="{{ asset('css/request-loading.css') }}?v={{ filemtime(public_path('css/request-loading.css')) }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            color: #212121;
        }

        .admin-container {
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 250px;
            background: #004d40;
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar h2 {
            margin-bottom: 30px;
            font-size: 1.5rem;
        }

        .sidebar nav {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .sidebar nav a {
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 6px;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar nav a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .header h1 {
            font-size: 2rem;
            color: #004d40;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logout-btn {
            background: #d32f2f;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #b71c1c;
        }

        .content-wrapper {
            display: flex;
            gap: 20px;
        }

        .applicants-list {
            width: 350px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .applicants-list-header {
            background: #004d40;
            color: white;
            padding: 20px;
            font-size: 1.1rem;
            font-weight: bold;
        }

        .applicants-list-body {
            max-height: min(68vh, 600px);
            overflow-y: auto;
            overscroll-behavior: contain;
            scrollbar-gutter: stable;
        }

        .applicant-item,
        .applicant-name,
        .applicant-email {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .files-table-scroll {
            max-height: min(68vh, 720px);
            overflow: auto;
            overscroll-behavior: contain;
            scrollbar-gutter: stable;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
        }

        .files-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #f5f5f5;
        }

        .applicant-item {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .applicant-item:hover {
            background: #f5f5f5;
        }

        .applicant-item.active {
            background: #e8f5e9;
            border-left: 4px solid #004d40;
            padding-left: 16px;
        }

        .applicant-info {
            flex: 1;
        }

        .applicant-name {
            font-weight: 600;
            color: #212121;
            margin-bottom: 4px;
        }

        .applicant-email {
            font-size: 0.85rem;
            color: #666;
        }

        .file-count {
            background: #ffab00;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .files-section {
            flex: 1;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }

        .files-header {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .files-header-title {
            font-size: 1.1rem;
            font-weight: bold;
            color: #212121;
        }

        .bulk-actions {
            display: flex;
            gap: 10px;
        }

        .bulk-actions button {
            background: #004d40;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.2s;
        }

        .bulk-actions button:hover {
            background: #00332a;
        }

        .search-files {
            margin-bottom: 20px;
        }

        .search-files input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }

        .files-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .files-table thead {
            background: #f5f5f5;
        }

        .files-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #212121;
            border-bottom: 2px solid #ddd;
            word-break: break-word;
        }

        .files-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            word-break: break-word;
        }

        .checkbox-col {
            width: 50px;
        }

        .file-name-col {
            width: 35%;
        }

        .file-icon {
            margin-right: 8px;
            color: #ffab00;
        }

        .actions-col {
            text-align: center;
            width: 150px;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
            white-space: nowrap;
        }

        .btn-download {
            background: #1976d2;
            color: white;
        }

        .btn-download:hover {
            background: #1565c0;
        }

        .btn-view {
            background: #00897b;
            color: white;
        }

        .btn-view:hover {
            background: #00695c;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        @media (max-width: 1200px) {
            .content-wrapper {
                flex-direction: column;
            }

            .applicants-list {
                width: 100%;
                max-height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <h2><x-icon class="fa-solid fa-shield" /> Admin</h2>
            <nav>
                <a href="{{ route('admin.dashboard') }}"><x-icon class="fa-solid fa-chart-line" /> Dashboard</a>
                <a href="{{ route('admin.applications.index') }}"><x-icon class="fa-solid fa-file-circle-check" /> Applications</a>
                <a href="{{ route('admin.applicant-docs') }}" style="background: rgba(255, 255, 255, 0.2);"><x-icon class="fa-solid fa-folder-open" /> Applicant Docs</a>
                <a href="#"><x-icon class="fa-solid fa-user" /> Users</a>
                <a href="#"><x-icon class="fa-solid fa-file-lines" /> Exports</a>
            </nav>
        </div>
        <div class="main-content">
            <div class="header">
                <div>
                    <h1>Applicant Docs</h1>
                    <p>Browse applicant documents and submissions</p>
                </div>
                <div class="user-info">
                    <span>{{ Auth::user()->email }}</span>
                    <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="logout-btn"><x-icon class="fa-solid fa-sign-out-alt" /> Logout</button>
                    </form>
                </div>
            </div>

            <div class="content-wrapper">
                <!-- Applicants List -->
                <div class="applicants-list">
                    <div class="applicants-list-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span><x-icon class="fa-solid fa-users" /> Applicants name</span>
                            <input type="text" id="searchApplicants" placeholder="Search..." onkeyup="searchApplicants()" style="width: 120px; padding: 6px 10px; border: 1px solid rgba(255,255,255,0.3); border-radius: 4px; background: rgba(255,255,255,0.1); color: white; font-size: 0.85rem;">
                        </div>
                    </div>
                    <div class="applicants-list-body">
                        @forelse($applicants as $applicant)
                            <div class="applicant-item" data-request-loading data-loading-label="Loading documents..." onclick="loadApplicantFiles('{{ $applicant->applicant_name }}', '{{ $applicant->applicant_email }}', this)">
                                <div class="applicant-info">
                                    <div class="applicant-name">
                                        <x-icon class="fa-solid fa-user-circle" style="margin-right: 8px;" />
                                        {{ $applicant->applicant_name }}
                                    </div>
                                    <div class="applicant-email">{{ $applicant->applicant_email }}</div>
                                </div>
                                <div class="file-count">
                                    {{ \App\Models\Application::where('applicant_name', $applicant->applicant_name)->count() }} file{{ \App\Models\Application::where('applicant_name', $applicant->applicant_name)->count() !== 1 ? 's' : '' }}
                                </div>
                            </div>
                        @empty
                            <div style="padding: 20px; text-align: center; color: #999;">
                                No applicants found
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Files Section -->
                <div class="files-section">
                    <div class="files-header">
                        <div class="files-header-title" id="selected-applicant">
                            <x-icon class="fa-solid fa-folder" /> Select an applicant to view documents
                        </div>
                    </div>

                    <div id="files-content">
                        <div class="empty-state">
                            <div class="empty-state-icon"><x-icon class="fa-solid fa-inbox" /></div>
                            <p>Select an applicant to view their documents</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/request-loading.js') }}?v={{ filemtime(public_path('js/request-loading.js')) }}"></script>
    <script>
        function loadApplicantFiles(applicantName, applicantEmail, element) {
            // Update active state
            document.querySelectorAll('.applicant-item').forEach(el => {
                el.classList.remove('active');
            });
            element.classList.add('active');

            // Update header
            document.getElementById('selected-applicant').innerHTML = 
                `<x-icon class="fa-solid fa-folder" /> Applicants / ${applicantName} / Documents`;

            // Fetch files for this applicant
            fetch(`/admin/applicant-files?applicant_name=${encodeURIComponent(applicantName)}`)
                .then(response => {
                    if (!response.ok) throw new Error('Could not load applicant files.');
                    return response.json();
                })
                .then(data => {
                    let content = '';
                    
                    if(data.applications.length === 0) {
                        content = `
                            <div class="empty-state">
                                <div class="empty-state-icon"><x-icon class="fa-solid fa-file" /></div>
                                <p>No documents found for this applicant</p>
                            </div>
                        `;
                    } else {
                        content = `
                            <div class="files-table-scroll" role="region" aria-label="Applicant documents" tabindex="0">
                            <table class="files-table">
                                <thead>
                                    <tr>
                                        <th class="checkbox-col"><input type="checkbox" id="selectAll" onchange="selectAllFiles(this)"></th>
                                        <th>File Name</th>
                                        <th>Upload Date</th>
                                        <th>File Type</th>
                                        <th>Status</th>
                                        <th class="actions-col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
        `;
                        data.applications.forEach(app => {
                            // Check if application has files
                            if(app.files && app.files.length > 0) {
                                app.files.forEach((file, index) => {
                                    let fileName = file.split('/').pop();
                                    let fileType = fileName.split('.').pop().toUpperCase();
                                    let statusBadge = `<span style="background: #4caf50; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">${app.status || 'pending'}</span>`;
                                    
                                    content += `
                                        <tr class="file-row">
                                            <td class="checkbox-col"><input type="checkbox" class="file-checkbox"></td>
                                            <td>
                                                <x-icon class="fa-solid fa-file file-icon" />
                                                ${fileName}
                                            </td>
                                            <td>${new Date(app.created_at).toLocaleDateString()}</td>
                                            <td><span style="color: #1976d2; font-weight: bold;">${fileType}</span></td>
                                            <td>${statusBadge}</td>
                                            <td class="actions-col">
                                                <a href="{{ asset('storage') }}/${file}" class="btn btn-download" download>
                                                    <x-icon class="fa-solid fa-download" /> Download
                                                </a>
                                                <a href="/admin/applications/${app.id}/view-form" class="btn btn-view">
                                                    <x-icon class="fa-solid fa-eye" /> View
                                                </a>
                                            </td>
                                        </tr>
                                    `;
                                });
                            } else if(app.form_data && Object.keys(app.form_data).length > 0) {
                                // If no files but has form data, show the form submission
                                let statusBadge = `<span style="background: #4caf50; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">${app.status || 'pending'}</span>`;
                                content += `
                                    <tr class="file-row">
                                        <td class="checkbox-col"><input type="checkbox" class="file-checkbox"></td>
                                        <td>
                                            <x-icon class="fa-solid fa-file file-icon" />
                                            Application Form Submission
                                        </td>
                                        <td>${new Date(app.created_at).toLocaleDateString()}</td>
                                        <td><span style="color: #1976d2; font-weight: bold;">FORM</span></td>
                                        <td>${statusBadge}</td>
                                        <td class="actions-col">
                                            <a href="/admin/applications/${app.id}/view-form" class="btn btn-view">
                                                <x-icon class="fa-solid fa-eye" /> View Form
                                            </a>
                                        </td>
                                    </tr>
                                `;
                            }
                        });
                        content += `
                                </tbody>
                            </table>
                            </div>
        `;
                    }
                    
                    document.getElementById('files-content').innerHTML = content;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('files-content').innerHTML = `
                        <div class="empty-state">
                            <p>Error loading files. Please try again.</p>
                        </div>
                    `;
                });
        }

        function searchApplicants() {
            const query = document.getElementById('searchApplicants').value.toLowerCase();
            const items = document.querySelectorAll('.applicant-item');
            
            items.forEach(item => {
                const name = item.querySelector('.applicant-name').textContent.toLowerCase();
                const email = item.querySelector('.applicant-email').textContent.toLowerCase();
                item.style.display = (name.includes(query) || email.includes(query)) ? '' : 'none';
            });
        }

        function selectAllFiles(checkbox) {
            document.querySelectorAll('.file-checkbox').forEach(cb => {
                cb.checked = checkbox.checked;
            });
        }
    </script>
</body>
</html>
