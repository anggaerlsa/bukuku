<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('characters.show', [$world, $character]) }}" class="text-sm text-ink hover:text-accent-dark">← {{ $character->name }}</a>
        <h1 class="font-display text-2xl text-ink mt-1">Sunting Karakter</h1>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="panel p-6 sm:p-8">
            @include('manage.characters._form')
        </div>

        <div class="panel border-l-4 border-danger p-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="font-display text-ink">Hapus karakter ini</p>
                <p class="text-sm text-ink-light">Tindakan permanen.</p>
            </div>
            <form method="POST" action="{{ route('characters.destroy', [$world, $character]) }}"
                  onsubmit="return confirm('Hapus karakter “{{ $character->name }}”?');">
                @csrf
                @method('DELETE')
                <x-danger-button>Hapus Karakter</x-danger-button>
            </form>
        </div>
    </div>
</x-app-layout>
