<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('novels.index') }}" class="text-sm text-ink hover:text-accent-dark">← Semua Novel</a>
            @can('update', $novel)
                <a href="{{ route('novels.edit', $novel) }}" class="btn-outline btn-sm">Sunting Novel</a>
            @endcan
        </div>
    </x-slot>

    @php($statusBadge = ['active' => 'badge-success', 'concept' => 'badge-accent', 'archived' => 'badge-muted'][$novel->status] ?? 'badge-muted')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        {{-- Novel header --}}
        <div class="grid sm:grid-cols-[10rem_1fr] gap-6">
            <div class="panel overflow-hidden">
                <img src="{{ $novel->coverUrl() }}" alt="Sampul {{ $novel->title }}" class="w-full aspect-[5/7] object-cover">
            </div>
            <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="{{ $statusBadge }}">{{ $novel->statusLabel() }}</span>
                    <span class="text-xs text-ink-light font-display uppercase tracking-wider">Novel</span>
                </div>
                <h1 class="font-display text-3xl sm:text-4xl text-ink leading-tight">{{ $novel->title }}</h1>
                @if ($novel->tagline)
                    <p class="text-lg text-ink-light italic">{{ $novel->tagline }}</p>
                @endif
                <p class="text-xs text-ink-light font-display uppercase tracking-wider">
                    Ditulis oleh {{ $novel->user?->name ?? 'sistem' }} ·
                    {{ $worlds->count() }} dunia · {{ $novel->charactersCount() }} karakter
                </p>
                @if ($novel->synopsis)
                    <div class="panel p-5">
                        <h2 class="label">Sinopsis</h2>
                        <p class="mt-1 text-ink leading-relaxed whitespace-pre-line">{{ $novel->synopsis }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Worlds belonging to this novel --}}
        <section>
            <div class="flex flex-wrap items-center gap-3 mb-5">
                <h2 class="font-display text-2xl text-ink">🌍 Dunia</h2>
                <span class="badge-accent">{{ $worlds->count() }}</span>
                <span class="h-px flex-1 bg-shell/20"></span>
                @can('create worlds')
                    <a href="{{ route('worlds.create') }}?novel={{ $novel->id }}" class="btn-primary btn-sm shrink-0">✚ Dunia</a>
                @endcan
            </div>

            @if ($worlds->isEmpty())
                <div class="panel p-12 text-center">
                    <p class="font-display text-xl text-ink">Novel ini belum punya dunia.</p>
                    <p class="text-ink-light mt-1">
                        Satu novel bisa menaungi banyak dunia — misalnya tiap planet, kota, atau dimensi yang disinggahi ceritanya.
                    </p>
                    @can('create worlds')
                        <a href="{{ route('worlds.create') }}?novel={{ $novel->id }}" class="btn-primary mt-4">✚ Dunia Pertama</a>
                    @endcan
                </div>
            @else
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($worlds as $world)
                        <x-world-card :world="$world" :show-novel="false" />
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
