<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official SPES Portal | PESO LAL-LO</title>

    {{-- Font Awesome --}}

    {{-- Your custom stylesheet (public/css/auth.css) --}}
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>

    {{-- ===== NAVBAR ===== --}}
    <nav class="navbar">
        <div class="logo">
            <img src="{{ asset('images/welcome_logo.jpg') }}" alt="LGU Logo">
         SPES <span>Lal-lo</span>
        </div>
        
        {{-- Desktop Navigation --}}
        <div class="nav-links desktop-nav">
            <a href="#about">About</a>
            <a href="#qualifications">Eligibility</a>
            <a href="#process">Process</a>
        </div>

        {{-- Mobile Navigation (Hamburger Menu) --}}
        <button class="hamburger-menu" id="hamburger-btn">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="mobile-menu" id="mobile-menu">
            <a href="#about">About</a>
            <a href="#qualifications">Eligibility</a>
            <a href="#process">Process</a>
        </div>
          <div class="nav-auth">
             <a href="{{ route('login') }}" class="btn-login">Login</a>
        {{-- Auth Buttons (Always visible) --}}
        <a href="{{ route('register') }}" class="btn-nav">Sign Up</a>
          </div>
    </nav>

    {{-- ===== HERO SECTION ===== --}}
    <section class="hero" >
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <span class="badge">Department of Labor and Employment</span>
            <h1>Special Program for Employment of Students (SPES)</h1>
            <p class="hero-desc">
                Bridging the gap between education and employment. A government initiative mandating
                the employment of poor but deserving students, out-of-school youth, and dependents
                of displaced workers.
            </p>
            <div class="cta-group">
                <a href="#about" class="btn-outline">Learn More</a>
                <a href="{{ auth()->check() ? route('applications.create') : route('register') }}" class="btn-filled">Apply Now</a>
            </div>
        </div>
    </section>

    {{-- ===== ABOUT SECTION ===== --}}
    <section id="about" class="section light-bg">
        <div class="container">
            <div class="row">
                <div class="col-text slide-in-left">
                    <h4 class="sub-title">Program History &amp; Mandate</h4>
                    <h2>Empowering Youth Since 1992</h2>
                    <p>
                        The SPES is an employment-bridging program enacted under
                        <strong>Republic Act No. 7323</strong> on March 30, 1992. Its core mission is to
                        assist financially challenged students in pursuing their education by providing
                        short-term employment during summer and/or Christmas vacations.
                    </p>
                    <p>
                        Over the decades, the program has evolved to serve more beneficiaries through
                        <strong>Republic Act No. 9547</strong> (2009) and further strengthened by
                        <strong>Republic Act No. 10917</strong> (2016), which expanded the age limit to
                        30 years old and extended the employment duration.
                    </p>

                    <div class="info-box">
                        <div>
                            <strong>The 60/40 Salary Scheme</strong>
                            <p>
                                Beneficiaries receive a salary no less than the minimum wage. 60% is paid
                                by the employer (LGU/Private), and the remaining 40% is subsidized by DOLE
                                in the form of education vouchers or cash.
                            </p>
                        </div>
                    </div>

                    <a
                        class="dole-spes-link"
                        href="https://dole.gov.ph/special-program-for-the-employment-of-students-spes/"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Read more about SPES on the Department of Labor and Employment website (opens in a new tab)"
                    >
                        <span class="dole-spes-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                                <path d="M12 7v14" />
                                <path d="M3 6.5A2.5 2.5 0 0 1 5.5 4H12v17H6a3 3 0 0 0-3 1.5z" />
                                <path d="M21 6.5A2.5 2.5 0 0 0 18.5 4H12v17h6a3 3 0 0 1 3 1.5z" />
                            </svg>
                        </span>
                        <span class="dole-spes-copy">
                            <strong>Want to learn more about SPES?</strong>
                            <small>Explore the program details from the Department of Labor and Employment.</small>
                        </span>
                        <span class="dole-spes-action">
                            Visit DOLE
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                <path d="M14 4h6v6" />
                                <path d="m20 4-9 9" />
                                <path d="M18 13v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h5" />
                            </svg>
                        </span>
                    </a>
                </div>

                <div class="col-image slide-in-right">
                    <div class="image-stack">
                        <div class="img-top">
                            
                            <span>Education First</span>
                        </div>
                        <div class="img-bottom">
                    
                            <span>Gain Experience</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== QUALIFICATIONS SECTION ===== --}}
    <section id="qualifications" class="section dark-bg">
        <div class="container">
            <div class="section-header fade-in">
                <h2>Who is Eligible to Apply?</h2>
                <p>Ensure you meet the following criteria set by the DOLE guidelines before submitting your application.</p>
            </div>

            <div class="grid-3">
                <div class="card fade-in">
                    
                    <h3>Age Requirement</h3>
                    <p>Applicants must be at least <strong>15 years of age</strong> but not more than <strong>30 years old</strong> at the time of application.</p>
                </div>
                <div class="card fade-in">
                    
                    <h3>Educational Status</h3>
                    <p>Must be currently enrolled, or an Out-of-School Youth (OSY) who intends to enroll in the upcoming school year. Must have a passing general weighted average.</p>
                </div>
                <div class="card fade-in">
                   
                    <h3>Financial Status</h3>
                    <p>The combined net income after tax of the applicant's parents, including their own (if any), must not exceed the annual regional poverty threshold.</p>
                </div>
            </div>

        </div>
    </section>

    {{-- ===== PROCESS / TIMELINE SECTION ===== --}}
    <section id="process" class="section light-bg">
        <div class="container">
            <div class="section-header fade-in">
                <h2>Application Procedure</h2>
                <p>Follow these steps to secure your slot in the SPES program.</p>
            </div>

            <div class="timeline">
                <div class="timeline-item slide-in-left">
                    <div class="step-number">01</div>
                    <div class="step-content">
                        <h4>Create an Account</h4>
                        <p>Register on this portal using your email address. Ensure all personal details match your documents.</p>
                    </div>
                </div>
                <div class="timeline-item slide-in-right">
                    <div class="step-number">02</div>
                    <div class="step-content">
                        <h4>Submit Application Form</h4>
                        <p>Complete the online SPES application and upload clear copies of your requirements (Birth Certificate, Certificate of Enrollment, and other required documents).</p>
                    </div>
                </div>
                <div class="timeline-item slide-in-left">
                    <div class="step-number">03</div>
                    <div class="step-content">
                        <h4>Wait for Evaluation</h4>
                        <p>The PESO Officer will review your submission. You can track your status (Pending/Approved) via the Notification Bar on your dashboard.</p>
                    </div>
                </div>
                <div class="timeline-item slide-in-right">
                    <div class="step-number">04</div>
                    <div class="step-content">
                        <h4>Orientation &amp; Deployment</h4>
                        <p>Once approved, you will be notified of the orientation schedule. Successful applicants will be assigned to their respective Local Government Units or partner agencies.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <button
        class="announcement-launcher"
        id="announcement-launcher"
        type="button"
        aria-haspopup="dialog"
        aria-controls="announcement-popup"
        aria-expanded="false"
        aria-label="Open latest SPES updates and announcements"
    >
        <x-icon class="fa-solid fa-bullhorn" aria-hidden="true" />
        <span>Latest Updates</span>
        @if($news->isNotEmpty())
            <span class="announcement-count" aria-label="{{ $news->count() }} announcements">{{ $news->count() }}</span>
        @endif
    </button>

    <dialog
        class="announcement-popup"
        id="announcement-popup"
        aria-labelledby="announcement-popup-title"
        data-auto-open="true"
    >
        <div class="announcement-popup-header">
            <div>
                <span class="announcement-popup-kicker"><x-icon class="fa-solid fa-bullhorn" aria-hidden="true" /> SPES LAL-LO</span>
                <h2 id="announcement-popup-title">Latest Updates &amp; Announcements</h2>
                <p>Stay informed with the latest news from the SPES program.</p>
            </div>
            <button class="announcement-popup-close" type="button" aria-label="Close announcements" data-announcement-close>
                <x-icon class="fa-solid fa-xmark" aria-hidden="true" />
            </button>
        </div>
        <div class="announcement-popup-list">
            @forelse($news as $item)
                <article class="announcement-popup-item">
                    <h3>{{ $item->title }}</h3>
                    <p>{{ strip_tags($item->content) }}</p>
                    <time datetime="{{ $item->published_at->toDateString() }}">
                        <x-icon class="fa-solid fa-calendar-days" aria-hidden="true" />
                        {{ $item->published_at->format('F d, Y') }}
                    </time>
                </article>
            @empty
                <div class="announcement-popup-empty">
                    <x-icon class="fa-regular fa-newspaper" aria-hidden="true" />
                    <p>No announcements at this time.</p>
                </div>
            @endforelse
        </div>
        <div class="announcement-popup-footer">
            <span>{{ $news->count() }} {{ \Illuminate\Support\Str::plural('announcement', $news->count()) }}</span>
            <button class="announcement-popup-done" type="button" data-announcement-close>Done</button>
        </div>
    </dialog>

    {{-- ===== CTA FINAL SECTION ===== --}}
    <section class="cta-final">
        <div class="cta-content fade-in-up">
            <h2>Start Your Journey Today</h2>
            <p>Be part of the nation-building workforce. Earn while you learn.</p>
            <a href="{{ route('register') }}" class="btn-giant">
                Apply for SPES Now <x-icon class="fa-solid fa-arrow-right" />
            </a>
        </div>
    </section>

    {{-- ===== FOOTER ===== --}}
    <footer>
        <div class="footer-container">
            <div class="footer-info">
                <h4>PESO LAL-LO</h4>
                <p>Located on:P. DUPAYA STREET, CENTRO, LAL-LO, CAGAYAN, 3509
                    </p>
                <p>Email: lgulalloinformationoffice@gmail.com</p>
            </div>
            <div class="footer-socials">
                <span class="footer-kicker">Stay connected</span>
                <a class="social-links social-links-primary" href="https://www.facebook.com/share/1EacDYqY7N/" target="_blank" rel="noopener noreferrer" aria-label="Visit PESO LAL-LO on Facebook">
                    <x-icon class="fa-brands fa-square-facebook" aria-hidden="true" />
                    <span>
                        <strong>Follow us on Facebook</strong>
                        <small>Visit our official page <x-icon class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true" /></small>
                    </span>
                </a>
                <a class="social-links social-links-secondary" href="https://www.instagram.com/official.lgulallo?igsh=MXU5dDJ1anR0dzEzaQ==" target="_blank" rel="noopener noreferrer" aria-label="Follow PESO LAL-LO on Instagram">
                    <x-icon class="fa-brands fa-square-instagram" aria-hidden="true" />
                    <span>Follow us on Instagram</span>
                </a>
            </div>
        </div>
        <div class="copyright">
            &copy; {{ date('Y') }} SPES Management System. In compliance with RA 10917.
        </div>
    </footer>

    {{-- Your custom JavaScript (public/js/auth.js) --}}
    <script src="{{ asset('js/auth.js') }}?v={{ filemtime(public_path('js/auth.js')) }}"></script>

</body>
</html>