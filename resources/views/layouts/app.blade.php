@php($novelTheme = $novelTheme ?? \App\Support\NovelTheme::DEFAULT)
<!DOCTYPE html>
<html lang="id" class="scroll-smooth" data-theme="{{ $novelTheme }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Bukuku') }}</title>

        {{-- Only the active theme's fonts are fetched. --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="{{ \App\Support\NovelTheme::fontUrl($novelTheme) }}" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased">
        <div class="min-h-screen flex flex-col">
            @include('layouts.navigation')

            @isset($header)
                <header class="border-b border-line/15 bg-surface/50">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            @if (session('status') || session('error'))
                <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-5">
                    @if (session('status'))
                        <div class="panel border-l-4 border-accent px-4 py-3 flex items-center gap-3 text-sm text-ink">
                            <span class="text-accent-dark text-lg"></span>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="panel border-l-4 border-danger px-4 py-3 flex items-center gap-3 text-sm text-danger">
                            <span class="text-lg"></span>
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif
                </div>
            @endif

            <main class="flex-1">
                {{ $slot }}
            </main>

            <footer class="mt-12 border-t border-line/15 bg-surface/40">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-xs text-ink-light font-display tracking-wider uppercase">
                    {{ config('app.name') }} · Ruang Kerja Penulis · © {{ date('Y') }}
                </div>
            </footer>
        </div>
    </body>
</html>
