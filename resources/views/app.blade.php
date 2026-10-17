<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        // Meta dasar dirender di server supaya pratinjau tautan (WhatsApp/Facebook/
        // Telegram) tetap tampil walau crawler-nya tidak menjalankan JavaScript.
        $__s = cache()->remember('site_settings', 3600, fn () => \App\Models\Setting::pluck('value', 'key')->toArray());
        $__school = $__s['school_name'] ?? 'SMKS Islam 1 Blitar';
        $__dept = $__s['department_name'] ?? 'Teknik Sepeda Motor';
        $__title = $__dept.' — '.$__school;
        $__desc = 'Jurusan '.$__dept.' '.$__school.': kompetensi keahlian, program unggulan, sarana praktik, prestasi, berita, dan testimoni alumni.';
        $__img = $__s['hero_bg'] ?? ($__s['site_logo'] ?? null);

        if (request()->routeIs('post.show')) {
            $__post = \App\Models\Post::where('slug', request()->route('slug'))->first(['title', 'excerpt', 'cover_image']);
            if ($__post) {
                $__title = $__post->title.' — '.$__school;
                $__desc = \Illuminate\Support\Str::limit(trim(strip_tags($__post->excerpt ?: $__desc)), 160);
                $__img = $__post->cover_image ?: $__img;
            }
        }

        $__imgUrl = $__img ? (str_starts_with($__img, 'http') ? $__img : url('/storage/'.$__img)) : null;
        $__fav = $__s['site_favicon'] ?? null;
        $__favUrl = $__fav ? (str_starts_with($__fav, 'http') ? $__fav : '/storage/'.$__fav) : '/favicon.ico';
    @endphp
    <title>{{ $__title }}</title>
    <meta name="description" content="{{ $__desc }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" href="{{ $__favUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $__school }}">
    <meta property="og:locale" content="id_ID">
    <meta property="og:title" content="{{ $__title }}">
    <meta property="og:description" content="{{ $__desc }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if($__imgUrl)
        <meta property="og:image" content="{{ $__imgUrl }}">
        <meta name="twitter:image" content="{{ $__imgUrl }}">
    @endif
    <meta name="twitter:card" content="{{ $__imgUrl ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $__title }}">
    <meta name="twitter:description" content="{{ $__desc }}">
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
