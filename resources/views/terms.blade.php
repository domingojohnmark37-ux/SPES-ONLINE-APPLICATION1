<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms and Conditions | SPES Online Application</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="terms-page">
    <main class="terms-document">
        <a class="terms-back-link" href="{{ route('register') }}">← Back to registration</a>

        <header class="terms-document-header">
            <p class="terms-eyebrow">SPES Online Application</p>
            <h1>TERMS AND CONDITIONS</h1>
            <p class="terms-document-subtitle">Online Application for the Special Program for Employment of Students (SPES)</p>
            <p class="terms-introduction">Please read these Terms and Conditions carefully before submitting your application. By using this online application system, you confirm that you understand and agree to the following terms.</p>
        </header>

        <article class="terms-document-content">
            <section>
                <h2>1. Purpose of the Online Application</h2>
                <p>This online system is provided to make the SPES application process easier and more convenient for students. It allows applicants to submit their information, upload required documents, and monitor the status of their application online.</p>
            </section>

            <section>
                <h2>2. Accurate Information</h2>
                <p>Applicants are responsible for providing complete, accurate, and updated information. All information submitted must be truthful and must belong to the applicant.</p>
                <p>Providing false, incomplete, or misleading information may result in the application being rejected or cancelled.</p>
            </section>

            <section>
                <h2>3. Required Documents</h2>
                <p>Applicants must submit the required documents within the given application period. Uploaded documents must be clear, readable, valid, and authentic.</p>
                <p>The office may request additional documents or ask the applicant to submit a clearer or corrected copy when necessary.</p>
            </section>

            <section>
                <h2>4. Application Review</h2>
                <p>Submitting an application does not guarantee acceptance into the SPES program.</p>
                <p>All applications will be reviewed and evaluated based on the applicable SPES requirements, qualifications, available slots, and other program guidelines.</p>
                <p>The decision of the authorized personnel regarding the application will be communicated through the online system or other official channels.</p>
            </section>

            <section>
                <h2>5. Account Security</h2>
                <p>Applicants are responsible for keeping their account credentials, including their password, secure and confidential.</p>
                <p>Applicants should not share their account with another person. Any activity made using the applicant's account may be considered the responsibility of the account holder.</p>
            </section>

            <section>
                <h2>6. Application Deadline</h2>
                <p>Applicants must complete and submit their applications before the announced deadline. Applications submitted after the deadline may not be accepted unless an extension is officially announced.</p>
            </section>

            <section>
                <h2>7. Use of Personal Information</h2>
                <p>The information submitted through this system will be used for the processing, verification, evaluation, and administration of the SPES application.</p>
                <p>Personal information will be handled in accordance with applicable data privacy laws and institutional policies. Information may be accessed only by authorized personnel for legitimate program-related purposes.</p>
            </section>

            <section>
                <h2>8. System Use</h2>
                <p>Applicants agree to use the online application system properly and only for legitimate SPES application purposes.</p>
                <p>Any attempt to provide false information, access another person's account, modify system data without authorization, or interfere with the operation of the system may result in appropriate action.</p>
            </section>

            <section>
                <h2>9. System Availability</h2>
                <p>The office will make reasonable efforts to keep the online application system available. However, temporary interruptions may occur because of maintenance, technical problems, internet connectivity, or other circumstances beyond the office's control.</p>
                <p>Applicants are encouraged to submit their applications and documents ahead of the deadline to avoid problems caused by unexpected technical issues.</p>
            </section>

            <section>
                <h2>10. Changes to the Terms</h2>
                <p>The office may update these Terms and Conditions when necessary to reflect changes in the SPES application process, requirements, or applicable policies.</p>
                <p>Any important changes will be communicated through the official application system or other official communication channels.</p>
            </section>

            <section>
                <h2>11. Applicant Confirmation</h2>
                <p>By checking the agreement box and submitting your application, you confirm that:</p>
                <ul>
                    <li>You have read and understood these Terms and Conditions.</li>
                    <li>The information you provided is true and accurate.</li>
                    <li>The documents you submitted are authentic and belong to you.</li>
                    <li>You understand that submitting an application does not guarantee acceptance into the SPES program.</li>
                    <li>You agree to follow the rules and requirements of the SPES program and the application process.</li>
                </ul>
                <p><strong>By continuing with your application, you acknowledge that you have read, understood, and agreed to these Terms and Conditions.</strong></p>
            </section>
        </article>

        <footer class="terms-document-footer">
            <a class="terms-return-button" href="{{ route('register') }}">Return to registration</a>
        </footer>
    </main>
</body>
</html>
