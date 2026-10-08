<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>View Application Form - {{ $application->applicant_name }}</title>
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
        
        /* Display Fields */
        .display-field {
            width: 100%;
            border: none;
            background: transparent;
            font-family: "Arial Narrow", Arial, sans-serif;
            font-size: 10px;
            padding: 3px 5px;
            color: #333;
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
        .btn-print { background: #004d40; color: white; }
        .btn-print:hover { background: #00332a; }
        .btn-download { background: #1976d2; color: white; }
        .btn-download:hover { background: #1565c0; }
        .btn-back { background: #999; color: white; }
        .btn-back:hover { background: #777; }
        .logo
        {
            width: 120px;
        }

        .view-header {
            background: #004d40;
            color: white;
            padding: 15px 20px;
            margin: -0.5in -0.5in 15px -0.5in;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .view-header h2 {
            margin: 0;
            font-size: 18px;
        }

        @media print {
            @page {
                size: A4;
                margin: 0.3in 0.3in 0.3in 0.3in;
            }

            * {
                margin: 0 !important;
                padding: 0 !important;
                page-break-inside: avoid !important;
            }

            html, body {
                width: 100% !important;
                height: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
            }

            body {
                font-size: 9.5px !important;
            }

            .view-header {
                display: none !important;
            }

            .document {
                border: none !important;
                padding: 0.3in !important;
                width: 100% !important;
                box-sizing: border-box !important;
                margin: 0 !important;
            }

            .spes-form-id {
                font-size: 10px !important;
                margin-bottom: 2px !important;
            }

            .header-section {
                margin-bottom: 2px !important;
                grid-template-columns: 70px 1fr 70px !important;
            }

            .header-text {
                line-height: 1 !important;
                font-size: 8px !important;
            }

            .logo {
                width: 70px !important;
            }

            .title {
                font-size: 13px !important;
                margin: 2px 0 !important;
                font-weight: bold !important;
            }

            .control-no {
                font-size: 9px !important;
                margin-bottom: 1px !important;
            }

            table {
                margin-bottom: -1px !important;
                page-break-inside: avoid !important;
            }

            td {
                border: 1px solid black !important;
                padding: 2px 3px !important;
                vertical-align: top !important;
                font-size: 9px !important;
            }

            .label {
                font-size: 7.5px !important;
                font-weight: bold !important;
                text-transform: uppercase !important;
                display: block !important;
                margin-bottom: 1px !important;
            }

            .display-field {
                width: 100% !important;
                border: none !important;
                background: transparent !important;
                font-family: "Arial Narrow", Arial, sans-serif !important;
                font-size: 8.5px !important;
                padding: 1px 2px !important;
                color: #333 !important;
            }

            .photo-box {
                width: 100px !important;
                height: 110px !important;
                font-size: 8px !important;
                border-left: 1px solid black !important;
            }

            .gray-bg {
                background-color: white !important;
                font-weight: bold !important;
            }

            .check-group {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 3px !important;
                font-size: 8.5px !important;
                margin-top: 1px !important;
            }

            input[type="checkbox"] {
                margin-right: 2px !important;
                width: 12px !important;
                height: 12px !important;
            }

            .req-section {
                font-size: 8.5px !important;
                line-height: 1.15 !important;
                border: 1px solid black !important;
                border-top: none !important;
                padding: 3px 5px !important;
                page-break-inside: avoid !important;
            }

            .legal-text {
                font-size: 8px !important;
                font-style: italic !important;
                text-align: justify !important;
                padding: 3px 0 !important;
                line-height: 1.15 !important;
            }

            .signature-section {
                text-align: right !important;
                margin-top: 5px !important;
            }

            .sig-line {
                display: inline-block !important;
                width: 200px !important;
                border-top: 1px solid black !important;
                text-align: center !important;
                padding-top: 0 !important;
                font-size: 8px !important;
            }

            .form-buttons {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="document">
    <div class="view-header">
        <h2>View Application Form</h2>
        <div>
            <a href="{{ route('admin.applications.export', $application->id) }}" class="btn btn-download"><i class="fas fa-download"></i> Download</a>
            <button onclick="window.print()" class="btn btn-print"><i class="fas fa-print"></i> Print</button>
            <a href="{{ url()->previous() }}" class="btn btn-back"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>

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

    <table>
        <tr>
            <td colspan="2"><span class="label">SURNAME</span><div class="display-field">{{ $application->form_data['surname'] ?? '' }}</div></td>
            <td colspan="2"><span class="label">FIRST NAME</span><div class="display-field">{{ $application->form_data['first_name'] ?? '' }}</div></td>
            <td colspan="2"><span class="label">MIDDLE NAME</span><div class="display-field">{{ $application->form_data['middle_name'] ?? '' }}</div></td>
            <td colspan="2"><span class="label">GSIS BENEFICIARY/RELATIONSHIP</span><div class="display-field">{{ $application->form_data['gsis_beneficiary'] ?? '' }}</div></td>
            <td rowspan="4" style="padding: 0; width: 140px;">
                <div class="photo-box">Passport Size Picture<br>(3.5cm x 4.5 cm)</div>
            </td>
        </tr>
        <tr>
            <td colspan="2"><span class="label">DATE OF BIRTH: (mm/dd/yyyy)</span><div class="display-field">{{ $application->form_data['birth_date'] ?? '' }}</div></td>
            <td colspan="3"><span class="label">PLACE OF BIRTH:</span><div class="display-field">{{ $application->form_data['birth_place'] ?? '' }}</div></td>
            <td colspan="3"><span class="label">CITIZENSHIP:</span><div class="display-field">{{ $application->form_data['citizenship'] ?? '' }}</div></td>
        </tr>
        <tr>
            <td colspan="3"><span class="label">CONTACT DETAILS/CELPHONE NO.:</span><div class="display-field">{{ $application->form_data['phone'] ?? '' }}</div></td>
            <td colspan="5"><span class="label">EMAIL ADDRESS:</span><div class="display-field">{{ $application->form_data['email'] ?? '' }}</div></td>
        </tr>
        <tr>
            <td colspan="8"><span class="label">SOCIAL MEDIA ACCOUNT (FACEBOOK, TWITTER, INSTAGRAM, ETC.)</span><div class="display-field">{{ $application->form_data['social_media'] ?? '' }}</div></td>
        </tr>
        <tr>
            <td colspan="4">
                <span class="label">STATUS</span>
                <div class="check-group">
                    @php
                        $status = $application->form_data['status'] ?? null;
                        $statusArray = is_array($status) ? $status : ($status ? [$status] : []);
                    @endphp
                    <label><input type="checkbox" {{ in_array('Single', $statusArray) ? 'checked' : '' }} disabled> Single</label>
                    <label><input type="checkbox" {{ in_array('Married', $statusArray) ? 'checked' : '' }} disabled> Married</label>
                    <label><input type="checkbox" {{ in_array('Widow/er', $statusArray) ? 'checked' : '' }} disabled> Widow/er</label>
                    <label><input type="checkbox" {{ in_array('Separated', $statusArray) ? 'checked' : '' }} disabled> Separated</label>
                </div>
            </td>
            <td colspan="2">
                <span class="label">SEX</span>
                <div class="check-group">
                    @php
                        $sex = $application->form_data['sex'] ?? null;
                        $sexArray = is_array($sex) ? $sex : ($sex ? [$sex] : []);
                    @endphp
                    <label><input type="checkbox" {{ in_array('Male', $sexArray) ? 'checked' : '' }} disabled> Male</label>
                    <label><input type="checkbox" {{ in_array('Female', $sexArray) ? 'checked' : '' }} disabled> Female</label>
                </div>
            </td>
            <td colspan="3">
                @php
                    $category = $application->form_data['category'] ?? null;
                    $categoryArray = is_array($category) ? $category : ($category ? [$category] : []);
                @endphp
                <div class="check-group">
                    <label><input type="checkbox" {{ in_array('Student', $categoryArray) ? 'checked' : '' }} disabled> Student</label>
                    <label><input type="checkbox" {{ in_array('ALS student', $categoryArray) ? 'checked' : '' }} disabled> ALS student</label>
                    <label><input type="checkbox" {{ in_array('out-of-school (OSY)', $categoryArray) ? 'checked' : '' }} disabled> out-of-school (OSY)</label>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="9">
                <span class="label">CURRENT STATUS OF PARENTS:</span>
                <div class="check-group">
                    @php
                        $parentStatus = $application->form_data['parent_status'] ?? null;
                        $parentStatusArray = is_array($parentStatus) ? $parentStatus : ($parentStatus ? [$parentStatus] : []);
                    @endphp
                    <label><input type="checkbox" {{ in_array('Living together', $parentStatusArray) ? 'checked' : '' }} disabled> Living together</label>
                    <label><input type="checkbox" {{ in_array('Solo Parent', $parentStatusArray) ? 'checked' : '' }} disabled> Solo Parent</label>
                    <label><input type="checkbox" {{ in_array('Separated', $parentStatusArray) ? 'checked' : '' }} disabled> Separated</label>
                    <label><input type="checkbox" {{ in_array('Person With Disability', $parentStatusArray) ? 'checked' : '' }} disabled> Person With Disability</label>
                    <label><input type="checkbox" {{ in_array('Senior Citizen', $parentStatusArray) ? 'checked' : '' }} disabled> Senior Citizen</label>
                    <label><input type="checkbox" {{ in_array('Sugar Plantation Worker', $parentStatusArray) ? 'checked' : '' }} disabled> Sugar Plantation Worker</label>
                    <label><input type="checkbox" {{ in_array('Indigenous People', $parentStatusArray) ? 'checked' : '' }} disabled> Indigenous People</label>
                    <label><input type="checkbox" {{ in_array('Displaced Worker', $parentStatusArray) ? 'checked' : '' }} disabled> Displaced Worker</label>
                    <label>(1)<input type="checkbox" {{ in_array('Local', $parentStatusArray) ? 'checked' : '' }} disabled> Local</label>
                    <label>(2)<input type="checkbox" {{ in_array('OFW', $parentStatusArray) ? 'checked' : '' }} disabled> OFW</label>
                </div>
            </td>
        </tr>
        <tr><td colspan="9"><span class="label">PRESENT ADDRESS:</span><div class="display-field">{{ $application->form_data['present_address'] ?? '' }}</div></td></tr>
        <tr><td colspan="9"><span class="label">PERMANENT ADDRESS:</span><div class="display-field">{{ $application->form_data['permanent_address'] ?? '' }}</div></td></tr>
        <tr>
            <td colspan="5"><span class="label">FATHER'S NAME / CONTACT NO.:</span><div class="display-field">{{ $application->form_data['father_info'] ?? '' }}</div></td>
            <td colspan="4"><span class="label">MOTHER'S MAIDEN NAME / CONTACT NO.:</span><div class="display-field">{{ $application->form_data['mother_info'] ?? '' }}</div></td>
        </tr>
        <tr>
            <td colspan="5"><span class="label">OCCUPATION:</span><div class="display-field">{{ $application->form_data['father_occupation'] ?? '' }}</div></td>
            <td colspan="4"><span class="label">OCCUPATION:</span><div class="display-field">{{ $application->form_data['mother_occupation'] ?? '' }}</div></td>
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
            <td colspan="3"><div class="display-field">{{ $application->form_data['elementary_school'] ?? '' }}</div></td>
            <td colspan="2"><div class="display-field">{{ $application->form_data['elementary_degree'] ?? '' }}</div></td>
            <td><div class="display-field">{{ $application->form_data['elementary_year'] ?? '' }}</div></td>
            <td colspan="2"><div class="display-field">{{ $application->form_data['elementary_date'] ?? '' }}</div></td>
        </tr>
        <tr>
            <td>Secondary</td>
            <td colspan="3"><div class="display-field">{{ $application->form_data['secondary_school'] ?? '' }}</div></td>
            <td colspan="2"><div class="display-field">{{ $application->form_data['secondary_degree'] ?? '' }}</div></td>
            <td><div class="display-field">{{ $application->form_data['secondary_year'] ?? '' }}</div></td>
            <td colspan="2"><div class="display-field">{{ $application->form_data['secondary_date'] ?? '' }}</div></td>
        </tr>
        <tr>
            <td>Tertiary</td>
            <td colspan="3"><div class="display-field">{{ $application->form_data['tertiary_school'] ?? '' }}</div></td>
            <td colspan="2"><div class="display-field">{{ $application->form_data['tertiary_degree'] ?? '' }}</div></td>
            <td><div class="display-field">{{ $application->form_data['tertiary_year'] ?? '' }}</div></td>
            <td colspan="2"><div class="display-field">{{ $application->form_data['tertiary_date'] ?? '' }}</div></td>
        </tr>
        <tr>
            <td>Tech-Voc</td>
            <td colspan="3"><div class="display-field">{{ $application->form_data['techvoc_school'] ?? '' }}</div></td>
            <td colspan="2"><div class="display-field">{{ $application->form_data['techvoc_degree'] ?? '' }}</div></td>
            <td><div class="display-field">{{ $application->form_data['techvoc_year'] ?? '' }}</div></td>
            <td colspan="2"><div class="display-field">{{ $application->form_data['techvoc_date'] ?? '' }}</div></td>
        </tr>
    </table>

    <div class="req-section">
        <strong>DOCUMENTARY REQUIREMENTS:</strong><br>
        (Original and other documents, when applicable, should be presented for validation)<br>
        @php
            $docReq = $application->form_data['doc_requirements'] ?? null;
            $docArray = is_array($docReq) ? $docReq : ($docReq ? [$docReq] : []);
        @endphp
        <label><input type="checkbox" {{ in_array('birth_cert', $docArray) ? 'checked' : '' }} disabled> [ ] 1) Photocopy of Birth Certificate or any document indicating date of birth or age (age must be 15-30)</label><br>
        <label><input type="checkbox" {{ in_array('income_tax', $docArray) ? 'checked' : '' }} disabled> [ ] 2) Photocopy of the latest Income Tax Return (ITR) of parents/legal guardian <strong>OR</strong> certification issued by BIR that the parents/guardians are exempted from payment of tax <strong>OR</strong> original Certificate of Indigence <strong>OR</strong> original Certificate of Low Income issued by the Barangay/DSWD or CSWD where the applicant resides; and</label><br>
        <label><input type="checkbox" {{ in_array('student_docs', $docArray) ? 'checked' : '' }} disabled> [ ] 3) <strong>For students</strong>, any of the following, in addition to requirements no. 1 and 2:</label><br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label><input type="checkbox" {{ in_array('grade_avg', $docArray) ? 'checked' : '' }} disabled> [ ] a) Photocopy of proof of average passing grade such as (1) class card or (2) Form 138 of the previous semester or year immediately preceding the application; <strong>OR</strong></label><br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label><input type="checkbox" {{ in_array('cert_grade', $docArray) ? 'checked' : '' }} disabled> [ ] b) Original copy of Certification by the School Registrar as to passing grade immediately preceding semester/year if grades are not yet available</label><br>
        <label><input type="checkbox" {{ in_array('osy_cert', $docArray) ? 'checked' : '' }} disabled> [ ] 4) <strong>For Out of School Youth (OSY)</strong>, original copy of Certification as OSY issued by DSWD/CSWD or the authorized Barangay Official where the OSY resides, in addition to requirements no. 1 and 2.</label>
    </div>

    <table style="margin-top: -1px;">
        <tr class="gray-bg">    
            <td colspan="4"><span class="label">SPECIAL SKILLS:</span><div class="display-field" style="margin-top: 5px;">{{ $application->form_data['special_skills'] ?? '' }}</div></td>
        </tr>
        <tr class="gray-bg" style="text-align: center; font-size: 9px;">
            <td colspan="2">HISTORY of SPES Availment/ Name of Establishment</td>
            <td>YEAR</td>
            <td>SPES ID NO. (if applicable)</td>
        </tr>
        <tr>
            <td colspan="2">
                @php
                    $spesAvail = $application->form_data['spes_availment'] ?? null;
                    $spesAvailArray = is_array($spesAvail) ? $spesAvail : ($spesAvail ? [$spesAvail] : []);
                @endphp
                <label><input type="checkbox" {{ in_array('1st', $spesAvailArray) ? 'checked' : '' }} disabled> [ ] 1st Availment-</label><div class="display-field">{{ $application->form_data['spes_1st'] ?? '' }}</div>
            </td>
            <td><div class="display-field">{{ $application->form_data['spes_1st_year'] ?? '' }}</div></td>
            <td><div class="display-field">{{ $application->form_data['spes_1st_id'] ?? '' }}</div></td>
        </tr>
        <tr>
            <td colspan="2">
                <label><input type="checkbox" {{ in_array('2nd', $spesAvailArray) ? 'checked' : '' }} disabled> [ ] 2nd Availment-</label><div class="display-field">{{ $application->form_data['spes_2nd'] ?? '' }}</div>
            </td>
            <td><div class="display-field">{{ $application->form_data['spes_2nd_year'] ?? '' }}</div></td>
            <td><div class="display-field">{{ $application->form_data['spes_2nd_id'] ?? '' }}</div></td>
        </tr>
        <tr>
            <td colspan="2">
                <label><input type="checkbox" {{ in_array('3rd', $spesAvailArray) ? 'checked' : '' }} disabled> [ ] 3rd Availment-</label><div class="display-field">{{ $application->form_data['spes_3rd'] ?? '' }}</div>
            </td>
            <td><div class="display-field">{{ $application->form_data['spes_3rd_year'] ?? '' }}</div></td>
            <td><div class="display-field">{{ $application->form_data['spes_3rd_id'] ?? '' }}</div></td>
        </tr>
        <tr>
            <td colspan="2">
                <label><input type="checkbox" {{ in_array('4th', $spesAvailArray) ? 'checked' : '' }} disabled> [ ] 4th Availment-</label><div class="display-field">{{ $application->form_data['spes_4th'] ?? '' }}</div>
            </td>
            <td><div class="display-field">{{ $application->form_data['spes_4th_year'] ?? '' }}</div></td>
            <td><div class="display-field">{{ $application->form_data['spes_4th_id'] ?? '' }}</div></td>
        </tr>
        <tr>
            <td colspan="4"><span class="label">Other related information/ requests/ interventions from DOLE:</span><div class="display-field" style="border: none; background: transparent; height: 60px; overflow: hidden;">{{ $application->form_data['other_info'] ?? '' }}</div></td>
        </tr>
    </table>

    <div class="legal-text">
        <strong>CERTIFICATION:</strong> I hereby attest that the information above are true and correct to the best of my knowledge, including the attached documents/requirements which I also attest as to their veracity. I agree that any false statement would cause the automatic disqualification/ cancellation of the service/ contract/ grant and I shall refund amount received and/or pay damages to DOLE or comply with other sanctions in accordance with law. Any material change in my financial status may affect my eligibility to continue the program.
        <br><br>
        <strong>PRIVACY CONSENT NOTICE:</strong> By submitting this form, you agree that DOLE Region 02 will use your information solely for documentation and processing purposes. Your data maybe disclose as required by the Data Privacy Act of 2012. We will retain your information only as long as needed for its intended purpose. By signing, you acknowledge and agree to this data use and retention.
    </div>

    <div class="signature-section">
        <div class="sig-line">Signature of Applicant / Date</div>
        <div style="font-size: 9px; margin-top: 5px;">BLE Revision as of <u>December 2016</u></div>
    </div>
</div>

</body>
</html>
