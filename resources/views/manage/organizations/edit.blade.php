<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('organizations.show', [$world, $organization]) }}" class="text-sm text-ink hover:text-accent-dark">← {{ $organization->name }}</a>
        <h1 class="font-display text-2xl text-ink mt-1">Sunting Organisasi</h1>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="panel p-6 sm:p-8">
            @include('manage.organizations._form')
        </div>

        <div class="panel border-l-4 border-danger p-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="font-display text-ink">Hapus organisasi ini</p>
                <p class="text-sm text-ink-light">Keanggotaannya ikut hilang; karakternya sendiri tetap aman.</p>
            </div>
            <form method="POST" action="{{ route('organizations.destroy', [$world, $organization]) }}"
                  onsubmit="return confirm('Hapus organisasi “{{ $organization->name }}”?');">
                @csrf
                @method('DELETE')
                <x-danger-button>Hapus Organisasi</x-danger-button>
            </form>
        </div>
    </div>
</x-app-layout>
