<style>
    :root {
        --type-title: 1.75rem;
        --type-section: 1.25rem;
        --type-body: 1rem;
        --type-secondary: .875rem;
        --type-caption: .75rem;
        --type-primary-color: #20242a;
        --type-secondary-color: #525d69;
        --type-caption-color: #59636f;
        --type-line-height: 1.55;
    }
    html[data-theme="dark"] {
        --type-primary-color: #f4f6f8;
        --type-secondary-color: #c6cbd2;
        --type-caption-color: #b4bbc4;
    }
    @media(prefers-color-scheme:dark) {
        html[data-theme="system"] {
            --type-primary-color: #f4f6f8;
            --type-secondary-color: #c6cbd2;
            --type-caption-color: #b4bbc4;
        }
    }
    .text-title {
        margin:0;
        color:var(--type-primary-color);
        font-size:var(--type-title);
        font-weight:700;
        line-height:1.2;
    }
    .text-section {
        margin:0;
        color:var(--type-primary-color);
        font-size:var(--type-section);
        font-weight:600;
        line-height:1.3;
    }
    .text-primary-line {
        display:block;
        color:var(--type-primary-color);
        font-size:var(--type-body);
        font-weight:600;
        line-height:1.4;
    }
    .text-secondary {
        display:block;
        margin: .3rem 0 0;
        color:var(--type-secondary-color);
        font-size:var(--type-secondary);
        font-weight:400;
        line-height:var(--type-line-height);
    }
    .text-caption {
        display:block;
        color:var(--type-caption-color);
        font-size:var(--type-caption);
        font-weight:400;
        line-height:1.45;
    }
    .info-item { min-width:0; }
    .info-item + .info-item { margin-top:1rem; }

    [data-app-theme="dark"] {
        --theme-bg:#151315;
        --theme-surface:#1D1A1C;
        --theme-card:#252022;
        --theme-elevated:#2D2729;
        --theme-maroon:#7F1D2D;
        --theme-maroon-hover:#9B2C3D;
        --theme-mustard:#D4A72C;
        --theme-mustard-bright:#E5B83C;
        --theme-text:#F8F5F0;
        --theme-text-secondary:#BDB5B0;
        --theme-text-muted:#8F8788;
        --theme-border:#3A3234;
        --theme-success:#4ADE80;
        --theme-success-bg:rgba(74,222,128,.12);
        --theme-danger:#F87171;
        --theme-danger-bg:rgba(248,113,113,.12);
        --theme-pending:#E5B83C;
        --theme-pending-bg:rgba(212,167,44,.14);
        --theme-review:#E7A0AC;
        --theme-review-bg:rgba(127,29,45,.28);
        --bg:var(--theme-bg);
        --surface:var(--theme-surface);
        --white:var(--theme-card);
        --primary:var(--theme-maroon);
        --primary-dark:var(--theme-bg);
        --accent:var(--theme-mustard);
        --text:var(--theme-text);
        --text-muted:var(--theme-text-secondary);
        --muted:var(--theme-text-secondary);
        --border:var(--theme-border);
        --shadow:0 2px 12px rgba(0,0,0,.16);
        color-scheme:dark;
        background-color:var(--theme-bg);
        color:var(--theme-text);
    }

    html[data-theme="dark"] body .topbar[data-app-theme="dark"],
    html[data-theme="dark"] body .peso-topbar[data-app-theme="dark"] {
        border-bottom:1px solid var(--theme-border);
        background:var(--theme-surface);
        color:var(--theme-text);
        box-shadow:0 2px 12px rgba(0,0,0,.16);
        font-family:Inter, "Plus Jakarta Sans", system-ui, sans-serif;
    }

    @media(prefers-color-scheme:dark) {
        html[data-theme="system"] body .topbar[data-app-theme="dark"],
        html[data-theme="system"] body .peso-topbar[data-app-theme="dark"] {
            border-bottom:1px solid var(--theme-border);
            background:var(--theme-surface);
            color:var(--theme-text);
            box-shadow:0 2px 12px rgba(0,0,0,.16);
            font-family:Inter, "Plus Jakarta Sans", system-ui, sans-serif;
        }
    }

    .topbar[data-app-theme="dark"] :is(h1, h2, h3, .text-primary-line) {
        color:var(--theme-text);
    }

    .topbar[data-app-theme="dark"] :is(button, .hamburger, .peso-menu-button) {
        color:var(--theme-mustard-bright);
    }

    .topbar[data-app-theme="dark"] .user-pill > div:first-child {
        color:var(--theme-text) !important;
    }

    .topbar[data-app-theme="dark"] .user-pill > div:last-child {
        color:var(--theme-text-secondary) !important;
    }

    .topbar[data-app-theme="dark"] .notif-dropdown {
        border:1px solid var(--theme-border);
        background:var(--theme-elevated);
        color:var(--theme-text);
        box-shadow:0 10px 30px rgba(0,0,0,.3);
    }

    .topbar[data-app-theme="dark"] .notif-item {
        border-color:var(--theme-border);
        color:var(--theme-text);
    }

    .topbar[data-app-theme="dark"] .notif-item .meta div:last-child {
        color:var(--theme-text-secondary) !important;
    }

    .page-wrapper[data-app-theme="dark"],
    .peso-wrapper[data-app-theme="dark"] {
        min-height:100vh;
        background:var(--theme-bg);
        color:var(--theme-text);
        font-family:Inter, "Plus Jakarta Sans", system-ui, sans-serif;
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(
        h1, h2, h3, h4, h5, h6, .detail-value, .stat-num, .stat-label,
        .text-primary-line, .text-title, .text-section
    ) {
        color:var(--theme-text);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(
        p, small, .description, .text-secondary, .text-caption, .detail-label,
        .stat-label, .text-gray-500, .text-gray-600, .text-slate-600, .text-slate-700
    ) {
        color:var(--theme-text-secondary);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(
        .card, .form-card, .settings-card, .contact-card, .update-card, .empty,
        .profile-card, .profile-page-card, .technical-card, .progress, .stat-card,
        .quick-actions, .table-card, .modal-content, .dropdown-menu
    ) {
        border-color:var(--theme-border);
        background:var(--theme-card);
        color:var(--theme-text);
        box-shadow:0 2px 12px rgba(0,0,0,.16);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(
        .card-header, .form-card-body, .card-body, .settings-card .subsection,
        .detail-item, .table-wrap, .faq-list details, .contact-card__icon
    ) {
        border-color:var(--theme-border);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(
        .card-header, .card-body, .form-card-body, .doc-item,
        .detail-item, .detail-grid, .faq-list details, .switch-row, .detail,
        .requirement-item, .quick-action-link, .contact-card, .technical-card
    ) {
        color:var(--theme-text);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] .card-header,
    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] .form-card-header {
        border-color:var(--theme-border);
        background:var(--theme-elevated);
        color:var(--theme-text);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] .form-card-header :is(h2, p, .text-secondary) {
        color:var(--theme-text);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(
        input:not([type="checkbox"]):not([type="radio"]),
        select, textarea, .search-bar input, .search-bar select
    ) {
        border-color:var(--theme-border);
        background-color:var(--theme-surface);
        color:var(--theme-text);
        color-scheme:dark;
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(input, select, textarea, button, a, summary):focus-visible {
        outline:2px solid var(--theme-mustard);
        outline-offset:2px;
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(input, textarea)::placeholder {
        color:var(--theme-text-muted);
        opacity:1;
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] input[type="checkbox"],
    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] input[type="radio"] {
        accent-color:var(--theme-mustard);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] input[type="file"]::file-selector-button {
        border:1px solid var(--theme-border);
        border-radius:.4rem;
        background:var(--theme-elevated);
        color:var(--theme-text);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] input:-webkit-autofill,
    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] input:-webkit-autofill:hover,
    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] input:-webkit-autofill:focus {
        -webkit-text-fill-color:var(--theme-text);
        box-shadow:0 0 0 1000px var(--theme-surface) inset;
        caret-color:var(--theme-text);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] table {
        color:var(--theme-text-secondary);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(thead, th) {
        background:var(--theme-elevated);
        color:var(--theme-text-secondary);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(th, td, tr, hr, .detail-item, .subsection) {
        border-color:var(--theme-border);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] tbody tr:hover td {
        background:var(--theme-elevated);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(
        a:not(.btn):not(.btn-apply):not(.btn-form):not(.portal-button),
        .btn-outline, .contact-email, .section-links a
    ) {
        color:var(--theme-mustard-bright);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(
        .btn-primary, .btn-apply, .btn-form-fill, .portal-button:not(.portal-button--secondary),
        .btn:not(.btn-secondary):not(.btn-outline)
    ) {
        border-color:var(--theme-maroon);
        background:var(--theme-maroon);
        color:var(--theme-text);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(
        .btn-primary, .btn-apply, .btn-form-fill, .portal-button:not(.portal-button--secondary),
        .btn:not(.btn-secondary):not(.btn-outline)
    ):hover:not(:disabled) {
        border-color:var(--theme-maroon-hover);
        background-color:var(--theme-maroon-hover);
        color:var(--theme-text);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(
        .btn-outline, .btn-secondary, .portal-button--secondary, .quick-action-link, .section-links a
    ) {
        border:1px solid var(--theme-border);
        background:var(--theme-surface);
        color:var(--theme-text);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(
        .btn-outline, .btn-secondary, .portal-button--secondary, .quick-action-link, .section-links a
    ):hover:not(:disabled) {
        border-color:var(--theme-border);
        background:var(--theme-elevated);
        color:var(--theme-text);
        transform:translateY(-1px);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] button:not(.btn-secondary):not(.portal-button--secondary):not([class*="secondary"]):not(.bg-white),
    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] input[type="submit"],
    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] input[type="button"] {
        border-color:var(--theme-maroon);
        background-color:var(--theme-maroon);
        color:var(--theme-text);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] button[class*="secondary"],
    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] button.bg-white {
        border:1px solid var(--theme-border);
        background:var(--theme-surface);
        color:var(--theme-text);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] button:not(.btn-secondary):not(.portal-button--secondary):not([class*="secondary"]):not(.bg-white):hover:not(:disabled),
    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] input[type="submit"]:hover:not(:disabled),
    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] input[type="button"]:hover:not(:disabled) {
        border-color:var(--theme-maroon-hover);
        background-color:var(--theme-maroon-hover);
        color:var(--theme-text);
    }

    .page-wrapper[data-app-theme="dark"] .notification-icon {
        background:var(--theme-elevated);
        color:var(--theme-text-secondary);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(
        button, input[type="button"], input[type="submit"], input[type="reset"], .btn, .btn-apply, .portal-button
    ):disabled {
        cursor:not-allowed;
        opacity:.55;
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(
        .badge-pending, .status-pending
    ) {
        border-color:var(--theme-mustard);
        background:var(--theme-pending-bg);
        color:var(--theme-pending);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(
        .badge-approved, .badge-done, .status-approved
    ) {
        border-color:var(--theme-success);
        background:var(--theme-success-bg);
        color:var(--theme-success);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(
        .badge-denied, .status-denied, .status-rejected
    ) {
        border-color:var(--theme-danger);
        background:var(--theme-danger-bg);
        color:var(--theme-danger);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(.badge-review, .status-review, .under-review) {
        border-color:var(--theme-maroon);
        background:var(--theme-review-bg);
        color:var(--theme-review);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(
        .alert-success, .message, .all-done-banner
    ) {
        border-color:var(--theme-success);
        background:var(--theme-success-bg);
        color:var(--theme-success);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(
        .alert-danger, .errors, .alert-error
    ) {
        border-color:var(--theme-danger);
        background:var(--theme-danger-bg);
        color:var(--theme-danger);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(
        .alert-info, .comment-box, .contact-card__icon, .file-upload-area, .hero-ill
    ) {
        border-color:var(--theme-border);
        background:var(--theme-elevated);
        color:var(--theme-text-secondary);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] .alert-warning {
        border-color:var(--theme-mustard);
        background:var(--theme-pending-bg);
        color:var(--theme-pending);
    }

    .page-wrapper[data-app-theme="dark"] .stats-row .stat-card:nth-child(1) .stat-icon {
        background:var(--theme-elevated) !important;
        color:var(--theme-text) !important;
    }

    .page-wrapper[data-app-theme="dark"] .stats-row .stat-card:nth-child(2) .stat-icon,
    .page-wrapper[data-app-theme="dark"] .stats-row .stat-card:nth-child(4) .stat-icon {
        background:var(--theme-pending-bg) !important;
        color:var(--theme-mustard-bright) !important;
    }

    .page-wrapper[data-app-theme="dark"] .stats-row .stat-card:nth-child(3) .stat-icon {
        background:var(--theme-success-bg) !important;
        color:var(--theme-success) !important;
    }

    .page-wrapper[data-app-theme="dark"] .hero-ill svg.icon,
    .page-wrapper[data-app-theme="dark"] .quick-action-link svg.icon {
        color:var(--theme-mustard-bright) !important;
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(.btn-outline, .btn-secondary, .portal-button--secondary) {
        border:1px solid var(--theme-border);
        background:var(--theme-surface);
        color:var(--theme-text);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] .contact-card__details,
    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] .technical-list {
        color:var(--theme-text-secondary);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] .contact-email {
        color:var(--theme-mustard-bright);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] .pagination :is(a, span) {
        border-color:var(--theme-border);
        background:var(--theme-card);
        color:var(--theme-text);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] .pagination .active {
        border-color:var(--theme-maroon);
        background:var(--theme-maroon);
        color:var(--theme-text);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] ::-webkit-scrollbar {
        width:.65rem;
        height:.65rem;
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] ::-webkit-scrollbar-track {
        background:var(--theme-surface);
    }

    :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] ::-webkit-scrollbar-thumb {
        border:2px solid var(--theme-surface);
        border-radius:999px;
        background:var(--theme-border);
    }

    .portal-help-panel[data-app-theme="dark"] {
        --theme-bg:#151315;
        --theme-surface:#1D1A1C;
        --theme-card:#252022;
        --theme-elevated:#2D2729;
        --theme-maroon:#7F1D2D;
        --theme-mustard:#D4A72C;
        --theme-text:#F8F5F0;
        --theme-text-secondary:#BDB5B0;
        --theme-border:#3A3234;
        border-color:var(--theme-border);
        background:var(--theme-card);
        color:var(--theme-text);
        color-scheme:dark;
    }

    .portal-help-panel[data-app-theme="dark"] .portal-help-header {
        background:var(--theme-maroon);
        color:var(--theme-text);
    }

    .portal-help-panel[data-app-theme="dark"] :is(.portal-help-thread, .portal-help-questions) {
        border-color:var(--theme-border);
        background:var(--theme-surface);
        color:var(--theme-text-secondary);
    }

    .portal-help-panel[data-app-theme="dark"] :is(.portal-help-message-bot, .portal-help-question) {
        border-color:var(--theme-border);
        background:var(--theme-elevated);
        color:var(--theme-text);
    }

    .portal-help-panel[data-app-theme="dark"] .portal-help-message-user {
        background:var(--theme-review-bg, rgba(127,29,45,.28));
        color:var(--theme-text);
    }

    .portal-help-panel[data-app-theme="dark"] :is(input, textarea, button):focus-visible {
        outline:2px solid var(--theme-mustard);
        outline-offset:2px;
    }

    @media(prefers-reduced-motion:no-preference) {
        :is(.page-wrapper, .peso-wrapper)[data-app-theme="dark"] :is(button, .btn, .btn-apply, .portal-button) {
            transition:background-color .16s ease, border-color .16s ease, transform .16s ease;
        }
    }

    html[data-theme="dark"] .how-item,
    html[data-theme="system"] .how-item {
        background-color:#24272d;
        color:var(--type-primary-color);
    }
    html[data-theme="dark"] .how-item .text-secondary,
    html[data-theme="system"] .how-item .text-secondary {
        color:var(--type-secondary-color);
    }
    @media(max-width:600px) {
        :root { --type-title:1.55rem; --type-section:1.15rem; }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const appearance = document.documentElement.dataset.theme;
        const useDarkTheme = appearance === 'dark'
            || (appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

        if (!useDarkTheme) return;

        document.querySelectorAll('.page-wrapper, .peso-wrapper, .topbar, .peso-topbar, .portal-help-panel')
            .forEach(function (element) {
                element.dataset.appTheme = 'dark';
            });
    });
</script>
