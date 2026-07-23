<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('worlds.show', $world) }}" class="text-sm text-ink hover:text-accent-dark">← {{ $world->name }}</a>
        <div class="flex flex-wrap items-center justify-between gap-3 mt-1">
            <h1 class="font-display text-2xl text-ink">👤 Karakter</h1>
            @can('update', $world)
                <a href="{{ route('characters.create', $world) }}" class="btn-primary">✚ Karakter Baru</a>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <form method="GET" action="{{ route('characters.index', $world) }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[12rem]">
                <x-input-label for="q" value="Cari" />
                <x-text-input id="q" name="q" type="text" class="mt-1" :value="$search" placeholder="Nama atau julukan…" />
            </div>
            <div>
                <x-input-label for="role" value="Peran" />
                <select id="role" name="role" class="select mt-1">
                    <option value="">Semua</option>
                    @foreach (\App\Models\Character::roles() as $key => $label)
                        <option value="{{ $key }}" @selected($role === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn-outline">Saring</button>
            @if ($search || $role)<a href="{{ route('characters.index', $world) }}" class="btn-outline">Reset</a>@endif
        </form>

        @if ($characters->isEmpty())
            <div class="panel p-12 text-center">
                <p class="font-display text-xl text-ink">Belum ada karakter.</p>
                @can('update', $world)
                    <a href="{{ route('characters.create', $world) }}" class="btn-primary mt-4">✚ Tambah Karakter</a>
                @endcan
            </div>
        @else
            <div class="grid gap-4 grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6">
                @foreach ($characters as $character)
                    <x-character-card :world="$world" :character="$character" />
                @endforeach
            </div>
            <div>{{ $characters->links() }}</div>
        @endif
    </div>
</x-app-layout>
