<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('custom-fields.index', $world) }}" class="text-sm text-ink hover:text-accent-dark">← Atribut · {{ $world->name }}</a>
        <h1 class="font-display text-2xl text-ink mt-1">Sunting Atribut</h1>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="panel p-6 sm:p-8">
            @include('manage.custom-fields._form')
        </div>

        <div class="panel border-l-4 border-danger p-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="font-display text-ink">Hapus atribut ini</p>
                <p class="text-sm text-ink-light">Semua isiannya di karakter/lokasi ikut terhapus.</p>
            </div>
            <form method="POST" action="{{ route('custom-fields.destroy', [$world, $field]) }}"
                  onsubmit="return confirm('Hapus atribut “{{ $field->name }}” beserta seluruh isiannya?');">
                @csrf
                @method('DELETE')
                <x-danger-button>Hapus Atribut</x-danger-button>
            </form>
        </div>
    </div>
</x-app-layout>
