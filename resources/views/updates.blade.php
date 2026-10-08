<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Updates - SPES Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #8b0000;
            --primary-dark: #720000;
            --bg: #f0f2f5;
            --white: #fff;
            --text: #212121;
            --text-muted: #6b7280;
            --border: #e0e0e0;
            --shadow: 0 2px 12px rgba(0,0,0,.08);
            --sidebar-w: 260px;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', Roboto, Arial, sans-serif; background: var(--bg); color: var(--text); }
        .sidebar { position: fixed; inset: 0 auto 0 0; width: var(--sidebar-w); height:100vh; background: var(--primary-dark); display: flex; flex-direction: column; z-index: 100; }
        .sidebar-brand { padding: 22px 20px 18px; border-bottom: 1px solid rgba(255,255,255,.1); display: flex; align-items: center; gap: 12px; }
        .sidebar-brand img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .sidebar-brand span { font-size: .95rem; font-weight: 700; color: #fff; line-height: 1.2; }
        .sidebar-brand small { display: block; font-size: .7rem; color: rgba(255,255,255,.5); }
        .sidebar-nav { padding: 16px 12px; flex: 1; }
        .nav-link { display: flex; align-items: center; gap: 12px; color: rgba(255,255,255,.95); text-decoration: none; padding: 14px 16px; border-radius: 12px; font-size: 1rem; transition: background .18s, transform .08s; margin-bottom: 10px; }
        .nav-link i { width: 16px; text-align: center; }
        .nav-link:hover { background: rgba(255,255,255,.04); transform: translateX(2px); }
        .nav-link.active { background: rgba(255,255,255,.12); font-weight: 700; color: #fff; }
        .sidebar-footer { padding: 14px 12px; border-top: 1px solid rgba(255,255,255,.1); }
        .btn-logout { width: 100%; padding: 9px 14px; border: 1.5px solid rgba(255,255,255,.3); border-radius: 7px; background: none; color: rgba(255,255,255,.8); cursor: pointer; font-size: .82rem; display: flex; align-items: center; justify-content: center; gap: 7px; }
        .topbar { position: fixed; top: 0; left: var(--sidebar-w); right: 0; height: 62px; background: var(--white); box-shadow: var(--shadow); display: flex; align-items: center; padding: 0 24px; z-index: 90; }
        .topbar h1 { margin: 0; font-size: var(--type-title); color: var(--type-primary-color); font-weight:700; }
        .topbar p { margin: .25rem 0 0; font-size: var(--type-secondary); color: var(--type-secondary-color); }
        .page-wrapper { margin-left: var(--sidebar-w); padding-top: 62px; min-height: 100vh; }
        .page-content { padding: 24px; max-width: 1000px; }
        .page-heading { margin-bottom: 20px; }
        .page-heading h2 { margin: 0; color: var(--type-primary-color); font-size: var(--type-section); font-weight:600; }
        .page-heading p { margin: .3rem 0 0; color: var(--type-secondary-color); font-size:var(--type-secondary); }
        .updates { display: grid; gap: 16px; }
        .update-card { background: var(--white); border-radius: 12px; box-shadow: var(--shadow); padding: 22px; border-left: 4px solid var(--primary); }
        .update-card h3 { margin: 0 0 .3rem; color: var(--type-primary-color); font-size: var(--type-body); font-weight:600; }
        .update-date { color: var(--type-caption-color); font-size: var(--type-caption); margin-bottom: .75rem; }
        .update-content { color:var(--type-secondary-color); font-size:var(--type-secondary); line-height: 1.65; white-space: pre-line; }
        .empty { background: var(--white); border-radius: 12px; box-shadow: var(--shadow); padding: 48px 24px; text-align: center; color: var(--text-muted); }
        .empty i { color: #d8b4b4; font-size: 2.5rem; margin-bottom: 12px; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .topbar, .page-wrapper { margin-left: 0; left: 0; }
            .page-content { padding: 18px; }
        }
        .hamburger { display:none; border:0; background:none; color:var(--primary); font-size:1.1rem; cursor:pointer; margin-right:12px; }
        @media (max-width: 768px) {
            .hamburger { display:block; }
        }
        @media (max-width: 767.98px) {
            .topbar { height:56px; padding:0 14px; }
            .topbar h1 { font-size:var(--type-section); }
            .topbar p { display:none; }
            .page-wrapper { padding-top:56px; }
            .page-content { padding:14px; }
            .page-heading h2 { font-size:1.2rem; }
            .update-card { padding:16px; }
            .update-content { overflow-wrap:anywhere; }
        }
    </style>
</head>
<body>
<x-applicant-sidebar />

<header class="topbar">
    <div style="display:flex;align-items:center;">
        <button class="hamburger" type="button" data-sidebar-toggle aria-label="Toggle applicant navigation" aria-controls="sidebar" aria-expanded="false">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>
        <div><h1>Updates</h1><p>Latest SPES announcements and notices</p></div>
    </div>
</header>

<main class="page-wrapper">
    <div class="page-content">
        <div class="page-heading">
            <h2 class="text-section">Latest Updates</h2>
            <p class="text-secondary">Stay informed about the latest news from PESO LAL-LO.</p>
        </div>

        @if($updates->isEmpty())
            <div class="empty">
                <i class="fa-solid fa-newspaper"></i>
                <div>No updates have been published yet.</div>
            </div>
        @else
            <div class="updates">
                @foreach($updates as $update)
                    <article class="update-card">
                        <h3 class="text-primary-line">{{ $update->title }}</h3>
                        <div class="update-date text-caption"><i class="fa-regular fa-calendar"></i> {{ $update->published_at->format('F j, Y') }}</div>
                        <div class="update-content text-secondary">{{ $update->content }}</div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</main>
<x-portal-help-chat />
</body>
</html>
