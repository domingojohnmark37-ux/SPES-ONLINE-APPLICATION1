<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SPES Application Form</title>
    <link rel="stylesheet" href="{{ asset('css/request-loading.css') }}?v={{ filemtime(public_path('css/request-loading.css')) }}">
    <style>
   
        body { font-family: "Arial Narrow", Arial, sans-serif; font-size: 11px; display: flex; justify-content: center; background: #999; padding: 20px; }
        .document { width: 8.5in; background: white; padding: 0.5in; box-sizing: border-box; border: 1px solid #000; }
        
        /* Header and Logos */
        .header-section { display: grid; grid-template-columns: 100px 1fr 100px; text-align: center; margin-bottom: 5px; }
        .header-text { line-height: 1.2; font-size: 10px; }
        .logo { width: 50px; height: auto; align-self: center;
           border-radius: 50%;;
    }
        .spes-form-id { text-align: right; font-weight: bold; font-size: 12px; }

        .title { text-align: center; font-size: 16px; font-weight: bold; margin: 10px 0; text-decoration: none; }
        .control-no { text-align: right; font-size: 10px; margin-bottom: 2px; }

        /* Table and Grids */
        table { width: 100%; border-collapse: collapse; margin-bottom: -1px; }
        td { border: 1px solid black; padding: 3px 5px; vertical-align: top; }
        .label { font-size: 9px; font-weight: bold; text-transform: uppercase; display: block; }
        
        /* Specific Layouts */
        .photo-box { width: 140px; height: 150px; text-align: center; display: flex; align-items: center; justify-content: center; font-size: 10px; border-left: 1px solid black; }
        .gray-bg { background-color: #e0e0e0; font-weight: bold; }
        .check-group { display: flex; flex-wrap: wrap; gap: 8px; font-size: 10px; margin-top: 3px; }
        
        /* Input Fields */
        input[type="text"], input[type="email"], input[type="date"], input[type="file"] {
            width: 100%;
            border: none;
            background: transparent;
            font-family: "Arial Narrow", Arial, sans-serif;
            font-size: 10px;
        }

        input[type="checkbox"] {
            margin-right: 3px;
        }
        
        /* Text Blocks */
        .req-section { font-size: 10px; line-height: 1.3; border: 1px solid black; border-top: none; padding: 5px 10px; }
        .legal-text { font-size: 10px; font-style: italic; text-align: justify; padding: 10px 0; }
        
        .signature-section { text-align: right; margin-top: 20px; }
        .sig-line { display: inline-block; width: 280px; border-top: 1px solid black; text-align: center; padding-top: 2px; }

        .form-buttons { text-align: center; margin-top: 20px; }
        .btn { padding: 10px 20px; margin: 0 10px; border: none; border-radius: 4px; font-size: 12px; cursor: pointer; }
        .btn-submit { background: #004d40; color: white; }
        .btn-submit:hover { background: #00332a; }
        .btn-reset { background: #999; color: white; }
        .btn-reset:hover { background: #777; }
        .logo
        {
            width: 120px;
        }
    </style>
</head>
<body>

<div class="document">
    <div class="spes-form-id">SPES Application Form</div>
    <div class="header-section">
          <img src="{{ asset('images/left-logo1.png') }}" alt="Dole logo" class="logo">
        <div class="header-text">
            REPUBLIC OF THE PHILIPPINES<br>
            <strong>DEPARTMENT OF LABOR AND EMPLOYMENT</strong><br>
            Regional Office No. _____________<br>
            <strong>PUBLIC EMPLOYMENT SERVICE OFFICE</strong><br><br>
            City/Municipality/Province<br>
            <strong>SPECIAL PROGRAM FOR EMPLOYMENT OF STUDENTS (SPES)</strong><br>
            (RA 7323, as amended by RAs 9547 and 10917)
        </div>
        <img src="{{ asset('images/peso_logo-1.png') }}" class="peso logo" alt="PESO Logo">
    </div>

    <div class="title">APPLICATION FORM</div>
    <div class="control-no">Control No.: ________________</div>

    <form action="{{ route('applications.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <table>
            <tr>
                <td colspan="2"><span class="label">SURNAME</span><input type="text" name="surname" value="{{ old('surname') }}"></td>
                <td colspan="2"><span class="label">FIRST NAME</span><input type="text" name="first_name" value="{{ old('first_name') }}"></td>
                <td colspan="2"><span class="label">MIDDLE NAME</span><input type="text" name="middle_name" value="{{ old('middle_name') }}"></td>
                <td colspan="2"><span class="label">GSIS BENEFICIARY/RELATIONSHIP</span><input type="text" name="gsis_beneficiary" value="{{ old('gsis_beneficiary') }}"></td>
                <td rowspan="4" style="padding: 0; width: 140px;">
                    <div class="photo-box">Passport Size Picture<br>(3.5cm x 4.5 cm)</div>
                </td>
            </tr>
            <tr>
                <td colspan="2"><span class="label">DATE OF BIRTH: (mm/dd/yyyy)</span><input type="date" name="birth_date" value="{{ old('birth_date') }}"></td>
                <td colspan="3"><span class="label">PLACE OF BIRTH:</span><input type="text" name="birth_place" value="{{ old('birth_place') }}"></td>
                <td colspan="3"><span class="label">CITIZENSHIP:</span><input type="text" name="citizenship" value="{{ old('citizenship') }}"></td>
            </tr>
            <tr>
                <td colspan="3"><span class="label">CONTACT DETAILS/CELPHONE NO.:</span><input type="text" name="phone" value="{{ old('phone') }}"></td>
                <td colspan="5"><span class="label">EMAIL ADDRESS:</span><input type="email" name="email" value="{{ old('email') }}"></td>
            </tr>
            <tr>
                <td colspan="8"><span class="label">SOCIAL MEDIA ACCOUNT (FACEBOOK, TWITTER, INSTAGRAM, ETC.)</span><input type="text" name="social_media" value="{{ old('social_media') }}"></td>
            </tr>
            <tr>
                <td colspan="4">
                    <span class="label">STATUS</span>
                    <div class="check-group">
                        @php
                            $oldStatus = old('status');
                            $statusArray = is_array($oldStatus) ? $oldStatus : ($oldStatus ? [$oldStatus] : []);
                        @endphp
                        <label><input type="checkbox" name="status[]" value="Single" {{ in_array('Single', $statusArray) ? 'checked' : '' }}> Single</label>
                        <label><input type="checkbox" name="status[]" value="Married" {{ in_array('Married', $statusArray) ? 'checked' : '' }}> Married</label>
                        <label><input type="checkbox" name="status[]" value="Widow/er" {{ in_array('Widow/er', $statusArray) ? 'checked' : '' }}> Widow/er</label>
                        <label><input type="checkbox" name="status[]" value="Separated" {{ in_array('Separated', $statusArray) ? 'checked' : '' }}> Separated</label>
                    </div>
                </td>
                <td colspan="2">
                    <span class="label">SEX</span>
                    <div class="check-group">
                        @php
                            $oldSex = old('sex');
                            $sexArray = is_array($oldSex) ? $oldSex : ($oldSex ? [$oldSex] : []);
                        @endphp
                        <label><input type="checkbox" name="sex[]" value="Male" {{ in_array('Male', $sexArray) ? 'checked' : '' }}> Male</label>
                        <label><input type="checkbox" name="sex[]" value="Female" {{ in_array('Female', $sexArray) ? 'checked' : '' }}> Female</label>
                    </div>
                </td>
                <td colspan="3">
                    @php
                        $oldCategory = old('category');
                        $categoryArray = is_array($oldCategory) ? $oldCategory : ($oldCategory ? [$oldCategory] : []);
                    @endphp
                    <div class="check-group">
                        <label><input type="checkbox" name="category[]" value="Student" {{ in_array('Student', $categoryArray) ? 'checked' : '' }}> Student</label>
                        <label><input type="checkbox" name="category[]" value="ALS student" {{ in_array('ALS student', $categoryArray) ? 'checked' : '' }}> ALS student</label>
                        <label><input type="checkbox" name="category[]" value="out-of-school (OSY)" {{ in_array('out-of-school (OSY)', $categoryArray) ? 'checked' : '' }}> out-of-school (OSY)</label>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="9">
                    <span class="label">CURRENT STATUS OF PARENTS:</span>
                    <div class="check-group">
                        @php
                            $oldParentStatus = old('parent_status');
                            $parentStatusArray = is_array($oldParentStatus) ? $oldParentStatus : ($oldParentStatus ? [$oldParentStatus] : []);
                        @endphp
                        <label><input type="checkbox" name="parent_status[]" value="Living together" {{ in_array('Living together', $parentStatusArray) ? 'checked' : '' }}> Living together</label>
                        <label><input type="checkbox" name="parent_status[]" value="Solo Parent" {{ in_array('Solo Parent', $parentStatusArray) ? 'checked' : '' }}> Solo Parent</label>
                        <label><input type="checkbox" name="parent_status[]" value="Separated" {{ in_array('Separated', $parentStatusArray) ? 'checked' : '' }}> Separated</label>
                        <label><input type="checkbox" name="parent_status[]" value="Person With Disability" {{ in_array('Person With Disability', $parentStatusArray) ? 'checked' : '' }}> Person With Disability</label>
                        <label><input type="checkbox" name="parent_status[]" value="Senior Citizen" {{ in_array('Senior Citizen', $parentStatusArray) ? 'checked' : '' }}> Senior Citizen</label>
                        <label><input type="checkbox" name="parent_status[]" value="Sugar Plantation Worker" {{ in_array('Sugar Plantation Worker', $parentStatusArray) ? 'checked' : '' }}> Sugar Plantation Worker</label>
                        <label><input type="checkbox" name="parent_status[]" value="Indigenous People" {{ in_array('Indigenous People', $parentStatusArray) ? 'checked' : '' }}> Indigenous People</label>
                        <label><input type="checkbox" name="parent_status[]" value="Displaced Worker" {{ in_array('Displaced Worker', $parentStatusArray) ? 'checked' : '' }}> Displaced Worker</label>
                        <label>(1)<input type="checkbox" name="parent_status[]" value="Local" {{ in_array('Local', $parentStatusArray) ? 'checked' : '' }}> Local</label>
                        <label>(2)<input type="checkbox" name="parent_status[]" value="OFW" {{ in_array('OFW', $parentStatusArray) ? 'checked' : '' }}> OFW</label>
                    </div>
                </td>
            </tr>
            <tr><td colspan="9"><span class="label">PRESENT ADDRESS:</span><input type="text" name="present_address" value="{{ old('present_address') }}"></td></tr>
            <tr><td colspan="9"><span class="label">PERMANENT ADDRESS:</span><input type="text" name="permanent_address" value="{{ old('permanent_address') }}"></td></tr>
            <tr>
                <td colspan="5"><span class="label">FATHER'S NAME / CONTACT NO.:</span><input type="text" name="father_info" value="{{ old('father_info') }}"></td>
                <td colspan="4"><span class="label">MOTHER'S MAIDEN NAME / CONTACT NO.:</span><input type="text" name="mother_info" value="{{ old('mother_info') }}"></td>
            </tr>
            <tr>
                <td colspan="5"><span class="label">OCCUPATION:</span><input type="text" name="father_occupation" value="{{ old('father_occupation') }}"></td>
                <td colspan="4"><span class="label">OCCUPATION:</span><input type="text" name="mother_occupation" value="{{ old('mother_occupation') }}"></td>
            </tr>
            <tr class="gray-bg" style="text-align: center;">
                <td style="width: 15%;">EDUCATION</td>
                <td colspan="3">NAME OF SCHOOL</td>
                <td colspan="2">DEGREE EARNED/COURSE</td>
                <td style="width: 10%;">YEAR/LEVEL</td>
                <td colspan="2">DATE OF ATTENDANCE</td>
            </tr>
            <tr>
                <td>Elementary</td>
                <td colspan="3"><input type="text" name="elementary_school" value="{{ old('elementary_school') }}"></td>
                <td colspan="2"><input type="text" name="elementary_degree" value="{{ old('elementary_degree') }}"></td>
                <td><input type="text" name="elementary_year" value="{{ old('elementary_year') }}"></td>
                <td colspan="2"><input type="date" name="elementary_date" value="{{ old('elementary_date') }}"></td>
            </tr>
            <tr>
                <td>Secondary</td>
                <td colspan="3"><input type="text" name="secondary_school" value="{{ old('secondary_school') }}"></td>
                <td colspan="2"><input type="text" name="secondary_degree" value="{{ old('secondary_degree') }}"></td>
                <td><input type="text" name="secondary_year" value="{{ old('secondary_year') }}"></td>
                <td colspan="2"><input type="date" name="secondary_date" value="{{ old('secondary_date') }}"></td>
            </tr>
            <tr>
                <td>Tertiary</td>
                <td colspan="3"><input type="text" name="tertiary_school" value="{{ old('tertiary_school') }}"></td>
                <td colspan="2"><input type="text" name="tertiary_degree" value="{{ old('tertiary_degree') }}"></td>
                <td><input type="text" name="tertiary_year" value="{{ old('tertiary_year') }}"></td>
                <td colspan="2"><input type="date" name="tertiary_date" value="{{ old('tertiary_date') }}"></td>
            </tr>
            <tr>
                <td>Tech-Voc</td>
                <td colspan="3"><input type="text" name="techvoc_school" value="{{ old('techvoc_school') }}"></td>
                <td colspan="2"><input type="text" name="techvoc_degree" value="{{ old('techvoc_degree') }}"></td>
                <td><input type="text" name="techvoc_year" value="{{ old('techvoc_year') }}"></td>
                <td colspan="2"><input type="date" name="techvoc_date" value="{{ old('techvoc_date') }}"></td>
            </tr>
        </table>

        <div class="req-section">
            <strong>DOCUMENTARY REQUIREMENTS:</strong><br>
            (Original and other documents, when applicable, should be presented for validation)<br>
            @php
                $docReq = old('doc_requirements');
                $docArray = is_array($docReq) ? $docReq : ($docReq ? [$docReq] : []);
            @endphp
            <label><input type="checkbox" name="doc_requirements[]" value="birth_cert" {{ in_array('birth_cert', $docArray) ? 'checked' : '' }}> [ ] 1) Photocopy of Birth Certificate or any document indicating date of birth or age (age must be 15-30)</label><br>
            <label><input type="checkbox" name="doc_requirements[]" value="income_tax" {{ in_array('income_tax', $docArray) ? 'checked' : '' }}> [ ] 2) Photocopy of the latest Income Tax Return (ITR) of parents/legal guardian <strong>OR</strong> certification issued by BIR that the parents/guardians are exempted from payment of tax <strong>OR</strong> original Certificate of Indigence <strong>OR</strong> original Certificate of Low Income issued by the Barangay/DSWD or CSWD where the applicant resides; and</label><br>
            <label><input type="checkbox" name="doc_requirements[]" value="student_docs" {{ in_array('student_docs', $docArray) ? 'checked' : '' }}> [ ] 3) <strong>For students</strong>, any of the following, in addition to requirements no. 1 and 2:</label><br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label><input type="checkbox" name="doc_requirements[]" value="grade_avg" {{ in_array('grade_avg', $docArray) ? 'checked' : '' }}> [ ] a) Photocopy of proof of average passing grade such as (1) class card or (2) Form 138 of the previous semester or year immediately preceding the application; <strong>OR</strong></label><br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label><input type="checkbox" name="doc_requirements[]" value="cert_grade" {{ in_array('cert_grade', $docArray) ? 'checked' : '' }}> [ ] b) Original copy of Certification by the School Registrar as to passing grade immediately preceding semester/year if grades are not yet available</label><br>
            <label><input type="checkbox" name="doc_requirements[]" value="osy_cert" {{ in_array('osy_cert', $docArray) ? 'checked' : '' }}> [ ] 4) <strong>For Out of School Youth (OSY)</strong>, original copy of Certification as OSY issued by DSWD/CSWD or the authorized Barangay Official where the OSY resides, in addition to requirements no. 1 and 2.</label>
        </div>

        <table style="margin-top: -1px;">
            <tr class="gray-bg">    
                <td colspan="4"><span class="label">SPECIAL SKILLS:</span><input type="text" name="special_skills" style="margin-top: 5px;" value="{{ old('special_skills') }}"></td>
            </tr>
            <tr class="gray-bg" style="text-align: center; font-size: 9px;">
                <td colspan="2">HISTORY of SPES Availment/ Name of Establishment</td>
                <td>YEAR</td>
                <td>SPES ID NO. (if applicable)</td>
            </tr>
            <tr>
                <td colspan="2">
                    @php
                        $spesAvail = old('spes_availment');
                        $spesAvailArray = is_array($spesAvail) ? $spesAvail : ($spesAvail ? [$spesAvail] : []);
                    @endphp
                    <label><input type="checkbox" name="spes_availment[]" value="1st" {{ in_array('1st', $spesAvailArray) ? 'checked' : '' }}> [ ] 1st Availment-</label><input type="text" name="spes_1st" value="{{ old('spes_1st') }}" style="width: 100%;">
                </td>
                <td><input type="text" name="spes_1st_year" value="{{ old('spes_1st_year') }}"></td>
                <td><input type="text" name="spes_1st_id" value="{{ old('spes_1st_id') }}"></td>
            </tr>
            <tr>
                <td colspan="2"><label><input type="checkbox" name="spes_availment[]" value="2nd" {{ in_array('2nd', $spesAvailArray) ? 'checked' : '' }}> [ ] 2nd Availment-</label><input type="text" name="spes_2nd" value="{{ old('spes_2nd') }}" style="width: 100%;"></td>
                <td><input type="text" name="spes_2nd_year" value="{{ old('spes_2nd_year') }}"></td>
                <td><input type="text" name="spes_2nd_id" value="{{ old('spes_2nd_id') }}"></td>
            </tr>
            <tr>
                <td colspan="2"><label><input type="checkbox" name="spes_availment[]" value="3rd" {{ in_array('3rd', $spesAvailArray) ? 'checked' : '' }}> [ ] 3rd Availment-</label><input type="text" name="spes_3rd" value="{{ old('spes_3rd') }}" style="width: 100%;"></td>
                <td><input type="text" name="spes_3rd_year" value="{{ old('spes_3rd_year') }}"></td>
                <td><input type="text" name="spes_3rd_id" value="{{ old('spes_3rd_id') }}"></td>
            </tr>
            <tr>
                <td colspan="2"><label><input type="checkbox" name="spes_availment[]" value="4th" {{ in_array('4th', $spesAvailArray) ? 'checked' : '' }}> [ ] 4th Availment-</label><input type="text" name="spes_4th" value="{{ old('spes_4th') }}" style="width: 100%;"></td>
                <td><input type="text" name="spes_4th_year" value="{{ old('spes_4th_year') }}"></td>
                <td><input type="text" name="spes_4th_id" value="{{ old('spes_4th_id') }}"></td>
            </tr>
            <tr>
                <td colspan="4"><span class="label">Other related information/ requests/ interventions from DOLE:</span><textarea name="other_info" style="width: 100%; height: 60px; border: none; background: transparent; font-family: Arial Narrow, Arial, sans-serif; font-size: 10px;">{{ old('other_info') }}</textarea></td>
            </tr>
        </table>

        <div class="legal-text">
            <strong>CERTIFICATION:</strong> I hereby attest that the information above are true and correct to the best of my knowledge, including the attached documents/requirements which I also attest as to their veracity. I agree that any false statement would cause the automatic disqualification/ cancellation of the service/ contract/ grant and I shall refund amount received and/or pay damages to DOLE or comply with other sanctions in accordance with law. Any material change in my financial status may affect my eligibility to continue the program.
            <br><br>
            <strong>PRIVACY CONSENT NOTICE:</strong> By submitting this form, you agree that DOLE Region 02 will use your information solely for documentation and processing purposes. Your data maybe disclose as required by the Data Privacy Act of 2012. We will retain your information only as long as needed for its intended purpose. By signing, you acknowledge and agree to this data use and retention.
        </div>

        <div style="margin: 15px 0;">
            <span class="label">UPLOAD REQUIREMENTS (Multiple files allowed):</span>
            <input type="file" name="files[]" multiple style="border: 1px solid black; padding: 5px;">
            @error('files.*')<p style="color: red; font-size: 10px;">{{ $message }}</p>@enderror
        </div>

        <div class="signature-section">
            <div class="sig-line">Signature of Applicant / Date</div>
            <div style="font-size: 9px; margin-top: 5px;">BLE Revision as of <u>December 2016</u></div>
        </div>

        <div class="form-buttons">
            <button type="submit" class="btn btn-submit"><x-icon class="fas fa-paper-plane" /> Submit Application</button>
            <button type="reset" class="btn btn-reset">Clear Form</button>
        </div>
    </form>

    @if ($errors->any())
        <div style="background: #ffebee; border: 1px solid #ef5350; padding: 10px; margin-top: 10px; border-radius: 4px;">
            <strong style="color: #c62828;">Please correct the following errors:</strong>
            <ul style="margin: 5px 0 0 20px; color: #c62828;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

<script src="{{ asset('js/request-loading.js') }}?v={{ filemtime(public_path('js/request-loading.js')) }}"></script>
</body>
</html>
