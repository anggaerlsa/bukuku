<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('novels.show', $novel) }}" class="text-sm text-ink hover:text-accent-dark">← {{ $novel->title }}</a>
        <h1 class="font-display text-2xl text-ink mt-1">Sunting Novel</h1>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="panel p-6 sm:p-8">
            @include('manage.novels._form')
        </div>

        @can('delete', $novel)
            <div class="panel border-l-4 border-danger p-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-display text-ink">Hapus novel ini</p>
                    <p class="text-sm text-ink-light">
                        Hanya bisa jika novel ini sudah tidak punya dunia.
                    </p>
                </div>
                <form method="POST" action="{{ route('novels.destroy', $novel) }}"
                      onsubmit="return confirm('Hapus novel “{{ $novel->title }}”?');">
                    @csrf
                    @method('DELETE')
                    <x-danger-button>Hapus Novel</x-danger-button>
                </form>
            </div>
        @endcan
    </div>
</x-app-layout>
