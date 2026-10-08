@php
    $phone = config('peso.phone');
    $email = config('peso.email');
    $emailUrl = filter_var($email, FILTER_VALIDATE_EMAIL) ? 'mailto:'.$email : null;
    $safeWebUrl = static function ($url): ?string {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true) ? $url : null;
    };
    $facebookUrl = $safeWebUrl(config('peso.facebook_url'));
    $mapEmbedUrl = config('peso.map_embed_url');
    $validMapEmbedUrl = is_string($mapEmbedUrl)
        && filter_var($mapEmbedUrl, FILTER_VALIDATE_URL)
        && parse_url($mapEmbedUrl, PHP_URL_SCHEME) === 'https'
        && parse_url($mapEmbedUrl, PHP_URL_HOST) === 'www.google.com'
        && str_starts_with(parse_url($mapEmbedUrl, PHP_URL_PATH) ?? '', '/maps/embed');
@endphp
<!DOCTYPE html>
<html lang="en" data-theme="system">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Contact Us - SPES Applicant Portal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { --peso-maroon:#720909; --peso-maroon-dark:#560606; --peso-gold:#eabf2b; --peso-bg:#f8fafc; --peso-border:#e2e8f0; --peso-text:#20242a; --peso-muted:#525d69; }
        body { margin:0; background:var(--peso-bg); color:var(--peso-text); font-family:ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; }
        .peso-topbar { position:fixed; z-index:90; top:0; right:0; left:260px; display:flex; align-items:center; height:62px; padding:0 2rem; background:#fff; border-bottom:1px solid var(--peso-border); }
        .peso-menu-button { display:none; margin-right:.75rem; border:0; background:transparent; color:var(--peso-maroon); cursor:pointer; }
        .peso-wrapper { min-height:100vh; margin-left:260px; padding:5.5rem 2rem 3rem; }
        .peso-content { width:min(72rem,100%); margin:0 auto; }
        .peso-section { margin-top:2rem; }
        .section-heading { margin-bottom:1rem; }
        .section-heading .text-secondary { margin-top:.4rem; }
        .contact-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
        .contact-card { display:flex; min-height:15rem; flex-direction:column; align-items:flex-start; padding:1.25rem; border:1px solid var(--peso-border); border-radius:1rem; background:#fff; box-shadow:0 4px 16px rgba(15,23,42,.05); }
        .contact-card__icon { display:grid; width:2.75rem; height:2.75rem; place-items:center; margin-bottom:1rem; border-radius:.8rem; background:#fff8dc; color:var(--peso-maroon); }
        .contact-card__icon svg { width:1.5rem; height:1.5rem; }
        .contact-card__content { width:100%; flex:1; }
        .contact-card__details { margin-top:.4rem; color:var(--peso-muted); font-size:.9rem; line-height:1.6; overflow-wrap:anywhere; }
        .contact-email { color:#075985; text-decoration:underline; text-underline-offset:.2em; }
        .contact-email:focus-visible { outline:3px solid #d5a900; outline-offset:2px; border-radius:.15rem; }
        .facebook-page-image { display:block; width:100%; height:auto; max-height:16rem; margin-top:1rem; border:1px solid var(--peso-border); border-radius:.75rem; object-fit:cover; object-position:top; }
        .office-map { width:100%; height:15rem; margin-top:1rem; border:0; border-radius:.75rem; }
        .portal-button { display:inline-flex; min-height:2.6rem; align-items:center; justify-content:center; gap:.45rem; margin-top:1rem; padding:.6rem .95rem; border-radius:.65rem; background:var(--peso-maroon); color:#fff; font-size:.9rem; font-weight:600; text-decoration:none; transition:background .15s ease; }
        .portal-button:hover { background:var(--peso-maroon-dark); }
        .portal-button:focus-visible { outline:3px solid #d5a900; outline-offset:2px; }
        .portal-button--secondary { background:#fff; border:1px solid #cbd5e1; color:var(--peso-maroon); }
        .portal-button--secondary:hover { background:#fff9e6; }
        .portal-button--disabled { cursor:not-allowed; border:1px solid #d1d5db; background:#e5e7eb; color:#4b5563; }
        .technical-card { margin-top:2.5rem; padding:1.5rem; border:1px solid #d1d5db; border-radius:1rem; background:#f3f4f6; }
        .technical-card__icon { display:grid; width:2.5rem; height:2.5rem; place-items:center; border-radius:.75rem; background:#e5e7eb; color:#374151; }
        .technical-card__icon svg { width:1.4rem; height:1.4rem; }
        .technical-content { display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-top:1rem; }
        .technical-list { margin:.6rem 0 0; padding-left:1.2rem; color:#4b5563; line-height:1.8; }
        .technical-divider { margin:2.5rem 0 0; border:0; border-top:1px solid #cbd5e1; }
        @media(max-width:900px) {
            .peso-topbar { left:0; padding:0 1rem; }
            .peso-menu-button { display:inline-flex; }
            .peso-wrapper { margin-left:0; padding:5rem 1.25rem 2.5rem; }
        }
        @media(max-width:640px) {
            .peso-wrapper { padding:4.75rem .85rem 2rem; }
            .peso-heading-subtitle { max-width:36rem; }
            .contact-grid, .technical-content { grid-template-columns:1fr; }
            .contact-card { min-height:0; }
        }
        html[data-theme="dark"] body { --peso-bg:#17191d; --peso-border:#434852; --peso-text:#f2f4f7; --peso-muted:#c6cbd2; background:var(--peso-bg); color:var(--peso-text); }
        html[data-theme="dark"] .contact-card, html[data-theme="dark"] .peso-topbar { background:#24272d; }
        html[data-theme="dark"] .contact-card__details { color:#c6cbd2; }
        html[data-theme="dark"] .contact-email { color:#7dd3fc; }
        html[data-theme="dark"] .portal-button--secondary { border-color:#59616d; background:#30343b; color:#fff; }
        html[data-theme="dark"] .technical-card { border-color:#4b5563; background:#24272d; }
        html[data-theme="dark"] .technical-card__icon { background:#3a3f47; color:#e5e7eb; }
        html[data-theme="dark"] .technical-list { color:#c6cbd2; }
        @media(prefers-color-scheme:dark) {
            html[data-theme="system"] body { --peso-bg:#17191d; --peso-border:#434852; --peso-text:#f2f4f7; --peso-muted:#c6cbd2; background:var(--peso-bg); color:var(--peso-text); }
            html[data-theme="system"] .contact-card, html[data-theme="system"] .peso-topbar { background:#24272d; }
            html[data-theme="system"] .contact-card__details { color:#c6cbd2; }
            html[data-theme="system"] .contact-email { color:#7dd3fc; }
            html[data-theme="system"] .portal-button--secondary { border-color:#59616d; background:#30343b; color:#fff; }
            html[data-theme="system"] .technical-card { border-color:#4b5563; background:#24272d; }
            html[data-theme="system"] .technical-card__icon { background:#3a3f47; color:#e5e7eb; }
            html[data-theme="system"] .technical-list { color:#c6cbd2; }
        }
    </style>
</head>
<body>
<x-applicant-sidebar />

<header class="peso-topbar">
    <button class="peso-menu-button" type="button" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false" aria-label="Toggle applicant navigation">
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-6 w-6"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <span class="text-primary-line">Contact Us</span>
</header>

<main class="peso-wrapper">
    <div class="peso-content">
        <div class="mb-8">
            <h1 class="text-title">Contact Us</h1>
            <p class="text-secondary peso-heading-subtitle">Need assistance with your SPES application? Contact PESO Lal-lo through any of the channels below.</p>
        </div>

        <section class="peso-section" aria-labelledby="peso-contact-heading">
            <x-section-heading id="peso-contact-heading" title="PESO Contact Information" description="Choose the channel that works best for your concern." />
            <div class="contact-grid">
                <x-contact-card title="Phone">
                    <x-slot:icon><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5.75A2.75 2.75 0 0 1 5.75 3h1.5A1.75 1.75 0 0 1 9 4.4l.7 2.8a1.75 1.75 0 0 1-.88 1.98l-1.1.6a14.2 14.2 0 0 0 6.5 6.5l.6-1.1a1.75 1.75 0 0 1 1.98-.88l2.8.7a1.75 1.75 0 0 1 1.4 1.75v1.5A2.75 2.75 0 0 1 18.25 21h-.5C10.16 21 3 13.84 3 6.25v-.5Z"/></svg></x-slot:icon>
                    <p>{{ $phone }}</p>
                    <p>Office hours: {{ config('peso.office_hours') }}</p>
                </x-contact-card>
                <x-contact-card title="Email">
                    <x-slot:icon><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5v10.5H3.75z"/><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 7.5 7.5 6 7.5-6"/></svg></x-slot:icon>
                    @if($emailUrl)
                        <p><a class="contact-email" href="{{ $emailUrl }}">{{ $email }}</a></p>
                    @else
                        <p>{{ $email }}</p>
                    @endif
                    <p>Send questions about your SPES application or requirements.</p>
                </x-contact-card>
                <x-contact-card title="Facebook" action-label="Visit Facebook" :action-url="$facebookUrl" :external="true">
                    <x-slot:icon><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14 8h3V4h-3a5 5 0 0 0-5 5v3H6v4h3v4h4v-4h3l1-4h-4V9a1 1 0 0 1 1-1Z"/></svg></x-slot:icon>
                    <p>Official PESO Lal-lo page</p>
                    <p>Follow official announcements and send us a message.</p>
                    <img
                        class="facebook-page-image"
                        src="{{ asset('images/peso-facebook-page.png') }}"
                        alt="Screenshot of the official PESO Lal-lo Facebook page"
                        loading="lazy"
                    >
                </x-contact-card>
                <x-contact-card title="PESO Office">
                    <x-slot:icon><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 10.2c0 5.1-7 10.3-7 10.3S5 15.3 5 10.2a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2.2"/></svg></x-slot:icon>
                    <p>{{ config('peso.office_name') }}</p>
                    <p>{{ config('peso.address') }}</p>
                    <p>Office hours: {{ config('peso.office_hours') }}</p>
                    @if($validMapEmbedUrl)
                        <iframe
                            class="office-map"
                            src="{{ $mapEmbedUrl }}"
                            title="Map to PESO Lal-lo Municipal Hall"
                            loading="lazy"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen
                        ></iframe>
                    @endif
                </x-contact-card>
            </div>
        </section>

        <hr class="technical-divider">
        <section class="technical-card" aria-labelledby="technical-support-heading">
            <div class="flex items-start gap-3">
                <span class="technical-card__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2M3 12h2m14 0h2M5.64 5.64l1.42 1.42m9.88 9.88 1.42 1.42m0-12.72-1.42 1.42m-9.88 9.88-1.42 1.42"/><circle cx="12" cy="12" r="4"/></svg></span>
                <div>
                    <h2 id="technical-support-heading" class="text-section">Developer / Technical Support</h2>
                    <p class="text-secondary">Having a technical problem with the SPES Portal? Contact the development team for website and system-related issues.</p>
                </div>
            </div>
            <div class="technical-content">
                <div>
                    <h3 class="text-primary-line">Technical issues we can help with</h3>
                    <ul class="technical-list">
                        <li>Login problems</li><li>System errors</li><li>Document upload problems</li><li>Pages not loading</li><li>Account technical issues</li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-primary-line">Developer contact</h3>
                    <p class="text-secondary">{{ config('peso.developer_name') }}</p>
                    <p class="text-secondary">{{ config('peso.developer_email') }}</p>
                    <p class="text-secondary">{{ config('peso.developer_phone') }}</p>
                    @if(filter_var(config('peso.developer_email'), FILTER_VALIDATE_EMAIL))
                        <a class="portal-button" href="mailto:{{ config('peso.developer_email') }}">Contact Developer</a>
                    @else
                        <button class="portal-button portal-button--disabled" type="button" disabled>Contact Developer</button>
                    @endif
                </div>
            </div>
            <p class="mt-5 text-sm leading-relaxed text-gray-700">For questions about your application, requirements, or appointments, please contact PESO above.</p>
        </section>
    </div>
</main>

<x-portal-help-chat />
</body>
</html>
