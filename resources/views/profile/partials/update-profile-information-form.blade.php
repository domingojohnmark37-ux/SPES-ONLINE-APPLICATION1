<section class="bg-white p-6">
    <style>
        :root {
            --space-xs: 0.5rem;
            --space-sm: 0.75rem;
            --space-md: 1rem;
            --space-lg: 1.5rem;
            --radius: 1rem;
            --max-w: 1140px;
        }

        .profile-card {
            max-width: var(--max-w);
            margin: 0 auto;
            border-radius: var(--radius);
            padding: var(--space-lg);
        }

        .compact-row {
            display: grid;
            gap: var(--space-md);
            align-items: start;
            position: relative;
            grid-template-columns: 1fr;
        }

        .field {
            display: flex;
            flex-direction: column;
        }

        .field label {
            font-size: 0.9rem;
            color: #475569;
            margin-bottom: 0.5rem;
        }

        .compact,
        .compact-md,
        .compact-sm,
        .compact-xs,
        .full,
        .education-input {
            width: 100%;
            max-width: 100%;
        }

        .compact { max-width: 18rem; }
        .compact-md { max-width: 22rem; }
        .compact-sm { max-width: 13rem; }
        .compact-xs { max-width: 11rem; }

        input.compact,
        select.compact,
        textarea.compact,
        input.compact-sm,
        select.compact-sm,
        input.compact-md,
        select.compact-md {
            width: 100%;
            padding: 0.8rem 1rem;
            font-size: 0.95rem;
            border-radius: 0.75rem;
            border: 1px solid #d1d5db;
        }

        .education-input {
            min-width: 0;
        }

        .parents-card {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-md);
        }

        .checkbox-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem 0.75rem;
        }

        .checkbox-grid label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            flex: 0 1 45%;
        }

        .upload-circle {
            position: absolute;
            right: -1.5rem;
            top: -5rem;
            width: 5.5rem;
            height: 5.5rem;
            border-radius: 50%;
            border: 2px dashed #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            cursor: pointer;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .upload-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            border-radius: 50%;
        }

        .upload-circle .upload-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 0.5rem;
        }

        .upload-circle input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            margin: 0;
            padding: 0;
            opacity: 0;
            cursor: pointer;
            z-index: 10;
        }

        .actions-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: var(--space-md);
            flex-wrap: wrap;
        }

        .actions-row button {
            min-width: 10rem;
            padding: 0.85rem 1.25rem;
            border-radius: 9999px;
            font-weight: 600;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .responsive-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        .responsive-table thead {
            background: #f8fafc;
        }

        .responsive-table th,
        .responsive-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .responsive-table td input {
            width: 100%;
        }

        @media (min-width: 576px) {
            .compact-row {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 768px) {
            .compact-row {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 992px) {
            .compact-row {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (min-width: 1200px) {
            .compact-row {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        @media (max-width: 991.98px) {
            .parents-card {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767.98px) {
            .compact-row {
                grid-template-columns: 1fr;
            }

            .upload-circle {
                position: static;
                right: auto;
                top: auto;
                width: 72px;
                height: 72px;
                margin: 0.75rem auto 0;
            }

            .actions-row {
                justify-content: center;
            }

            .actions-row button {
                width: 100%;
                min-width: 0;
            }

            .checkbox-grid label {
                flex: 0 1 48%;
            }

            .responsive-table,
            .responsive-table thead,
            .responsive-table tbody,
            .responsive-table tr,
            .responsive-table th,
            .responsive-table td {
                display: block;
                width: 100%;
            }

            .responsive-table thead {
                display: none;
            }

            .responsive-table tr {
                margin-bottom: var(--space-md);
                border: 1px solid #e2e8f0;
                border-radius: 0.75rem;
                overflow: hidden;
                padding: 0.5rem 0;
            }

            .responsive-table td {
                border: none;
                padding: 0.75rem 1rem;
            }

            .responsive-table td::before {
                content: attr(data-label) ": ";
                font-weight: 700;
                color: #334155;
                display: inline-block;
                width: 10rem;
            }

            .profile-card {
                padding: 0.75rem;
            }
        }
    </style>
    @php
        $readOnly = $readOnly ?? session('profile_read_only', false);
    @endphp
    <div class="profile-card">
        <div class="mb-4">
            <h2 class="text-lg font-bold text-gray-900">Account Setup: Profile Details</h2>
            <p class="text-xs text-gray-600">Profile Information: Student / Applicant's Information</p>
        </div>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('patch')
        <fieldset {{ $readOnly ? 'disabled' : '' }}>

        <!-- NAME SECTION with Photo (compact layout) -->
        <div class="compact-row mb-3">
            <div class="field">
                <label>Last Name</label>
                <x-text-input id="last_name" name="last_name" type="text" class="compact px-2 py-2 rounded border border-gray-300" :value="old('last_name', optional($profile)->last_name ?? $user->last_name)" required />
                <x-input-error class="text-xs mt-1" :messages="$errors->get('last_name')" />
            </div>
            <div class="field">
                <label>First Name</label>
                <x-text-input id="first_name" name="first_name" type="text" class="compact px-2 py-2 rounded border border-gray-300" :value="old('first_name', optional($profile)->first_name ?? $user->first_name)" required />
                <x-input-error class="text-xs mt-1" :messages="$errors->get('first_name')" />
            </div>
            <div class="field">
                <label>Middle Name</label>
                <x-text-input id="middle_name" name="middle_name" type="text" class="compact px-2 py-2 rounded border border-gray-300" :value="old('middle_name', optional($profile)->middle_name ?? $user->middle_name)" />
                <x-input-error class="text-xs mt-1" :messages="$errors->get('middle_name')" />
            </div>
            <div class="field">
                <label>Sex</label>
                <select id="sex" name="sex" class="compact-sm px-2 py-2 rounded border border-gray-300 bg-white" required>
                    <option value="">Select</option>
                    <option value="Male" {{ old('sex', optional($profile)->sex ?? $user->sex) === 'Male' ? 'selected' : '' }}>Male</option>
                    <option value="Female" {{ old('sex', optional($profile)->sex ?? $user->sex) === 'Female' ? 'selected' : '' }}>Female</option>
                </select>
                <x-input-error class="text-xs mt-1" :messages="$errors->get('sex')" />
            </div>
            <label class="upload-circle" style="cursor: pointer;">
                <div class="upload-placeholder" style="{{ $user->profile_photo ? 'display:none;' : '' }}">
                    <small>Upload<br>Photo</small>
                </div>
                <img id="profilePhotoPreview"
                     src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : '' }}"
                     alt="Profile photo"
                     style="{{ $user->profile_photo ? '' : 'display:none;' }}"
                     class="w-full h-full object-cover rounded-full" />
                <input id="profile_photo" type="file" name="profile_photo" accept="image/*" />
            </label>
            <x-input-error class="text-xs mt-1" :messages="$errors->get('profile_photo')" />
        </div>

        <!-- Personal Information compact row -->
        <div class="compact-row mb-3">
            <div class="field">
                <label>Date of Birth</label>
                <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="compact-sm px-2 py-2 rounded border border-gray-300" :value="old('date_of_birth', optional(optional($profile)->date_of_birth ?? $user->date_of_birth)->format('Y-m-d'))" required />
                <x-input-error class="text-xs mt-1" :messages="$errors->get('date_of_birth')" />
            </div>
            <div class="field">
                <label>Place of Birth</label>
                <x-text-input id="place_of_birth" name="place_of_birth" type="text" class="compact-md px-2 py-2 rounded border border-gray-300" :value="old('place_of_birth', optional($profile)->place_of_birth ?? $user->place_of_birth)" required />
                <x-input-error class="text-xs mt-1" :messages="$errors->get('place_of_birth')" />
            </div>
            <div class="field">
                <label>Status</label>
                <select id="status" name="status" class="compact px-2 py-2 rounded border border-gray-300 bg-white">
                    <option value="">Select</option>
                    <option value="Single" {{ old('status', optional($profile)->status ?? $user->status) === 'Single' ? 'selected' : '' }}>Single</option>
                    <option value="Married" {{ old('status', optional($profile)->status ?? $user->status) === 'Married' ? 'selected' : '' }}>Married</option>
                </select>
            </div>
            <div class="field">
                <label>Citizenship</label>
                <select id="citizenship" name="citizenship" class="compact-sm px-2 py-2 rounded border border-gray-300 bg-white">
                    <option value="">Select</option>
                    <option value="Filipino" {{ old('citizenship', optional($profile)->citizenship ?? $user->citizenship) === 'Filipino' ? 'selected' : '' }}>Filipino</option>
                    <option value="Foreign" {{ old('citizenship', optional($profile)->citizenship ?? $user->citizenship) === 'Foreign' ? 'selected' : '' }}>Foreign</option>
                </select>
            </div>
        </div>

        <!-- Email, Social Media, GSIS (compact) -->
        <div class="compact-row mb-3">
            <div class="field">
                <label>Email Address</label>
                <x-text-input id="email" name="email" type="email" class="full px-2 py-2 rounded border border-gray-300" :value="old('email', $user->email)" required />
                <x-input-error class="text-xs mt-1" :messages="$errors->get('email')" />
            </div>
            <div class="field">
                <label>Social Media Account</label>
                <x-text-input id="social_media" name="social_media" type="text" class="compact-md px-2 py-2 rounded border border-gray-300" :value="old('social_media', optional($profile)->social_media ?? $user->social_media)" />
                <p class="text-xs text-gray-400 mt-1">Social Media Account (Profile)</p>
            </div>
            <div class="field">
                <label>GSIS Beneficiary/Relationship</label>
                <x-text-input id="gsis_beneficiary" name="gsis_beneficiary" type="text" class="compact-md px-2 py-2 rounded border border-gray-300" :value="old('gsis_beneficiary', optional($profile)->gsis_beneficiary ?? $user->gsis_beneficiary)" />
            </div>
        </div>

        <!-- Addresses and Applicant Category (compact) -->
        <div class="compact-row mb-3">
            <div class="field" style="flex:1;">
                <label>Present Address</label>
                <x-text-input id="present_address" name="present_address" type="text" class="full px-2 py-2 rounded border border-gray-300" :value="old('present_address', optional($profile)->present_address ?? $user->present_address)" />
            </div>
            <div class="field">
                <label>Permanent Address</label>
                <x-text-input id="permanent_address" name="permanent_address" type="text" class="compact-md px-2 py-2 rounded border border-gray-300" :value="old('permanent_address', optional($profile)->permanent_address ?? $user->permanent_address)" />
            </div>
            <div class="field">
                <label>Contact Number</label>
                <x-text-input id="contact_number" name="contact_number" type="text" class="compact-sm px-2 py-2 rounded border border-gray-300" :value="old('contact_number', optional($profile)->contact_number ?? $user->contact_number ?? '')" />
            </div>
        </div>

        <!-- Applicant Category -->
        <div class="mb-3">
            <label class="block text-xs font-semibold text-gray-600 mb-1">APPLICANT'S CATEGORY</label>
            <select id="applicant_category" name="applicant_category" class="compact-md px-2 py-2 text-sm rounded border border-gray-300 bg-white">
                <option value="">Select</option>
                <option value="Student" {{ old('applicant_category', optional($profile)->applicant_category ?? $user->applicant_category) === 'Student' ? 'selected' : '' }}>Student</option>
                <option value="OFW" {{ old('applicant_category', optional($profile)->applicant_category ?? $user->applicant_category) === 'OFW' ? 'selected' : '' }}>OFW</option>
                <option value="Unemployed" {{ old('applicant_category', optional($profile)->applicant_category ?? $user->applicant_category) === 'Unemployed' ? 'selected' : '' }}>Unemployed</option>
            </select>
        </div>

        <!-- EDUCATION TABLE -->
        <div class="mb-4">
            <label class="block text-xs font-bold text-gray-600 uppercase mb-2">EDUCATION</label>
            <div class="overflow-x-auto border border-gray-300 rounded">
                <div class="table-responsive">
                    <table class="responsive-table">
                        <thead>
                            <tr>
                                <th>EDUCATION</th>
                                <th>NAME OF SCHOOL</th>
                                <th>COURSE</th>
                                <th>YEAR LEVEL</th>
                                <th>DATE OF ATTENDANCE</th>
                            </tr>
                        </thead>
                    <tbody>
                        @php
                            $educationLevels = ['Elementary', 'Secondary', 'Tertiary', 'Tech-Voc'];
                            $oldEducation = old('education_history', optional($profile)->education_history ?? $user->education_history ?? []);
                            if (!is_array($oldEducation)) {
                                $oldEducation = [];
                            }

                            $education = [];
                            foreach ($educationLevels as $index => $level) {
                                $row = $oldEducation[$index] ?? [];
                                $education[] = [
                                    'level' => $row['level'] ?? $level,
                                    'school' => $row['school'] ?? '',
                                    'course' => $row['course'] ?? '',
                                    'year_level' => $row['year_level'] ?? '',
                                    'date_attended' => $row['date_attended'] ?? '',
                                ];
                            }
                        @endphp
                        @foreach ($education as $index => $row)
                            <tr class="border-b border-gray-100">
                                <td class="px-3 py-2 text-xs font-semibold text-gray-700 bg-gray-50" data-label="EDUCATION">
                                    {{ $row['level'] }}
                                    <input type="hidden" name="education_history[{{ $index }}][level]" value="{{ $row['level'] }}">
                                </td>
                                <td class="px-3 py-2" data-label="NAME OF SCHOOL">
                                    <x-text-input id="school_{{ $index }}" name="education_history[{{ $index }}][school]" type="text" class="education-input px-2 py-1 text-sm rounded border border-gray-300" :value="old('education_history.'.$index.'.school', $row['school'])" />
                                </td>
                                <td class="px-3 py-2" data-label="COURSE">
                                    <x-text-input id="course_{{ $index }}" name="education_history[{{ $index }}][course]" type="text" class="education-input px-2 py-1 text-sm rounded border border-gray-300" :value="old('education_history.'.$index.'.course', $row['course'])" />
                                </td>
                                <td class="px-3 py-2" data-label="YEAR LEVEL">
                                    <x-text-input id="year_{{ $index }}" name="education_history[{{ $index }}][year_level]" type="text" class="education-input px-2 py-1 text-sm rounded border border-gray-300" :value="old('education_history.'.$index.'.year_level', $row['year_level'])" />
                                </td>
                                <td class="px-3 py-2" data-label="DATE OF ATTENDANCE">
                                    <x-text-input id="date_{{ $index }}" name="education_history[{{ $index }}][date_attended]" type="text" class="education-input px-2 py-1 text-sm rounded border border-gray-300" :value="old('education_history.'.$index.'.date_attended', $row['date_attended'])" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Parents Information compact cards -->
        <div class="mb-4">
            <label class="block text-xs font-bold text-gray-600 uppercase mb-2">PARENTS INFORMATION</label>
            <div class="parents-card">
                <div class="p-3 border border-gray-200 rounded">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Father's Name</label>
                    <x-text-input id="father_name" name="father_name" type="text" class="px-2 py-2 text-sm rounded border border-gray-300" :value="old('father_name', optional($profile)->father_name ?? $user->father_name)" />
                    <label class="block text-xs font-semibold text-gray-600 mb-1 mt-3">Father's Contact No.</label>
                    <x-text-input id="father_contact_number" name="father_contact_number" type="text" class="px-2 py-2 text-sm rounded border border-gray-300" :Value="old('father_contact_number', optional($profile)->father_contact_number ?? $user->father_contact_number)" />
                    <label class="block text-xs font-semibold text-gray-600 mb-1 mt-3">Occupation</label>
                    <x-text-input id="father_occupation" name="father_occupation" type="text" class="px-2 py-2 text-sm rounded border border-gray-300" :value="old('father_occupation', optional($profile)->father_occupation ?? $user->father_occupation)" />
                </div>
                <div class="p-3 border border-gray-200 rounded">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Mother's Name</label>
                    <x-text-input id="mother_name" name="mother_name" type="text" class="px-2 py-2 text-sm rounded border border-gray-300" :value="old('mother_name', optional($profile)->mother_name ?? $user->mother_name)" />
                    <label class="block text-xs font-semibold text-gray-600 mb-1 mt-3">Mother's Contact No.</label>
                    <x-text-input id="mother_contact_number" name="mother_contact_number" type="text" class="px-2 py-2 text-sm rounded border border-gray-300" :value="old('mother_contact_number', optional($profile)->mother_contact_number ?? $user->mother_contact_number)" />
                    <label class="block text-xs font-semibold text-gray-600 mb-1 mt-3">Occupation</label>
                    <x-text-input id="mother_occupation" name="mother_occupation" type="text" class="px-2 py-2 text-sm rounded border border-gray-300" :value="old('mother_occupation', optional($profile)->mother_occupation ?? $user->mother_occupation)" />
                </div>
            </div>
        </div>

        <!-- CURRENT STATUS OF PARENTS -->
        <div class="mb-4">
            <label class="block text-xs font-bold text-gray-600 uppercase mb-2">CURRENT STATUS OF PARENTS</label>
            <div class="checkbox-grid">
                @php
                    $parentStatusOptions = [
                        'Living Together',
                        'Solo Parent',
                        'Orphan',
                        'Guardian',
                    ];
                    $selectedParentStatus = old('parent_status_details', optional($profile)->parent_status_details ?? $user->parent_status_details ?? []);
                @endphp

                @foreach ($parentStatusOptions as $statusOption)
                    <label>
                        <input type="checkbox" name="parent_status_details[]" value="{{ $statusOption }}" class="w-4 h-4 border border-gray-300 rounded" {{ in_array($statusOption, $selectedParentStatus) ? 'checked' : '' }}>
                        <span class="ml-1">{{ $statusOption }}</span>
                    </label>
                @endforeach
            </div>
            <x-input-error class="text-xs mt-1" :messages="$errors->get('parent_status_details')" />
        </div>

        <!-- SPECIAL SKILLS -->
        <div class="mb-4">
            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">SPECIAL SKILLS</label>
            <textarea id="special_skills" name="special_skills" rows="2" class="px-2 py-2 text-sm rounded border border-gray-300 text-gray-900" style="width:100%;">{{ old('special_skills', optional($profile)->special_skills ?? $user->special_skills) }}</textarea>
            <x-input-error class="text-xs mt-1" :messages="$errors->get('special_skills')" />
        </div>
        </fieldset>

        <!-- ACTIONS -->
        <div class="actions-row mt-4">
            @if ($readOnly)
                <a href="{{ route('profile.edit', ['edit' => 1]) }}" class="px-5 py-2 bg-white border border-blue-600 text-blue-600 font-semibold rounded hover:bg-blue-50">
                    Edit
                </a>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-black font-semibold rounded" disabled>
                    Save Profile & Continue
                </button>
            @else
                <button type="submit" class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-white border-2 border-green-500 text-green-600 font-semibold shadow-md hover:bg-green-500 hover:text-white hover:border-green-500 hover:shadow-xl hover:shadow-green-300/50 hover:-translate-y-1 active:scale-95 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-green-200">
                    Save Profile & Continue
                </button>
            @endif
        </div>

        @if (session('status') === 'profile-updated')
            <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded text-sm text-green-700">
                Profile updated successfully.
            </div>
        @endif
    </form>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const fileInput = document.getElementById('profile_photo');
            const previewImage = document.getElementById('profilePhotoPreview');
            const uploadPlaceholder = document.querySelector('.upload-placeholder');

            if (!fileInput) {
                return;
            }

            // Handle file selection
            fileInput.addEventListener('change', function (event) {
                const file = event.target.files && event.target.files[0];
                if (!file) {
                    return;
                }

                // Validate file is an image
                if (!file.type.startsWith('image/')) {
                    alert('Please select a valid image file.');
                    fileInput.value = '';
                    return;
                }

                // Validate file size (5MB max)
                if (file.size > 5120 * 1024) {
                    alert('File size must be less than 5MB.');
                    fileInput.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    if (previewImage) {
                        previewImage.src = e.target.result;
                        previewImage.style.display = 'block';
                    }
                    if (uploadPlaceholder) {
                        uploadPlaceholder.style.display = 'none';
                    }
                };
                reader.onerror = function () {
                    alert('Error reading file. Please try again.');
                    fileInput.value = '';
                };
                reader.readAsDataURL(file);
            });
        });
    </script>
</section>
