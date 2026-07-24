<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('lore.show', [$world, $entry]) }}" class="text-sm text-ink hover:text-accent-dark">← {{ $entry->title }}</a>
        <h1 class="font-display text-2xl text-ink mt-1">Sunting Artikel</h1>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="panel p-6 sm:p-8">
            @include('manage.lore._form')
        </div>

        <div class="panel border-l-4 border-danger p-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="font-display text-ink">Hapus artikel ini</p>
                <p class="text-sm text-ink-light">Tindakan permanen.</p>
            </div>
            <form method="POST" action="{{ route('lore.destroy', [$world, $entry]) }}"
                  onsubmit="return confirm('Hapus artikel “{{ $entry->title }}”?');">
                @csrf
                @method('DELETE')
                <x-danger-button>Hapus Artikel</x-danger-button>
            </form>
        </div>
    </div>
</x-app-layout>
