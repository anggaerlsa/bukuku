@props(['title' => null])

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title . ' — ' . config('app.name') : config('app.name') . ' — Perancang Dunia & Lore' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Source+Serif+4:opsz,wght@8..60,400;8..60,600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col">
    <header class="sticky top-0 z-40 border-b-2 border-accent/40 panel-shell rounded-none shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between gap-4">
                <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                    <x-application-logo class="h-10 w-auto drop-shadow" />
                    <span class="leading-none">
                        <span class="block font-display text-2xl text-accent-light">{{ config('app.name') }}</span>
                        <span class="block font-display text-[0.6rem] uppercase tracking-[0.3em] text-white/60">Meja Perancang Dunia</span>
                    </span>
                </a>

                <nav class="flex items-center gap-1 sm:gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary btn-sm ml-1">Meja Kerja</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-primary btn-sm ml-1">Masuk</a>
                    @endauth
                </nav>
            </div>
        </div>
    </header>

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="mt-16 border-t-2 border-accent/30 panel-shell rounded-none">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 text-center">
            <x-application-logo class="h-12 w-auto mx-auto opacity-90" />
            <p class="mt-3 font-display text-2xl text-accent-light">{{ config('app.name') }}</p>
            <div class="divider max-w-xs mx-auto"></div>
            <p class="text-sm text-white/60">Tempat berkumpulnya kisah-kisah dari segala penjuru negeri.</p>
            <p class="mt-2 text-xs text-white/40 font-display tracking-wider uppercase">© {{ date('Y') }} {{ config('app.name') }} · Disusun dengan tinta &amp; perkamen</p>
        </div>
    </footer>
</body>
</html>
