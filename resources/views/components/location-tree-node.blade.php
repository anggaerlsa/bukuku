@props(['world', 'node', 'depth' => 0, 'filter' => null])

@php
    $tier = $node->tierKey();
    $childTier = $node->childTierKey();
    $children = $node->nodeChildren()->sortBy('name');
    $badge = ['benua' => 'badge-accent', 'negara' => 'badge-success', 'provinsi' => 'badge-danger', 'kota' => 'badge-muted', 'desa' => 'badge-muted'][$tier] ?? 'badge-muted';
    // When a search is running, rows that matched are highlighted; the rest are
    // only here to keep the matches under their real ancestors.
    $hit = $filter?->isMatch($node) ?? false;
    $dimmed = $filter?->active() && ! $hit;
@endphp

<div class="border-b border-line/10 last:border-b-0">
    <div class="flex items-center gap-2 sm:gap-3 py-2.5 pr-3 {{ $hit ? 'bg-accent-soft' : '' }} {{ $dimmed ? 'opacity-60' : '' }}"
         style="padding-inline-start: {{ 0.75 + $depth * 1.5 }}rem">
        @if ($depth > 0)<span class="text-accent-dark/40 shrink-0">⤷</span>@endif
        <span class="{{ $badge }} shrink-0">{{ $node->displayLabel() }}</span>
        <a href="{{ route('locations.show', [$world, $tier, $node->id]) }}" class="font-display text-ink hover:text-accent-dark truncate min-w-0">{{ $node->name }}</a>
        @if ($node->type)<span class="text-xs text-ink-light truncate hidden sm:inline">· {{ $node->tierLabel() }}</span>@endif
        <span class="flex-1"></span>
        @can('update', $world)
            <div class="flex items-center gap-1 shrink-0">
                @if ($childTier)
                    <a href="{{ route('locations.create', $world) }}?tier={{ $childTier }}&parent={{ $node->id }}" class="btn-outline btn-sm" title="Tambah {{ \App\Support\Hierarchy::label($childTier) }} di {{ $node->name }}">✚ {{ \App\Support\Hierarchy::label($childTier) }}</a>
                @endif
                <a href="{{ route('locations.edit', [$world, $tier, $node->id]) }}" class="text-xs text-ink-light hover:text-accent-dark px-1.5">Sunting</a>
            </div>
        @endcan
    </div>

    @foreach ($children as $child)
        <x-location-tree-node :world="$world" :node="$child" :depth="$depth + 1" :filter="$filter" />
    @endforeach
</div>
