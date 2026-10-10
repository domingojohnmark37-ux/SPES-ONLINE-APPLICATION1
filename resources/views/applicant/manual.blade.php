<!DOCTYPE html>
<html lang="{{ request()->attributes->get('applicant_language', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('User Guide') }} — {{ __('SPES Portal') }}</title>
    <x-applicant-text-styles />
    <style>
        :root {
            --primary:#8B0000;
            --primary-dark:#660000;
            --bg:#f0f2f5;
            --white:#fff;
            --text:#212121;
            --text-muted:#6b7280;
            --border:#e0e0e0;
            --shadow:0 2px 12px rgba(0,0,0,.08);
            --sidebar-w:260px;
        }
        html[data-theme="dark"] {
            color-scheme:dark;
            --primary:#ffaaaa;
            --primary-dark:#4a1212;
            --bg:#17191d;
            --white:#24272d;
            --text:#f2f4f7;
            --text-muted:#c0c6d0;
            --border:#434852;
            --shadow:0 2px 12px rgba(0,0,0,.3);
        }
        @media(prefers-color-scheme:dark) {
            html[data-theme="system"] {
                color-scheme:dark;
                --primary:#ffaaaa;
                --primary-dark:#4a1212;
                --bg:#17191d;
                --white:#24272d;
                --text:#f2f4f7;
                --text-muted:#c0c6d0;
                --border:#434852;
                --shadow:0 2px 12px rgba(0,0,0,.3);
            }
        }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--bg); color:var(--text); font-family:Inter,"Plus Jakarta Sans",system-ui,sans-serif; }
        .topbar { position:fixed; z-index:90; top:0; right:0; left:var(--sidebar-w); display:flex; align-items:center; min-height:62px; padding:10px 28px; border-bottom:1px solid var(--border); background:var(--white); }
        .topbar h1 { margin:0; color:var(--type-primary-color); font-size:var(--type-title); font-weight:700; }
        .topbar p { margin:3px 0 0; color:var(--type-secondary-color); font-size:var(--type-caption); }
        .hamburger { display:none; margin-right:12px; border:0; background:transparent; color:var(--primary); font-size:1.1rem; cursor:pointer; }
        .page-wrapper { min-height:100vh; margin-left:var(--sidebar-w); padding:84px 28px 30px; }
        .page-content { max-width:1120px; margin:0 auto; }
        .guide-intro,.guide-card { border:1px solid var(--border); border-radius:12px; background:var(--white); box-shadow:var(--shadow); }
        .guide-intro { margin-bottom:16px; padding:22px; }
        .guide-intro h2 { margin:0 0 8px; color:var(--type-primary-color); font-size:var(--type-section); }
        .guide-intro p { margin:0; color:var(--type-secondary-color); font-size:var(--type-secondary); line-height:1.65; }
        .guide-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
        .guide-card { padding:18px; }
        .guide-card-wide { grid-column:1 / -1; }
        .guide-card h2 { display:flex; align-items:center; gap:10px; margin:0 0 9px; color:var(--type-primary-color); font-size:var(--type-section); }
        .guide-card h2 svg.icon { width:22px; color:var(--primary); text-align:center; }
        .guide-card p,.guide-card li { color:var(--type-secondary-color); font-size:var(--type-secondary); line-height:1.6; }
        .guide-card p { margin:0; }
        .guide-card ul,.guide-card ol { display:grid; gap:6px; margin:9px 0 0; padding-left:20px; }
        .guide-links { display:flex; flex-wrap:wrap; gap:8px; margin-top:13px; }
        .guide-link { display:inline-flex; align-items:center; gap:7px; min-height:36px; padding:7px 10px; border:1px solid var(--border); border-radius:7px; color:var(--primary); font-size:var(--type-caption); font-weight:650; text-decoration:none; }
        .guide-link:hover,.guide-link:focus-visible { border-color:var(--primary); background:color-mix(in srgb,var(--primary) 8%,var(--white)); }
        .guide-note { margin-top:16px; padding:14px 16px; border-left:4px solid var(--primary); border-radius:7px; background:var(--white); color:var(--type-secondary-color); font-size:var(--type-caption); line-height:1.6; }
        @media(max-width:768px) {
            .topbar { left:0; padding:10px 14px; }
            .hamburger { display:block; }
            .page-wrapper { margin-left:0; padding:82px 14px 22px; }
            .guide-grid { grid-template-columns:1fr; }
        }
        @media(max-width:600px) {
            .topbar { min-height:56px; }
            .topbar h1 { font-size:var(--type-section); }
            .guide-intro,.guide-card { padding:15px; }
        }
    </style>
