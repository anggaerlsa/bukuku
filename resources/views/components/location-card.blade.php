@props(['world', 'location'])

@php($parent = $location->parentNode())

<a href="{{ route('locations.show', [$world, $location->tierKey(), $location->id]) }}" class="panel p-4 block hover:shadow-accent transition group">
    <div class="flex items-start gap-3">
        <div class="grid place-items-center h-10 w-10 rounded-lg bg-success/15 text-xl shrink-0">🗺️</div>
        <div class="min-w-0">
            <h3 class="font-display text-ink group-hover:text-accent-dark transition line-clamp-1">{{ $location->name }}</h3>
            <p class="text-xs text-ink-light">
                {{ $location->displayLabel() }}@if ($parent) · di {{ $parent->name }}@endif
            </p>
            @if ($location->summary)
                <p class="text-sm text-ink-light/90 mt-1 line-clamp-2">{{ $location->summary }}</p>
            @endif
        </div>
    </div>
</a>
