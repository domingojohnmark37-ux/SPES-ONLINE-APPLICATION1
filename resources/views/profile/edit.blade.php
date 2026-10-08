<!DOCTYPE html>
<html lang="{{ request()->attributes->get('applicant_language', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Profile') }} — {{ __('SPES Applicant Portal') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --primary: #8B0000;
            --primary-dark: #660000;
            --bg: #f0f2f5;
            --white: #fff;
            --text: #212121;
            --text-muted: #6b7280;
            --border: #e0e0e0;
            --shadow: 0 2px 12px rgba(0,0,0,.08);
            --sidebar-w: 260px;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Roboto, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .applicant-profile-page .topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-w);
            right: 0;
            height: 62px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            background: var(--white);
            box-shadow: var(--shadow);
            z-index: 90;
        }

        .applicant-profile-page .topbar .hamburger {
            display: none;
            background: none;
            border: none;
            color: var(--primary);
            font-size: 1.2rem;
            cursor: pointer;
            margin-right: 10px;
        }

        .applicant-profile-page .page-wrapper {
            margin-left: var(--sidebar-w);
            padding-top: 62px;
        }

        .applicant-profile-page .page-content {
            padding: 24px;
        }

        .applicant-profile-page .page-title {
            margin-bottom: 20px;
        }

        .applicant-profile-page .page-title h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .applicant-profile-page .page-title p {
            margin: 0.35rem 0 0;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .applicant-profile-page .page-grid {
            display: grid;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .applicant-profile-page .topbar {
                left: 0;
                padding: 0 14px;
            }

            .applicant-profile-page .topbar .hamburger {
                display: inline-block;
            }

            .applicant-profile-page .page-wrapper {
                margin-left: 0;
            }

            .applicant-profile-page .page-content {
                padding: 14px;
            }
        }
    </style>
</head>
<body class="applicant-profile-page">
    <x-applicant-sidebar />

    <header class="topbar">
        <div style="display:flex;align-items:center;">
            <button class="hamburger" type="button" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false" aria-label="Toggle applicant navigation">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
            </button>
            <div>
                <span class="text-primary-line">SPES Applicant Portal</span>
            </div>
        </div>
        <span style="font-size:.82rem;color:var(--text-muted);">{{ Auth::user()->email }}</span>
    </header>

    <div class="page-wrapper">
        <div class="page-content">
            @if (session('status') === 'profile-updated')
                <div style="background:#fff;border-left:4px solid #22c55e;padding:16px 20px;border-radius:12px;box-shadow:var(--shadow);margin-bottom:20px;">
                    <p style="margin:0;color:#14532d;"><i class="fa-solid fa-circle-check"></i> Profile updated successfully.</p>
                </div>
            @endif

            <div class="page-title">
                <h1 class="text-title">Profile</h1>
                <p class="text-secondary">{{ __('Manage your personal and applicant information.') }}</p>
            </div>

            <div class="page-grid" id="account-settings">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>
    </div>

    <x-portal-help-chat />
</body>
</html>
