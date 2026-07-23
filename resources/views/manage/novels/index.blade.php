<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-display text-2xl text-ink">@can('manage novels') Semua Novel @else Novel Saya @endcan</h1>
                <p class="text-sm text-ink-light">Tiap novel menaungi dunia-dunia tempat ceritanya berlangsung.</p>
            </div>
            @can('create novels')
                <a href="{{ route('novels.create') }}" class="btn-primary">✚ Novel Baru</a>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <form method="GET" action="{{ route('novels.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[12rem]">
                <x-input-label for="q" value="Cari novel" />
                <x-text-input id="q" name="q" type="text" class="mt-1" :value="$search" placeholder="Judul atau tagline…" />
            </div>
            <div>
                <x-input-label for="scope" value="Tampilkan" />
                <select id="scope" name="scope" class="select mt-1">
                    <option value="">Semua</option>
                    <option value="milik" @selected($scope === 'milik')>Milik saya</option>
                    <option value="dibagikan" @selected($scope === 'dibagikan')>Dibagikan penulis lain{{ $sharedCount ? " ({$sharedCount})" : '' }}</option>
                </select>
            </div>
            <button class="btn-outline">Cari</button>
            @if ($search || $scope)<a href="{{ route('novels.index') }}" class="btn-outline">Reset</a>@endif
        </form>

        @if ($scope === 'dibagikan')
            <p class="text-sm text-ink-light">
                Novel yang dibagikan penulis lain sebagai referensi — kamu bisa menjelajahi dunia, lokasi,
                dan karakternya, tapi <strong>hanya lihat</strong>.
            </p>
        @endif

        @if ($novels->isEmpty())
            <div class="panel p-12 text-center">
                <p class="font-display text-xl text-ink">
                    {{ $search ? 'Tak ada novel yang cocok.' : 'Belum ada novel.' }}
                </p>
                <p class="text-ink-light mt-1">Mulai dari judulnya — dunianya menyusul.</p>
                @can('create novels')
                    <a href="{{ route('novels.create') }}" class="btn-primary mt-4">✚ Novel Pertama</a>
                @endcan
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($novels as $novel)
                    <x-novel-card :novel="$novel" />
                @endforeach
            </div>
            <div>{{ $novels->links() }}</div>
        @endif
    </div>
</x-app-layout>
