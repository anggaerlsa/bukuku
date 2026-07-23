@props(['novel'])

@php($statusBadge = ['active' => 'badge-success', 'concept' => 'badge-accent', 'archived' => 'badge-muted'][$novel->status] ?? 'badge-muted')

<a href="{{ route('novels.show', $novel) }}" class="lore-card group flex">
    <div class="relative w-24 shrink-0 overflow-hidden bg-shell-dark">
        <img src="{{ $novel->coverUrl() }}" alt="Sampul {{ $novel->title }}"
             class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
    </div>
    <div class="p-4 flex-1 flex flex-col min-w-0">
        <div class="flex items-start gap-2">
            <h3 class="font-display text-lg font-semibold text-ink leading-snug group-hover:text-accent-dark transition line-clamp-2">
                {{ $novel->title }}
            </h3>
            <span class="{{ $statusBadge }} shrink-0">{{ $novel->statusLabel() }}</span>
        </div>
        @if ($novel->tagline)
            <p class="text-sm text-ink-light italic line-clamp-2 mt-1">{{ $novel->tagline }}</p>
        @endif
        @if ($novel->relationLoaded('genres') && $novel->genres->isNotEmpty())
            <div class="flex flex-wrap gap-1 mt-2">
                @foreach ($novel->genres->take(3) as $genre)
                    <span class="badge-muted">{{ $genre->name }}</span>
                @endforeach
            </div>
        @endif
        <div class="mt-auto pt-3 text-xs text-ink-light font-display">
            🌍 {{ $novel->worlds_count ?? $novel->worlds()->count() }} Dunia
        </div>
    </div>
</a>
