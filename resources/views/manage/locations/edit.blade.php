<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('locations.show', [$world, $tier, $node->id]) }}" class="text-sm text-ink hover:text-accent-dark">← {{ $node->name }}</a>
        <h1 class="font-display text-2xl text-ink mt-1">Sunting Lokasi</h1>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="panel p-6 sm:p-8">
            @include('manage.locations._form')
        </div>

        <div class="panel border-l-4 border-danger p-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="font-display text-ink">Hapus lokasi ini</p>
                <p class="text-sm text-ink-light">Lokasi yang masih punya sub-lokasi harus dikosongkan dahulu.</p>
            </div>
            <form method="POST" action="{{ route('locations.destroy', [$world, $tier, $node->id]) }}"
                  onsubmit="return confirm('Hapus lokasi “{{ $node->name }}”?');">
                @csrf
                @method('DELETE')
                <x-danger-button>Hapus Lokasi</x-danger-button>
            </form>
        </div>
    </div>
</x-app-layout>
