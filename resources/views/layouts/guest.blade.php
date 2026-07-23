<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Bukuku') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Source+Serif+4:opsz,wght@8..60,400;8..60,600&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <div class="min-h-screen bg-shell flex flex-col justify-center items-center px-4 py-10">
            <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 mb-6 group">
                <x-application-logo class="h-20 w-auto drop-shadow-[0_4px_12px_rgba(79,70,229,0.25)] transition group-hover:scale-105" />
                <span class="font-display text-4xl text-accent-light">{{ config('app.name') }}</span>
                <span class="font-display text-[0.6rem] uppercase tracking-[0.35em] text-white/50">Meja Perancang Dunia</span>
            </a>

            <div class="w-full sm:max-w-md panel px-7 py-7">
                {{ $slot }}
            </div>

            <p class="mt-6 text-xs text-white/40 font-display uppercase tracking-[0.2em]">
                Hanya untuk para perancang dunia
            </p>
        </div>
    </body>
</html>
