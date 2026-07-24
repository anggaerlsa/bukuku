<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('worlds.show', $world) }}" class="text-sm text-ink hover:text-accent-dark">← {{ $world->name }}</a>
        <div class="flex flex-wrap items-center justify-between gap-3 mt-1">
            <h1 class="font-display text-2xl text-ink">🛡️ Organisasi</h1>
            @can('update', $world)
                <a href="{{ route('organizations.create', $world) }}" class="btn-primary">✚ Organisasi Baru</a>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <form method="GET" action="{{ route('organizations.index', $world) }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[12rem]">
                <x-input-label for="q" value="Cari" />
                <x-text-input id="q" name="q" type="text" class="mt-1" :value="$search" placeholder="Nama, julukan, atau sebutan…" />
            </div>
            <div>
                <x-input-label for="status" value="Status" />
                <select id="status" name="status" class="select mt-1">
                    <option value="">Semua</option>
                    @foreach (\App\Models\Organization::statuses() as $key => $label)
                        <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn-outline">Saring</button>
            @if ($search || $status)<a href="{{ route('organizations.index', $world) }}" class="btn-outline">Reset</a>@endif
        </form>

        @if ($organizations->isEmpty())
            <div class="panel p-12 text-center">
                <p class="font-display text-xl text-ink">
                    {{ $search || $status ? 'Tak ada organisasi yang cocok.' : 'Belum ada organisasi.' }}
                </p>
                <p class="text-ink-light mt-1">
                    Wangsa, pasukan, sekte, guild, ordo — kelompok tempat para karakter bernaung.
                </p>
                @can('update', $world)
                    <a href="{{ route('organizations.create', $world) }}" class="btn-primary mt-4">✚ Organisasi Pertama</a>
                @endcan
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($organizations as $organization)
                    <x-organization-card :world="$world" :organization="$organization" />
                @endforeach
            </div>
            <div>{{ $organizations->links() }}</div>
        @endif
    </div>
</x-app-layout>
