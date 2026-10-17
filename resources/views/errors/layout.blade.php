@php
    $code = $code ?? 404;
    $label = $label ?? 'Terjadi kesalahan';
    $heading = $heading ?? 'Ada yang tidak beres';
    $message = $message ?? 'Silakan coba lagi beberapa saat lagi.';
    $primaryUrl = $primaryUrl ?? url('/');
    $primaryLabel = $primaryLabel ?? 'Kembali ke Beranda';
    $secondaryUrl = $secondaryUrl ?? null;
    $secondaryLabel = $secondaryLabel ?? 'Halaman Sebelumnya';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $code }} — {{ $label }} | Teknik Sepeda Motor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #0A1A3A;
            --navy-panel: #12285A;
            --accent: #7CC8FF;
            --ice: #EAF5FF;
            --muted: #C9DDF5;
            --subtle: #A9C3E3;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Manrope', system-ui, -apple-system, 'Segoe UI', sans-serif;
            color: var(--ice);
            background:
                radial-gradient(1000px 600px at 78% -10%, rgba(124, 200, 255, .22), transparent 60%),
                radial-gradient(800px 500px at 0% 100%, rgba(30, 79, 168, .35), transparent 65%),
                linear-gradient(160deg, var(--navy) 0%, #0B1F45 55%, var(--navy-panel) 100%);
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }
        /* Garis grid halus, senada hero landing */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, .045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .045) 1px, transparent 1px);
            background-size: 72px 72px;
            mask-image: radial-gradient(circle at 50% 40%, #000 25%, transparent 78%);
            -webkit-mask-image: radial-gradient(circle at 50% 40%, #000 25%, transparent 78%);
            pointer-events: none;
        }
        .wrap {
            position: relative;
            z-index: 1;
            flex: 1;
            width: 100%;
            max-width: 1040px;
            margin: 0 auto;
            padding: 32px 24px 24px;
            display: flex;
            flex-direction: column;
        }
        .top { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: var(--ice);
            text-decoration: none;
            font-family: 'Sora', system-ui, sans-serif;
            font-weight: 700;
            letter-spacing: -.2px;
        }
        .brand-mark {
            width: 44px; height: 44px;
            border-radius: 50%;
            border: 1px solid rgba(124, 200, 255, .5);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: 'JetBrains Mono', ui-monospace, monospace;
            font-size: 12px;
            letter-spacing: 1.5px;
            color: var(--accent);
        }
        .brand small {
            display: block;
            font-family: 'JetBrains Mono', ui-monospace, monospace;
            font-size: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--subtle);
            font-weight: 400;
            margin-top: 2px;
        }
        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 0;
        }
        .card {
            position: relative;
            width: 100%;
            max-width: 720px;
            padding: 48px 44px 40px;
            border-radius: 28px;
            background: linear-gradient(180deg, rgba(255, 255, 255, .07), rgba(255, 255, 255, .025));
            border: 1px solid rgba(124, 200, 255, .22);
            box-shadow: 0 30px 80px rgba(3, 10, 26, .45);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            text-align: center;
            overflow: hidden;
        }
        .card::after {
            content: '';
            position: absolute;
            inset: auto -40% -65% auto;
            width: 320px; height: 320px;
            background: radial-gradient(circle, rgba(124, 200, 255, .18), transparent 70%);
            pointer-events: none;
        }
        .code-wrap { position: relative; display: inline-block; margin-bottom: 8px; }
        .code {
            position: relative;
            font-family: 'Sora', system-ui, sans-serif;
            font-size: clamp(72px, 15vw, 128px);
            line-height: 1;
            font-weight: 800;
            letter-spacing: -6px;
            background: linear-gradient(180deg, #FFFFFF 15%, var(--accent) 95%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .gear {
            position: absolute;
            right: -46px;
            top: -26px;
            width: 112px; height: 112px;
            opacity: .28;
            animation: spin 26s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .label {
            margin: 6px 0 14px;
            font-family: 'JetBrains Mono', ui-monospace, monospace;
            font-size: 11px;
            letter-spacing: 2.4px;
            text-transform: uppercase;
            color: var(--accent);
        }
        h1 {
            margin: 0 0 14px;
            font-family: 'Sora', system-ui, sans-serif;
            font-size: clamp(22px, 3.4vw, 30px);
            line-height: 1.25;
            letter-spacing: -.6px;
            color: #FFFFFF;
        }
        .desc {
            margin: 0 auto 30px;
            max-width: 480px;
            font-size: 15.5px;
            line-height: 1.7;
            color: var(--muted);
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
            margin-bottom: 24px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 46px;
            padding: 0 24px;
            border-radius: 999px;
            font-family: 'Manrope', system-ui, sans-serif;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid transparent;
            transition: transform .18s ease, background .18s ease, border-color .18s ease, color .18s ease;
        }
        .btn:active { transform: translateY(1px); }
        .btn-primary { background: var(--accent); color: var(--navy); }
        .btn-primary:hover { background: #A6DBFF; }
        .btn-ghost { background: rgba(255, 255, 255, .06); color: var(--ice); border-color: rgba(201, 221, 245, .35); }
        .btn-ghost:hover { border-color: var(--accent); color: var(--accent); background: rgba(124, 200, 255, .08); }
        .btn:focus-visible, .brand:focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }
        .hint {
            margin: 0;
            font-family: 'JetBrains Mono', ui-monospace, monospace;
            font-size: 11px;
            letter-spacing: .6px;
            color: var(--subtle);
        }
        .hint span { color: var(--ice); }
        footer {
            padding: 12px 0 4px;
            text-align: center;
            font-size: 12px;
            color: var(--subtle);
        }
        @media (max-width: 600px) {
            .wrap { padding: 22px 16px 18px; }
            .card { padding: 36px 20px 30px; border-radius: 22px; }
            .code { letter-spacing: -3px; }
            .gear { right: -34px; top: -18px; width: 86px; height: 86px; }
            .actions .btn { width: 100%; }
        }
        @media (prefers-reduced-motion: reduce) {
            .gear { animation: none; }
            .btn { transition: none; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <header class="top">
            <a class="brand" href="{{ url('/') }}">
                <span class="brand-mark">TSM</span>
                <span>Teknik Sepeda Motor<small>SMKS Islam 1 Kota Blitar</small></span>
            </a>
        </header>

        <main>
            <section class="card" role="alert" aria-labelledby="err-title">
                <div class="code-wrap">
                    <svg class="gear" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                        <circle cx="24" cy="24" r="10" stroke="#7CC8FF" stroke-width="1.6"/>
                        <circle cx="24" cy="24" r="3.2" stroke="#7CC8FF" stroke-width="1.6"/>
                        <g stroke="#7CC8FF" stroke-width="1.6" stroke-linecap="round">
                            <path d="M24 4v6M24 38v6M4 24h6M38 24h6M9.9 9.9l4.2 4.2M33.9 33.9l4.2 4.2M38.1 9.9l-4.2 4.2M14.1 33.9l-4.2 4.2"/>
                        </g>
                    </svg>
                    <div class="code">{{ $code }}</div>
                </div>

                <p class="label">{{ $label }}</p>
                <h1 id="err-title">{{ $heading }}</h1>
                <p class="desc">{{ $message }}</p>

                <div class="actions">
                    <a class="btn btn-primary" href="{{ $primaryUrl }}">{{ $primaryLabel }}</a>
                    @if ($secondaryUrl)
                        <a class="btn btn-ghost" href="{{ $secondaryUrl }}">{{ $secondaryLabel }}</a>
                    @else
                        <a class="btn btn-ghost" href="{{ url('/') }}"
                           onclick="if (window.history.length > 1) { window.history.back(); return false; }">Halaman Sebelumnya</a>
                    @endif
                </div>

                <p class="hint">Kode galat <span>{{ $code }}</span> &middot; bila terus terjadi, hubungi pengelola website.</p>
            </section>
        </main>

        <footer>&copy; {{ date('Y') }} Teknik Sepeda Motor SMKS Islam 1 Kota Blitar</footer>
    </div>
</body>
</html>
