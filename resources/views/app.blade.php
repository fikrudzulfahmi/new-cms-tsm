<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $__s = cache()->remember('site_settings', 3600, fn () => \App\Models\Setting::pluck('value', 'key')->toArray());
        $__fav = $__s['site_favicon'] ?? null;
        $__favUrl = $__fav ? (str_starts_with($__fav, 'http') ? $__fav : '/storage/'.$__fav) : '/favicon.ico';
    @endphp
    <link rel="icon" href="{{ $__favUrl }}">
    @inertiaHead
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Manrope:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#0A1A3A">
</head>
<body class="font-sans antialiased bg-white text-navy">
    @inertia
</body>
</html>
