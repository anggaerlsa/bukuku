@props(['world', 'organization'])

@php($statusBadge = ['aktif' => 'badge-success', 'bubar' => 'badge-muted', 'rahasia' => 'badge-danger'][$organization->status] ?? 'badge-muted')

<a href="{{ route('organizations.show', [$world, $organization]) }}" class="lore-card group flex">
    <div class="relative w-20 shrink-0 overflow-hidden bg-shell-dark">
        <img src="{{ $organization->emblemUrl() }}" alt="Lambang {{ $organization->name }}"
             class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
    </div>
    <div class="p-3 flex-1 flex flex-col min-w-0">
        <div class="flex items-start gap-2">
            <h3 class="font-display text-ink group-hover:text-accent-dark transition line-clamp-2 flex-1">{{ $organization->name }}</h3>
            <span class="{{ $statusBadge }} shrink-0">{{ $organization->statusLabel() }}</span>
        </div>
        <p class="text-xs text-ink-light line-clamp-1">
            {{ $organization->displayLabel() }}@if ($organization->parent) · di bawah {{ $organization->parent->name }}@endif
        </p>
        @if ($organization->summary)
            <p class="text-sm text-ink-light/90 mt-1 line-clamp-2">{{ $organization->summary }}</p>
        @endif
        <div class="mt-auto pt-2 text-xs text-ink-light font-display">
            👤 {{ $organization->memberships_count ?? $organization->memberships()->count() }} Anggota
        </div>
    </div>
</a>
