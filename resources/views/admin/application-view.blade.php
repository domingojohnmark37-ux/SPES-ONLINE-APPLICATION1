<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SPES Application Form - View</title>
    <link rel="stylesheet" href="{{ asset('css/form.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
        }
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
        }
        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 50px 20px;
            margin: 0;
            background-color: #f5f5f5;
        }
        .document {
            position: relative;
            max-width: 850px;
            width: 100%;
            background-color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .view-header {
            display: none;
        }
        .back-btn, .print-btn {
            background: #2196f3;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 11px;
            margin: 0 5px;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .back-btn:hover, .print-btn:hover {
            background: #1976d2;
        }
        .bottom-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #004d40;
            width: 100%;
        }
        input[type="text"],
        input[type="email"],
        input[type="date"],
        textarea {
            background-color: #f9f9f9 !important;
            color: #666 !important;
            cursor: not-allowed;
        }
        input[type="checkbox"],
        input[type="radio"] {
            cursor: not-allowed;
            opacity: 0.7;
        }
        .form-buttons {
            display: none;
        }
        @media print {
            * {
                margin: 0 !important;
                padding: 0 !important;
                border-collapse: collapse !important;
            }
            html {
                margin: 0 !important;
                padding: 0 !important;
            }
            body {
                display: block !important;
                padding: 0 !important;
                margin: 0 !important;
                background: white !important;
                height: auto !important;
                min-height: auto !important;
                justify-content: auto !important;
            }
            @page {
                margin: 0.3in 0.3in 0.3in 0.3in;
                size: letter;
                orphans: 0;
                widows: 0;
            }
            .document {
                max-width: 100% !important;
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
                page-break-after: avoid;
            }
            .bottom-buttons {
                display: none !important;
            }
            .spes-form-id {
                margin: 0 !important;
                padding: 1px 0 !important;
                font-size: 10px !important;
                line-height: 1.1 !important;
            }
            .header-section {
                margin: 0 !important;
                padding: 2px !important;
                line-height: 1.1 !important;
            }
            .header-section img {
                max-width: 50px !important;
                max-height: 50px !important;
            }
            .title {
                margin: 2px 0 !important;
                padding: 1px !important;
                font-size: 11px !important;
                line-height: 1.1 !important;
                font-weight: bold !important;
            }
            .control-no {
                margin: 0 !important;
                padding: 1px 0 !important;
                font-size: 8px !important;
                line-height: 1 !important;
            }
            table {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border-spacing: 0 !important;
                font-size: 8px !important;
                line-height: 1 !important;
                border: 1px solid #000 !important;
            }
            td, th {
                padding: 1px 2px !important;
                margin: 0 !important;
                border: 0.5px solid #000 !important;
                font-size: 7px !important;
                line-height: 1 !important;
                page-break-inside: avoid !important;
            }
            tr {
                page-break-inside: avoid !important;
            }
            input[type="text"],
            input[type="email"],
            input[type="date"],
            textarea {
                background-color: white !important;
                border: none !important;
                margin: 0 !important;
                padding: 0px !important;
                font-size: 7px !important;
                line-height: 1 !important;
                height: auto !important;
                width: 100% !important;
                color: #000 !important;
            }
            input[type="checkbox"],
            input[type="radio"] {
                margin: 0 2px !important;
                padding: 0 !important;
                opacity: 1 !important;
            }
            .legal-text {
                margin: 1px 0 !important;
                padding: 1px !important;
                font-size: 6px !important;
                line-height: 1 !important;
                page-break-inside: avoid !important;
            }
            .signature-section {
                margin: 1px 0 !important;
                padding: 1px !important;
                font-size: 7px !important;
                line-height: 1 !important;
                page-break-inside: avoid !important;
            }
            .req-section {
                margin: 1px 0 !important;
                padding: 1px !important;
                font-size: 7px !important;
                line-height: 1 !important;
            }
            .check-group {
                margin: 0 !important;
                padding: 0 !important;
                line-height: 1 !important;
            }
            label {
                margin: 0 !important;
                padding: 0 !important;
                font-size: 7px !important;
                line-height: 1 !important;
                display: inline !important;
            }
            .header-text {
                font-size: 8px !important;
                line-height: 1.1 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .photo-box {
                font-size: 6px !important;
                line-height: 1 !important;
            }
            .gray-bg {
                background: #e0e0e0 !important;
            }
            br {
                line-height: 0.5 !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body>

<div class="document">
    <div class="spes-form-id">SPES Application Form</div>
    <div class="header-section">
        <img src="{{ asset('images/left-logo1.png') }}" alt="Left Logo">
        <div class="header-text">
            REPUBLIC OF THE PHILIPPINES<br>
            <strong>DEPARTMENT OF LABOR AND EMPLOYMENT</strong><br>
            Regional Office No. _____________<br>
            <strong>PUBLIC EMPLOYMENT SERVICE OFFICE</strong><br><br>
            City/Municipality/Province<br>
            <strong>SPECIAL PROGRAM FOR EMPLOYMENT OF STUDENTS (SPES)</strong><br>
            (RA 7323, as amended by RAs 9547 and 10917)
        </div>
        <img src="{{ asset('images/peso_logo-1.png') }}" alt="PESO LOGO">
    </div>

    <div class="title">APPLICATION FORM</div>
    <div class="control-no">Control No.: {{ $application->ref_id }}</div>

    <table>
        <tr>
            <td colspan="2"><span class="label">SURNAME</span><input type="text" value="{{ $application->form_data['surname'] ?? '' }}" readonly></td>
            <td colspan="2"><span class="label">FIRST NAME</span><input type="text" value="{{ $application->form_data['first_name'] ?? '' }}" readonly></td>
            <td colspan="2"><span class="label">MIDDLE NAME</span><input type="text" value="{{ $application->form_data['middle_name'] ?? '' }}" readonly></td>
            <td colspan="2"><span class="label">GSIS BENEFICIARY/RELATIONSHIP</span><input type="text" value="{{ $application->form_data['gsis_beneficiary'] ?? '' }}" readonly></td>
            <td rowspan="4" style="padding: 0; width: 140px;">
                <div class="photo-box">Passport Size Picture<br>(3.5cm x 4.5 cm)</div>
            </td>
        </tr>
        <tr>
            <td colspan="2"><span class="label">DATE OF BIRTH: (mm/dd/yyyy)</span><input type="date" value="{{ $application->form_data['birth_date'] ?? '' }}" readonly></td>
            <td colspan="3"><span class="label">PLACE OF BIRTH:</span><input type="text" value="{{ $application->form_data['birth_place'] ?? '' }}" readonly></td>
            <td colspan="3"><span class="label">CITIZENSHIP:</span><input type="text" value="{{ $application->form_data['citizenship'] ?? '' }}" readonly></td>
        </tr>
        <tr>
            <td colspan="3"><span class="label">CONTACT DETAILS/CELPHONE NO.:</span><input type="text" value="{{ $application->form_data['phone'] ?? '' }}" readonly></td>
            <td colspan="5"><span class="label">EMAIL ADDRESS:</span><input type="email" value="{{ $application->form_data['email'] ?? '' }}" readonly></td>
        </tr>
        <tr>
            <td colspan="8"><span class="label">SOCIAL MEDIA ACCOUNT (FACEBOOK, TWITTER, INSTAGRAM, ETC.)</span><input type="text" value="{{ $application->form_data['social_media'] ?? '' }}" readonly></td>
        </tr>
        <tr>
            <td colspan="4">
                <span class="label">STATUS</span>
                <div class="check-group">
                    <label><input type="checkbox" value="Single" {{ ($application->form_data['status'] ?? null) === 'Single' ? 'checked' : '' }} disabled> Single</label>
                    <label><input type="checkbox" value="Married" {{ ($application->form_data['status'] ?? null) === 'Married' ? 'checked' : '' }} disabled> Married</label>
                    <label><input type="checkbox" value="Widow/er" {{ ($application->form_data['status'] ?? null) === 'Widow/er' ? 'checked' : '' }} disabled> Widow/er</label>
                    <label><input type="checkbox" value="Separated" {{ ($application->form_data['status'] ?? null) === 'Separated' ? 'checked' : '' }} disabled> Separated</label>
                </div>
            </td>
            <td colspan="2">
                <span class="label">SEX</span>
                <div class="check-group">
                    <label><input type="checkbox" value="Male" {{ ($application->form_data['sex'] ?? null) === 'Male' ? 'checked' : '' }} disabled> Male</label>
                    <label><input type="checkbox" value="Female" {{ ($application->form_data['sex'] ?? null) === 'Female' ? 'checked' : '' }} disabled> Female</label>
                </div>
            </td>
            <td colspan="3">
                <div class="check-group">
                    <label><input type="checkbox" value="Student" {{ ($application->form_data['category'] ?? null) === 'Student' ? 'checked' : '' }} disabled> Student</label>
                    <label><input type="checkbox" value="ALS student" {{ ($application->form_data['category'] ?? null) === 'ALS student' ? 'checked' : '' }} disabled> ALS student</label>
                    <label><input type="checkbox" value="out-of-school (OSY)" {{ ($application->form_data['category'] ?? null) === 'out-of-school (OSY)' ? 'checked' : '' }} disabled> out-of-school (OSY)</label>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="9">
                <span class="label">CURRENT STATUS OF PARENTS:</span>
                <div class="check-group">
                    <label><input type="checkbox" value="Living together" {{ ($application->form_data['parent_status'] ?? null) === 'Living together' ? 'checked' : '' }} disabled> Living together</label>
                    <label><input type="checkbox" value="Solo Parent" {{ ($application->form_data['parent_status'] ?? null) === 'Solo Parent' ? 'checked' : '' }} disabled> Solo Parent</label>
                    <label><input type="checkbox" value="Separated" {{ ($application->form_data['parent_status'] ?? null) === 'Separated' ? 'checked' : '' }} disabled> Separated</label>
                    <label><input type="checkbox" value="Person With Disability" {{ ($application->form_data['parent_status'] ?? null) === 'Person With Disability' ? 'checked' : '' }} disabled> Person With Disability</label>
                    <label><input type="checkbox" value="Senior Citizen" {{ ($application->form_data['parent_status'] ?? null) === 'Senior Citizen' ? 'checked' : '' }} disabled> Senior Citizen</label>
                    <label><input type="checkbox" value="Sugar Plantation Worker" {{ ($application->form_data['parent_status'] ?? null) === 'Sugar Plantation Worker' ? 'checked' : '' }} disabled> Sugar Plantation Worker</label>
                    <label><input type="checkbox" value="Indigenous People" {{ ($application->form_data['parent_status'] ?? null) === 'Indigenous People' ? 'checked' : '' }} disabled> Indigenous People</label>
                    <label><input type="checkbox" value="Displaced Worker" {{ ($application->form_data['parent_status'] ?? null) === 'Displaced Worker' ? 'checked' : '' }} disabled> Displaced Worker</label>
                    <label>(1)<input type="checkbox" value="Local" {{ ($application->form_data['parent_status'] ?? null) === 'Local' ? 'checked' : '' }} disabled> Local</label>
                    <label>(2)<input type="checkbox" value="OFW" {{ ($application->form_data['parent_status'] ?? null) === 'OFW' ? 'checked' : '' }} disabled> OFW</label>
                </div>
            </td>
        </tr>
        <tr><td colspan="9"><span class="label">PRESENT ADDRESS:</span><input type="text" value="{{ $application->form_data['present_address'] ?? '' }}" readonly></td></tr>
        <tr><td colspan="9"><span class="label">PERMANENT ADDRESS:</span><input type="text" value="{{ $application->form_data['permanent_address'] ?? '' }}" readonly></td></tr>
        <tr>
            <td colspan="5"><span class="label">FATHER'S NAME / CONTACT NO.:</span><input type="text" value="{{ $application->form_data['father_info'] ?? '' }}" readonly></td>
            <td colspan="4"><span class="label">MOTHER'S MAIDEN NAME / CONTACT NO.:</span><input type="text" value="{{ $application->form_data['mother_info'] ?? '' }}" readonly></td>
        </tr>
        <tr>
            <td colspan="5"><span class="label">OCCUPATION:</span><input type="text" value="{{ $application->form_data['father_occupation'] ?? '' }}" readonly></td>
            <td colspan="4"><span class="label">OCCUPATION:</span><input type="text" value="{{ $application->form_data['mother_occupation'] ?? '' }}" readonly></td>
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
            <td colspan="3"><input type="text" value="{{ $application->form_data['elementary_school'] ?? '' }}" readonly></td>
            <td colspan="2"><input type="text" value="{{ $application->form_data['elementary_degree'] ?? '' }}" readonly></td>
            <td><input type="text" value="{{ $application->form_data['elementary_year'] ?? '' }}" readonly></td>
            <td colspan="2"><input type="date" value="{{ $application->form_data['elementary_date'] ?? '' }}" readonly></td>
        </tr>
        <tr>
            <td>Secondary</td>
            <td colspan="3"><input type="text" value="{{ $application->form_data['secondary_school'] ?? '' }}" readonly></td>
            <td colspan="2"><input type="text" value="{{ $application->form_data['secondary_degree'] ?? '' }}" readonly></td>
            <td><input type="text" value="{{ $application->form_data['secondary_year'] ?? '' }}" readonly></td>
            <td colspan="2"><input type="date" value="{{ $application->form_data['secondary_date'] ?? '' }}" readonly></td>
        </tr>
        <tr>
            <td>Tertiary</td>
            <td colspan="3"><input type="text" value="{{ $application->form_data['tertiary_school'] ?? '' }}" readonly></td>
            <td colspan="2"><input type="text" value="{{ $application->form_data['tertiary_degree'] ?? '' }}" readonly></td>
            <td><input type="text" value="{{ $application->form_data['tertiary_year'] ?? '' }}" readonly></td>
            <td colspan="2"><input type="date" value="{{ $application->form_data['tertiary_date'] ?? '' }}" readonly></td>
        </tr>
        <tr>
            <td>Tech-Voc</td>
            <td colspan="3"><input type="text" value="{{ $application->form_data['techvoc_school'] ?? '' }}" readonly></td>
            <td colspan="2"><input type="text" value="{{ $application->form_data['techvoc_degree'] ?? '' }}" readonly></td>
            <td><input type="text" value="{{ $application->form_data['techvoc_year'] ?? '' }}" readonly></td>
            <td colspan="2"><input type="date" value="{{ $application->form_data['techvoc_date'] ?? '' }}" readonly></td>
        </tr>
    </table>

    <div class="req-section">
        <strong>DOCUMENTARY REQUIREMENTS:</strong><br>
        (Original and other documents, when applicable, should be presented for validation)<br>
        <label><input type="checkbox" value="birth_cert" {{ (isset($application->form_data['doc_requirements']) && in_array('birth_cert', (array)$application->form_data['doc_requirements'])) ? 'checked' : '' }} disabled> [ ] 1) Photocopy of Birth Certificate or any document indicating date of birth or age (age must be 15-30)</label><br>
        <label><input type="checkbox" value="income_tax" {{ (isset($application->form_data['doc_requirements']) && in_array('income_tax', (array)$application->form_data['doc_requirements'])) ? 'checked' : '' }} disabled> [ ] 2) Photocopy of the latest Income Tax Return (ITR) of parents/legal guardian <strong>OR</strong> certification issued by BIR that the parents/guardians are exempted from payment of tax <strong>OR</strong> original Certificate of Indigence <strong>OR</strong> original Certificate of Low Income issued by the Barangay/DSWD or CSWD where the applicant resides; and</label><br>
        <label><input type="checkbox" value="student_docs" {{ (isset($application->form_data['doc_requirements']) && in_array('student_docs', (array)$application->form_data['doc_requirements'])) ? 'checked' : '' }} disabled> [ ] 3) <strong>For students</strong>, any of the following, in addition to requirements no. 1 and 2:</label><br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label><input type="checkbox" value="grade_avg" {{ (isset($application->form_data['doc_requirements']) && in_array('grade_avg', (array)$application->form_data['doc_requirements'])) ? 'checked' : '' }} disabled> [ ] a) Photocopy of proof of average passing grade such as (1) class card or (2) Form 138 of the previous semester or year immediately preceding the application; <strong>OR</strong></label><br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label><input type="checkbox" value="cert_grade" {{ (isset($application->form_data['doc_requirements']) && in_array('cert_grade', (array)$application->form_data['doc_requirements'])) ? 'checked' : '' }} disabled> [ ] b) Original copy of Certification by the School Registrar as to passing grade immediately preceding semester/year if grades are not yet available</label><br>
        <label><input type="checkbox" value="osy_cert" {{ (isset($application->form_data['doc_requirements']) && in_array('osy_cert', (array)$application->form_data['doc_requirements'])) ? 'checked' : '' }} disabled> [ ] 4) <strong>For Out of School Youth (OSY)</strong>, original copy of Certification as OSY issued by DSWD/CSWD or the authorized Barangay Official where the OSY resides, in addition to requirements no. 1 and 2.</label>
    </div>

    <table style="margin-top: -1px;">
        <tr class="gray-bg">
            <td colspan="4"><span class="label">SPECIAL SKILLS:</span><input type="text" name="special_skills" style="margin-top: 5px;" value="{{ $application->form_data['special_skills'] ?? '' }}" readonly></td>
        </tr>
        <tr class="gray-bg" style="text-align: center; font-size: 9px;">
            <td colspan="2">HISTORY of SPES Availment/ Name of Establishment</td>
            <td>YEAR</td>
            <td>SPES ID NO. (if applicable)</td>
        </tr>
        <tr>
            <td colspan="2"><label><input type="checkbox" value="1st" {{ ($application->form_data['spes_availment'] ?? null) === '1st' ? 'checked' : '' }} disabled> [ ] 1st Availment-</label><input type="text" value="{{ $application->form_data['spes_1st'] ?? '' }}" readonly style="width: 100%;"></td>
            <td><input type="text" value="{{ $application->form_data['spes_1st_year'] ?? '' }}" readonly></td>
            <td><input type="text" value="{{ $application->form_data['spes_1st_id'] ?? '' }}" readonly></td>
        </tr>
        <tr>
            <td colspan="2"><label><input type="checkbox" value="2nd" {{ ($application->form_data['spes_availment'] ?? null) === '2nd' ? 'checked' : '' }} disabled> [ ] 2nd Availment-</label><input type="text" value="{{ $application->form_data['spes_2nd'] ?? '' }}" readonly style="width: 100%;"></td>
            <td><input type="text" value="{{ $application->form_data['spes_2nd_year'] ?? '' }}" readonly></td>
            <td><input type="text" value="{{ $application->form_data['spes_2nd_id'] ?? '' }}" readonly></td>
        </tr>
        <tr>
            <td colspan="2"><label><input type="checkbox" value="3rd" {{ ($application->form_data['spes_availment'] ?? null) === '3rd' ? 'checked' : '' }} disabled> [ ] 3rd Availment-</label><input type="text" value="{{ $application->form_data['spes_3rd'] ?? '' }}" readonly style="width: 100%;"></td>
            <td><input type="text" value="{{ $application->form_data['spes_3rd_year'] ?? '' }}" readonly></td>
            <td><input type="text" value="{{ $application->form_data['spes_3rd_id'] ?? '' }}" readonly></td>
        </tr>
        <tr>
            <td colspan="2"><label><input type="checkbox" value="4th" {{ ($application->form_data['spes_availment'] ?? null) === '4th' ? 'checked' : '' }} disabled> [ ] 4th Availment-</label><input type="text" value="{{ $application->form_data['spes_4th'] ?? '' }}" readonly style="width: 100%;"></td>
            <td><input type="text" value="{{ $application->form_data['spes_4th_year'] ?? '' }}" readonly></td>
            <td><input type="text" value="{{ $application->form_data['spes_4th_id'] ?? '' }}" readonly></td>
        </tr>
        <tr>
            <td colspan="4"><span class="label">Other related information/ requests/ interventions from DOLE:</span><textarea readonly style="width: 100%; height: 60px; border: none; background: transparent; font-family: Arial Narrow, Arial, sans-serif; font-size: 10px;">{{ $application->form_data['other_info'] ?? '' }}</textarea></td>
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

    <div class="form-buttons">
        <button type="button" class="btn btn-submit" disabled><i class="fas fa-paper-plane"></i> Submit Application</button>
        <button type="button" class="btn btn-reset" disabled>Clear Form</button>
    </div>
</div>

<div class="bottom-buttons">
    <button class="back-btn" onclick="window.history.back()"><i class="fas fa-arrow-left"></i> Back</button>
    <button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
</div>

</body>
</html>