</head>
<body>
    <x-applicant-sidebar />
    <header class="topbar">
        <button class="hamburger" type="button" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false" aria-label="{{ __('Toggle applicant navigation') }}">
            <x-icon class="fa-solid fa-bars" aria-hidden="true" />
        </button>
        <div>
            <h1>{{ __('SPES Portal User Guide') }}</h1>
            <p>{{ __('Learn what each applicant portal feature does and how to use it.') }}</p>
        </div>
    </header>
    <main class="page-wrapper">
        <div class="page-content">
            <section class="guide-intro">
                <h2>{{ __('Welcome to the SPES Applicant Portal') }}</h2>
                <p>{{ __('Use this guide to understand the portal, follow your application, and find the right place for common tasks. Your available actions may depend on your application status and the current application period.') }}</p>
            </section>

            <div class="guide-grid">
                <section class="guide-card">
                    <h2><x-icon class="fa-solid fa-house" aria-hidden="true" />{{ __('Dashboard') }}</h2>
                    <p>{{ __('Your dashboard is the portal home. It gives you a quick view of your application status, profile completion, important announcements, upcoming appointments, and recent notifications.') }}</p>
                    <div class="guide-links"><a class="guide-link" href="{{ route('dashboard') }}">{{ __('Open Dashboard') }} <x-icon class="fa-solid fa-arrow-right" aria-hidden="true" /></a></div>
                </section>

                <section class="guide-card">
                    <h2><x-icon class="fa-solid fa-file-lines" aria-hidden="true" />{{ __('My Application') }}</h2>
                    <p>{{ __('Use Application Status to review the latest status and any feedback about your submission. If you have not applied, use Apply Now when applications are open. If your application was denied, the portal may offer Reapply so you can correct details and submit again.') }}</p>
                    <ul>
                        <li>{{ __('Check that the information you provide matches your official documents.') }}</li>
                        <li>{{ __('Review your application after submission and read any administrator feedback.') }}</li>
                        <li>{{ __('An approved application may still require final confirmation or additional steps; follow the instructions shown in the portal.') }}</li>
                    </ul>
                    <div class="guide-links">
                        <a class="guide-link" href="{{ route('applications.myApplication') }}">{{ __('Application Status') }}</a>
                        @if(!$application || $application->status === 'denied')
                            <a class="guide-link" href="{{ $application ? route('applications.edit') : route('applications.create') }}">{{ __($application ? 'Reapply' : 'Apply Now') }}</a>
                        @endif
                    </div>
                </section>

                <section class="guide-card guide-card-wide">
                    <h2><x-icon class="fa-solid fa-list-check" aria-hidden="true" />{{ __('How to Apply') }}</h2>
                    <ol>
                        <li>
                            <strong>{{ __('Complete the application form') }}</strong>
                            <p>{{ __('Open Apply Now and complete the personal, education, family, and contact information in the application form.') }}</p>
                        </li>
                        <li>
                            <strong>{{ __('Add the required documents') }}</strong>
                            <p>{{ __('Upload your Birth Certificate and Certificate of Enrollment as PDF files. Each file must be 5 MB or smaller.') }}</p>
                        </li>
                        <li>
                            <strong>{{ __('Submit for PESO review') }}</strong>
                            <p>{{ __('Review your information and submit the form. Your application status will be Pending while PESO reviews it.') }}</p>
                        </li>
                        <li>
                            <strong>{{ __('Track your application') }}</strong>
                            <p>{{ __('Check My Application and Notifications for status updates or PESO feedback. If your application is denied, use Update Application to make corrections and reapply.') }}</p>
                        </li>
                        <li>
                            <strong>{{ __('Complete the next steps if approved') }}</strong>
                            <p>{{ __('If approved as a proposed applicant, open Additional Requirements, download requested forms, complete and sign them by hand, then upload the requested files. Bring printed forms to the PESO office and follow the listed deadlines and instructions.') }}</p>
                        </li>
                    </ol>
                    <div class="guide-links">
                        @if(!$application || $application->status === 'denied')
                            <a class="guide-link" href="{{ $application ? route('applications.edit') : route('applications.create') }}">{{ __($application ? 'Update Application' : 'Apply Now') }}</a>
                        @else
                            <a class="guide-link" href="{{ route('applications.myApplication') }}">{{ __('My Application') }}</a>
                        @endif
                        <a class="guide-link" href="{{ route('applicant.requirements') }}">{{ __('Additional Requirements') }}</a>
                    </div>
                </section>

                <section class="guide-card">
                    <h2><x-icon class="fa-solid fa-folder-open" aria-hidden="true" />{{ __('Additional Requirements') }}</h2>
                    <p>{{ __('This page lists any extra documents requested for your application. Read each instruction, download a provided template if available, and upload the requested file. You can review your submitted files and replace them when the portal allows it.') }}</p>
                    <ul>
                        <li>{{ __('Check the accepted file types and size limit shown beside each requirement.') }}</li>
                        <li>{{ __('Make sure scans or photos are complete and readable before uploading.') }}</li>
                        <li>{{ __('A requirement marked optional is not required unless its instructions say otherwise.') }}</li>
                    </ul>
                    <div class="guide-links"><a class="guide-link" href="{{ route('applicant.requirements') }}">{{ __('Open Requirements') }}</a></div>
                </section>

                <section class="guide-card">
                    <h2><x-icon class="fa-solid fa-bell" aria-hidden="true" />{{ __('Notifications and Appointments') }}</h2>
                    <p>{{ __('The Notifications menu includes appointments, recent updates, and previous notifications. Recent Notifications highlights new or recent announcements and application updates. Previous Notifications lets you search and review older items. Open Appointments to see published schedules and their details.') }}</p>
                    <ul>
                        <li>{{ __('Read appointment dates, times, and locations carefully, and follow any instructions provided.') }}</li>
                        <li>{{ __('Use notification links to return to the related application or update when available.') }}</li>
                        <li>{{ __('Announcements may include information that applies to all applicants.') }}</li>
                    </ul>
                    <div class="guide-links">
                        <a class="guide-link" href="{{ route('applicant.notifications.recent') }}">{{ __('Recent Notifications') }}</a>
                        <a class="guide-link" href="{{ route('applicant.notifications.previous') }}">{{ __('Previous Notifications') }}</a>
                        <a class="guide-link" href="{{ route('applicant.appointments.index') }}">{{ __('Appointments') }}</a>
                    </div>
                </section>

                <section class="guide-card">
                    <h2><x-icon class="fa-solid fa-circle-question" aria-hidden="true" />{{ __('Help and Contact') }}</h2>
                    <p>{{ __('Open FAQs in the Need Help & Support menu for answers to common questions. Use Contact Us to send a support request to PESO if you need help that is not covered by the FAQs.') }}</p>
                    <div class="guide-links">
                        <button class="guide-link" type="button" data-open-portal-help>{{ __('Open FAQs') }}</button>
                        <a class="guide-link" href="{{ route('contact-peso.index') }}">{{ __('Contact PESO') }}</a>
                    </div>
                </section>

                <section class="guide-card">
                    <h2><x-icon class="fa-solid fa-user-gear" aria-hidden="true" />{{ __('Profile and Settings') }}</h2>
                    <p>{{ __('Keep your profile information and contact details up to date so the program can reach you. Settings lets you manage account security, notification preferences, appearance, language, privacy information, and accessibility options.') }}</p>
                    <div class="guide-links">
                        <a class="guide-link" href="{{ route('profile.edit') }}">{{ __('Open Profile') }}</a>
                        <a class="guide-link" href="{{ route('settings.index') }}">{{ __('Open Settings') }}</a>
                    </div>
                </section>

                <section class="guide-card">
                    <h2><x-icon class="fa-solid fa-shield-halved" aria-hidden="true" />{{ __('Keep Your Account Safe') }}</h2>
                    <p>{{ __('Use a strong password, do not share your sign-in details, and sign out when you finish using a shared device. Only upload genuine documents that belong to your application. Contact PESO if you notice an account or application issue.') }}</p>
                </section>
            </div>
            <p class="guide-note">{{ __('Portal actions and available features can change based on the application period, your application status, and requirements set by the administrators. Follow the latest instructions displayed in your account.') }}</p>
        </div>
    </main>
    <x-portal-help-chat />
</body>
</html>
