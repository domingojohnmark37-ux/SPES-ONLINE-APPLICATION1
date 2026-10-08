<!DOCTYPE html>
<html lang="{{ request()->attributes->get('applicant_language', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $documentTitle }} - {{ __('SPES Portal') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { display:flex; flex-direction:column; width:100%; height:100vh; margin:0; overflow:hidden; background:#eef1f5; color:#202124; font-family:'Segoe UI',Roboto,Arial,sans-serif; }
        .viewer-header { z-index:1; display:flex; min-height:68px; align-items:center; gap:16px; padding:10px 20px; border-bottom:1px solid #d8dde5; background:#fff; box-shadow:0 2px 8px rgba(15,23,42,.08); }
        .back-link { flex:0 0 auto; display:inline-flex; align-items:center; gap:9px; min-height:42px; padding:0 15px; border:1px solid #d5dbe3; border-radius:8px; background:#fff; color:#760000; font-size:.9rem; font-weight:700; text-decoration:none; transition:background .15s,border-color .15s; }
        .back-link:hover { border-color:#8b0000; background:#fff8f8; }
        .back-link:focus-visible { outline:3px solid #2563eb; outline-offset:2px; }
        .document-heading { min-width:0; }
        .document-heading h1 { overflow:hidden; margin:0; color:#202124; font-size:1rem; font-weight:700; text-overflow:ellipsis; white-space:nowrap; }
        .document-heading p { margin:3px 0 0; color:#687385; font-size:.78rem; }
        .document-frame { flex:1; width:100%; min-height:0; border:0; background:#3c4043; }
        @media(max-width:560px) {
            .viewer-header { min-height:60px; gap:10px; padding:8px 10px; }
            .back-link { width:42px; justify-content:center; padding:0; }
            .back-label { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; }
            .document-heading h1 { font-size:.88rem; }
        }
    </style>
</head>
<body>
    <header class="viewer-header">
        <a class="back-link" href="{{ $backUrl }}">
            <span aria-hidden="true">&larr;</span>
            <span class="back-label">{{ __('Back to application') }}</span>
        </a>
        <div class="document-heading">
            <h1>{{ $documentTitle }}</h1>
            <p>{{ $application->document_original_names[$document] ?? __('Submitted document') }}</p>
        </div>
    </header>
    <iframe class="document-frame" src="{{ $documentUrl }}" title="{{ __('Document preview') }}"></iframe>
</body>
</html>
