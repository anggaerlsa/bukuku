<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('locations.index', $world) }}" class="text-sm text-ink hover:text-accent-dark">← Lokasi · {{ $world->name }}</a>
        <div class="flex flex-wrap items-center justify-between gap-3 mt-1">
            <h1 class="font-display text-2xl text-ink">{{ $node->name }}</h1>
            @can('update', $world)
                <div class="flex items-center gap-2">
                    <a href="{{ route('locations.edit', [$world, $tier, $node->id]) }}" class="btn-outline btn-sm">Sunting</a>
                    <form method="POST" action="{{ route('locations.destroy', [$world, $tier, $node->id]) }}"
                          onsubmit="return confirm('Hapus lokasi “{{ $node->name }}”?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn-danger btn-sm">Hapus</button>
                    </form>
                </div>
            @endcan
        </div>
    </x-slot>

    @php
        $childTier = $node->childTierKey();
        $children = $node->nodeChildren()->sortBy('name');
        $badge = ['benua' => 'badge-accent', 'negara' => 'badge-success', 'provinsi' => 'badge-danger', 'kota' => 'badge-muted', 'desa' => 'badge-muted'][$tier] ?? 'badge-muted';
    @endphp

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Breadcrumb / hierarchy trail --}}
        <div class="flex flex-wrap items-center gap-1.5 text-sm text-ink-light">
            @foreach ($ancestors as $ancestor)
                <a href="{{ route('locations.show', [$world, $ancestor->tierKey(), $ancestor->id]) }}" class="hover:text-accent-dark">{{ $ancestor->name }}</a>
                <span class="text-accent-dark/50">›</span>
            @endforeach
            <span class="{{ $badge }}">{{ $node->displayLabel() }}</span>
            @if ($node->type)<span class="text-xs text-ink-light">(tingkat {{ $node->tierLabel() }})</span>@endif
            <span class="text-ink font-display">{{ $node->name }}</span>
        </div>

        @if ($node->summary)
            <p class="text-lg text-ink-light italic">{{ $node->summary }}</p>
        @endif

        @if ($node->mapUrl())
            <div class="panel overflow-hidden">
                <img src="{{ $node->mapUrl() }}" alt="Peta {{ $node->name }}" class="w-full max-h-96 object-cover">
            </div>
        @endif

        @php($facts = array_filter(['Iklim' => $node->climate, 'Populasi' => $node->population, 'Pemerintahan' => $node->government]))
        @if ($facts)
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                @foreach ($facts as $label => $value)
                    <div class="panel p-4">
                        <p class="label">{{ $label }}</p>
                        <p class="text-ink mt-0.5">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        <x-custom-field-list :owner="$node" />

        @foreach (['Deskripsi' => $node->description, 'Geografi' => $node->geography, 'Tempat Menarik' => $node->points_of_interest] as $title => $body)
            @if ($body)
                <div class="panel p-6">
                    <h2 class="label">{{ $title }}</h2>
                    <p class="mt-1 text-ink leading-relaxed whitespace-pre-line">{{ $body }}</p>
                </div>
            @endif
        @endforeach

        <x-image-gallery :world="$world" :owner="$node" :type="$tier"
                         title="Galeri Lokasi"
                         hint="Peta utama diatur di form Sunting; gambar di sini adalah tambahannya." />

        {{-- Characters tied to this place --}}
        @if ($natives->isNotEmpty() || $residents->isNotEmpty())
            <section class="space-y-5">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="font-display text-xl text-ink">👤 Karakter Terkait</h2>
                    <span class="badge-accent">{{ $natives->count() + $residents->count() }}</span>
                    <span class="h-px flex-1 bg-shell/20"></span>
                </div>

                @foreach (['Berasal dari sini' => $natives, 'Berdomisili di sini' => $residents] as $heading => $group)
                    @if ($group->isNotEmpty())
                        <div>
                            <p class="label mb-2">{{ $heading }} · {{ $group->count() }}</p>
                            <div class="grid gap-4 grid-cols-2 sm:grid-cols-3 lg:grid-cols-5">
                                @foreach ($group as $character)
                                    <x-character-card :world="$world" :character="$character" />
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </section>
        @endif

        {{-- Sub-locations (next tier down) --}}
        @if ($childTier)
            <section>
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <h2 class="font-display text-xl text-ink">Sub-lokasi · {{ \App\Support\Hierarchy::label($childTier) }}</h2>
                    <span class="badge-accent">{{ $children->count() }}</span>
                    <span class="h-px flex-1 bg-shell/20"></span>
                    @can('update', $world)
                        <a href="{{ route('locations.create', $world) }}?tier={{ $childTier }}&parent={{ $node->id }}" class="btn-primary btn-sm shrink-0">✚ {{ \App\Support\Hierarchy::label($childTier) }}</a>
                    @endcan
                </div>

                @if ($children->isEmpty())
                    <div class="panel p-8 text-center text-ink-light">
                        Belum ada {{ \App\Support\Hierarchy::label($childTier) }} di dalam {{ $node->name }}.
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($children as $child)
                            <x-location-card :world="$world" :location="$child" />
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    </div>
</x-app-layout>
