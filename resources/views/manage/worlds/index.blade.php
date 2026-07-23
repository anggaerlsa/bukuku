<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-display text-2xl text-ink">@can('manage worlds') Semua Dunia @else Dunia Saya @endcan</h1>
                <p class="text-sm text-ink-light">Universe tempatmu membangun cerita.</p>
            </div>
            @can('create worlds')
                <a href="{{ route('worlds.create') }}" class="btn-primary">✚ Dunia Baru</a>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <form method="GET" action="{{ route('worlds.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[12rem]">
                <x-input-label for="q" value="Cari dunia" />
                <x-text-input id="q" name="q" type="text" class="mt-1" :value="$search" placeholder="Nama dunia…" />
            </div>
            <button class="btn-outline">Cari</button>
            @if ($search)<a href="{{ route('worlds.index') }}" class="btn-outline">Reset</a>@endif
        </form>

        @if ($worlds->isEmpty())
            <div class="panel p-12 text-center">
                <p class="font-display text-xl text-ink">Tak ada dunia ditemukan.</p>
                @can('create worlds')
                    <a href="{{ route('worlds.create') }}" class="btn-primary mt-4">✚ Buat Dunia Baru</a>
                @endcan
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($worlds as $world)
                    <x-world-card :world="$world" />
                @endforeach
            </div>
            <div>{{ $worlds->links() }}</div>
        @endif
    </div>
</x-app-layout>
