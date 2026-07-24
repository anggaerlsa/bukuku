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
                    {{-- Genres belong to the novel; shown here as inherited context. --}}
                    @foreach ($world->novel?->genres ?? [] as $genre)
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

        {{-- Organisasi --}}
        <section>
            <div class="flex items-center gap-3 mb-5">
                <h2 class="font-display text-2xl text-ink">🛡️ Organisasi</h2>
                <span class="badge-accent">{{ $world->organizations_count }}</span>
                <span class="h-px flex-1 bg-shell/20"></span>
                <a href="{{ route('organizations.index', $world) }}" class="text-sm text-ink hover:text-accent-dark shrink-0">Lihat semua →</a>
                @can('update', $world)
                    <a href="{{ route('organizations.create', $world) }}" class="btn-primary btn-sm shrink-0">✚ Organisasi</a>
                @endcan
            </div>

            @if ($organizations->isEmpty())
                <div class="panel p-8 text-center text-ink-light">
                    Belum ada organisasi — wangsa, pasukan, sekte, atau guild tempat para karakter bernaung.
                    @can('update', $world)<a href="{{ route('organizations.create', $world) }}" class="text-ink underline hover:text-accent-dark">Tambahkan yang pertama</a>.@endcan
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($organizations as $organization)
                        <x-organization-card :world="$world" :organization="$organization" />
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Lore --}}
        <section>
            <div class="flex items-center gap-3 mb-5">
                <h2 class="font-display text-2xl text-ink">📖 Lore</h2>
                <span class="badge-accent">{{ $world->lore_entries_count }}</span>
                <span class="h-px flex-1 bg-shell/20"></span>
                <a href="{{ route('lore.index', $world) }}" class="text-sm text-ink hover:text-accent-dark shrink-0">Lihat semua →</a>
                @can('update', $world)
                    <a href="{{ route('lore.create', $world) }}" class="btn-primary btn-sm shrink-0">✚ Artikel</a>
                @endcan
            </div>

            @if ($loreByCategory->isEmpty())
                <div class="panel p-8 text-center text-ink-light">
                    Belum ada artikel lore — aturan sihir, panteon, glosarium, teknologi, doktrin.
                    @can('update', $world)<a href="{{ route('lore.create', $world) }}" class="text-ink underline hover:text-accent-dark">Tulis yang pertama</a>.@endcan
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($loreByCategory as $groupName => $group)
                        <div class="panel p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <p class="label !mb-0">{{ $groupName }}</p>
                                <span class="badge-muted">{{ $group->count() }}</span>
                            </div>
                            <ul class="space-y-1">
                                @foreach ($group->take(5) as $entry)
                                    <li>
                                        <a href="{{ route('lore.show', [$world, $entry]) }}"
                                           class="text-sm text-ink hover:text-accent-dark line-clamp-1">{{ $entry->title }}</a>
                                    </li>
                                @endforeach
                                @if ($group->count() > 5)
                                    <li class="text-xs text-ink-light">+{{ $group->count() - 5 }} lagi</li>
                                @endif
                            </ul>
                        </div>
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
