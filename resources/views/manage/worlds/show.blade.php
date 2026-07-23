<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            @if ($world->novel)
                <a href="{{ route('novels.show', $world->novel) }}" class="text-sm text-ink hover:text-accent-dark">← 📕 {{ $world->novel->title }}</a>
            @else
                <a href="{{ route('worlds.index') }}" class="text-sm text-ink hover:text-accent-dark">← Semua Dunia</a>
            @endif
            <div class="flex items-center gap-2">
                <a href="{{ route('custom-fields.index', $world) }}" class="btn-outline btn-sm">⚙️ Atribut Dunia</a>
                @can('update', $world)
                    <a href="{{ route('worlds.edit', $world) }}" class="btn-outline btn-sm">Sunting Dunia</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        {{-- Banner --}}
        <div class="relative h-56 sm:h-72 rounded-xl overflow-hidden border border-line/25 shadow-card">
            <img src="{{ $world->coverUrl() }}" alt="Sampul {{ $world->name }}" class="absolute inset-0 h-full w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-shell-dark via-shell-dark/55 to-transparent"></div>
            <div class="relative h-full flex flex-col justify-end p-6 text-white">
                <div class="flex flex-wrap gap-2 mb-2">
                    @php($statusBadge = ['active' => 'badge-success', 'concept' => 'badge-accent', 'archived' => 'badge-muted'][$world->status] ?? 'badge-muted')
                    <span class="{{ $statusBadge }}">{{ $world->statusLabel() }}</span>
                    @foreach ($world->genres as $genre)
                        <span class="badge-accent">{{ $genre->name }}</span>
                    @endforeach
                </div>
                @if ($world->novel)
                    <a href="{{ route('novels.show', $world->novel) }}"
                       class="text-white/70 hover:text-accent-light font-display uppercase tracking-wider text-xs sm:text-sm">
                        📕 {{ $world->novel->title }}
                    </a>
                @endif
                <h1 class="font-display text-3xl sm:text-5xl text-accent-light leading-tight">{{ $world->name }}</h1>
                @if ($world->tagline)
                    <p class="text-white/80 italic mt-1 text-lg">{{ $world->tagline }}</p>
                @endif
                <p class="text-xs text-white/50 font-display uppercase tracking-wider mt-2">
                    Dibangun oleh {{ $world->user?->name ?? 'sistem' }} ·
                    {{ $world->characters_count }} karakter · {{ $world->locations_count }} lokasi
                </p>
            </div>
        </div>

        {{-- Premise --}}
        @if ($world->premise)
            <div class="panel p-6">
                <h2 class="label">Premis Dunia</h2>
                <p class="mt-1 text-ink leading-relaxed whitespace-pre-line">{{ $world->premise }}</p>
            </div>
        @endif

        {{-- Characters --}}
        <section>
            <div class="flex items-center gap-3 mb-5">
                <h2 class="font-display text-2xl text-ink">👤 Karakter</h2>
                <span class="badge-accent">{{ $world->characters_count }}</span>
                <span class="h-px flex-1 bg-shell/20"></span>
                <a href="{{ route('characters.index', $world) }}" class="text-sm text-ink hover:text-accent-dark shrink-0">Lihat semua →</a>
                @can('update', $world)
                    <a href="{{ route('characters.create', $world) }}" class="btn-primary btn-sm shrink-0">✚ Karakter</a>
                @endcan
            </div>

            @if ($characters->isEmpty())
                <div class="panel p-8 text-center text-ink-light">
                    Belum ada karakter di dunia ini.
                    @can('update', $world)<a href="{{ route('characters.create', $world) }}" class="text-ink underline hover:text-accent-dark">Tambahkan tokoh pertama</a>.@endcan
                </div>
            @else
                <div class="grid gap-4 grid-cols-2 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($characters as $character)
                        <x-character-card :world="$world" :character="$character" />
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Locations (hierarchy tree) --}}
        <section>
            <div class="flex items-center gap-3 mb-5">
                <h2 class="font-display text-2xl text-ink">🗺️ Lokasi</h2>
                <span class="badge-accent">{{ $locationsCount }}</span>
                <span class="h-px flex-1 bg-shell/20"></span>
                <a href="{{ route('locations.index', $world) }}" class="text-sm text-ink hover:text-accent-dark shrink-0">Kelola peta →</a>
                @can('update', $world)
                    <a href="{{ route('locations.create', $world) }}" class="btn-primary btn-sm shrink-0">✚ Benua</a>
                @endcan
            </div>

            @if ($benuas->isEmpty())
                <div class="panel p-8 text-center text-ink-light">
                    Peta dunia ini masih kosong.
                    @can('update', $world)<a href="{{ route('locations.create', $world) }}" class="text-ink underline hover:text-accent-dark">Tambahkan Benua pertama</a>.@endcan
                </div>
            @else
                <div class="panel overflow-hidden">
                    @foreach ($benuas as $benua)
                        <x-location-tree-node :world="$world" :node="$benua" />
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
