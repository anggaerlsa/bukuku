@props(['world', 'character'])

<a href="{{ route('characters.show', [$world, $character]) }}" class="lore-card group flex flex-col">
    <div class="relative aspect-[4/5] overflow-hidden bg-shell-dark">
        <img src="{{ $character->portraitUrl() }}" alt="Potret {{ $character->name }}"
             class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
        @if ($character->roleLabel())
            <span class="absolute top-2 left-2 badge-accent">{{ $character->roleLabel() }}</span>
        @endif
    </div>
    <div class="p-3">
        <h3 class="font-display text-ink group-hover:text-accent-dark transition line-clamp-1">{{ $character->name }}</h3>
        <p class="text-xs text-ink-light line-clamp-1">
            {{ collect([$character->species, $character->occupation])->filter()->implode(' · ') ?: 'Karakter' }}
        </p>
    </div>
</a>
