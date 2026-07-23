<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="font-display text-2xl text-ink">Sunting Dunia</h1>
                <p class="text-sm text-ink-light">{{ $world->name }}</p>
            </div>
            <a href="{{ route('worlds.show', $world) }}" class="btn-outline btn-sm">Buka Dunia ↗</a>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="panel p-6 sm:p-8">
            @include('manage.worlds._form')
        </div>

        @can('delete', $world)
            <div class="panel border-l-4 border-danger p-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-display text-ink">Hapus dunia ini</p>
                    <p class="text-sm text-ink-light">Seluruh karakter &amp; lokasi di dalamnya ikut terhapus. Permanen.</p>
                </div>
                <form method="POST" action="{{ route('worlds.destroy', $world) }}"
                      onsubmit="return confirm('Hapus dunia “{{ $world->name }}” beserta seluruh lorenya?');">
                    @csrf
                    @method('DELETE')
                    <x-danger-button>Hapus Dunia</x-danger-button>
                </form>
            </div>
        @endcan
    </div>
</x-app-layout>
