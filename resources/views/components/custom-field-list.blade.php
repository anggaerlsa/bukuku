@props(['owner', 'title' => 'Atribut Dunia'])

@php($entries = $owner->customFieldEntries()->filter(fn ($entry) => $entry['display'] !== null))

@if ($entries->isNotEmpty())
    <div class="panel p-4 space-y-2 text-sm">
        <p class="label">{{ $title }}</p>
        @foreach ($entries as $entry)
            <div class="flex justify-between gap-3 border-b border-line/10 pb-2 last:border-0 last:pb-0">
                <span class="text-ink-light font-display text-xs uppercase tracking-wider">{{ $entry['field']->name }}</span>
                <span class="text-ink text-right whitespace-pre-line">{{ $entry['display'] }}</span>
            </div>
        @endforeach
    </div>
@endif
