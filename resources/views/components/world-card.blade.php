@props(['world'])

@php
    $statusBadge = ['active' => 'badge-success', 'concept' => 'badge-accent', 'archived' => 'badge-muted'][$world->status] ?? 'badge-muted';
    $locationsCount = ($world->benuas_count ?? 0) + ($world->negaras_count ?? 0) + ($world->provinsis_count ?? 0) + ($world->kotas_count ?? 0) + ($world->desas_count ?? 0);
@endphp

<a href="{{ route('worlds.show', $world) }}" class="lore-card group flex flex-col">
    <div class="relative aspect-[21/9] overflow-hidden bg-shell-dark">
        <img src="{{ $world->coverUrl() }}" alt="Sampul {{ $world->name }}"
             class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
        <span class="absolute top-2 left-2 {{ $statusBadge }}">{{ $world->statusLabel() }}</span>
    </div>
    <div class="p-4 flex-1 flex flex-col">
        <h3 class="font-display text-lg font-semibold text-ink leading-snug group-hover:text-accent-dark transition">{{ $world->name }}</h3>
        @if ($world->tagline)
            <p class="text-sm text-ink-light italic line-clamp-2 mt-1">{{ $world->tagline }}</p>
        @endif
        <div class="mt-auto pt-3 flex items-center gap-4 text-xs text-ink-light font-display">
            <span>👤 {{ $world->characters_count ?? 0 }} Karakter</span>
            <span>🗺 {{ $locationsCount }} Lokasi</span>
        </div>
    </div>
</a>
