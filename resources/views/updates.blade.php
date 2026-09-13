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
        .sidebar { position: fixed; inset: 0 auto 0 0; width: var(--sidebar-w); background: var(--primary-dark); display: flex; flex-direction: column; z-index: 100; }
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
        .topbar h1 { margin: 0; font-size: 1.15rem; color: var(--primary); }
        .topbar p { margin: 1px 0 0; font-size: .78rem; color: var(--text-muted); }
        .page-wrapper { margin-left: var(--sidebar-w); padding-top: 62px; min-height: 100vh; }
        .page-content { padding: 24px; max-width: 1000px; }
        .page-heading { margin-bottom: 20px; }
        .page-heading h2 { margin: 0; color: var(--primary); font-size: 1.4rem; }
        .page-heading p { margin: 6px 0 0; color: var(--text-muted); }
        .updates { display: grid; gap: 16px; }
        .update-card { background: var(--white); border-radius: 12px; box-shadow: var(--shadow); padding: 22px; border-left: 4px solid var(--primary); }
        .update-card h3 { margin: 0 0 8px; color: var(--primary); font-size: 1.05rem; }
        .update-date { color: var(--text-muted); font-size: .78rem; margin-bottom: 12px; }
        .update-content { line-height: 1.65; white-space: pre-line; }
        .empty { background: var(--white); border-radius: 12px; box-shadow: var(--shadow); padding: 48px 24px; text-align: center; color: var(--text-muted); }
        .empty i { color: #d8b4b4; font-size: 2.5rem; margin-bottom: 12px; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .topbar, .page-wrapper { margin-left: 0; left: 0; }
            .page-content { padding: 18px; }
        }
    </style>
</head>
<body>
<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="{{ asset('images/welcome_logo.jpg') }}" alt="SPES">
        <div><span>SPES Portal<small>PESO LAL-LO</small></span></div>
    </div>
    <nav class="sidebar-nav">
        <a href="{{ route('dashboard') }}" class="nav-link"><i class="fa-solid fa-house"></i> Dashboard</a>
        <a href="{{ route('applications.myApplication') }}" class="nav-link"><i class="fa-solid fa-file-lines"></i> My Application</a>
        <a href="{{ route('profile.edit') }}" class="nav-link"><i class="fa-solid fa-user-pen"></i> Edit Profile</a>
        <a href="{{ route('applications.create') }}" class="nav-link"><i class="fa-solid fa-file-circle-plus"></i> Apply Now</a>
        <a href="{{ route('updates') }}" class="nav-link active"><i class="fa-solid fa-newspaper"></i> Updates</a>
    </nav>
    <div class="sidebar-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Log Out</button>
        </form>
    </div>
</aside>

<header class="topbar">
    <div><h1>Updates</h1><p>Latest SPES announcements and notices</p></div>
</header>

<main class="page-wrapper">
    <div class="page-content">
        <div class="page-heading">
            <h2>Latest Updates</h2>
            <p>Stay informed about the latest news from PESO LAL-LO.</p>
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
                        <h3>{{ $update->title }}</h3>
                        <div class="update-date"><i class="fa-regular fa-calendar"></i> {{ $update->published_at->format('F j, Y') }}</div>
                        <div class="update-content">{{ $update->content }}</div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</main>
</body>
</html>
