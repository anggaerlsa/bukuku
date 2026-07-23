<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('worlds.show', $world) }}" class="text-sm text-ink hover:text-accent-dark">← {{ $world->name }}</a>
        <div class="flex flex-wrap items-center justify-between gap-3 mt-1">
            <h1 class="font-display text-2xl text-ink">🗺️ Lokasi</h1>
            @can('update', $world)
                <a href="{{ route('locations.create', $world) }}" class="btn-primary">✚ Tambah Benua</a>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-4">
        @if (! $hasLocations)
            <div class="panel p-12 text-center">
                <p class="font-display text-xl text-ink">Peta dunia ini masih kosong.</p>
                <p class="text-ink-light mt-1">Mulai dari tingkat teratas: sebuah <strong>Benua</strong>.</p>
                @can('update', $world)
                    <a href="{{ route('locations.create', $world) }}" class="btn-primary mt-4">✚ Tambah Benua</a>
                @endcan
            </div>
        @else
            {{-- Search across all five tier tables; the tree below is pruned, never replaced. --}}
            <form method="GET" action="{{ route('locations.index', $world) }}" class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[12rem]">
                    <x-input-label for="q" value="Cari lokasi" />
                    <x-text-input id="q" name="q" type="text" class="mt-1" :value="$filter->term()"
                                  placeholder="Nama, sebutan, atau ringkasan…" />
                </div>
                <div>
                    <x-input-label for="tier" value="Tingkat" />
                    <select id="tier" name="tier" class="select mt-1">
                        <option value="">Semua tingkat</option>
                        @foreach (\App\Support\Hierarchy::labels() as $key => $label)
                            <option value="{{ $key }}" @selected($filter->tier() === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn-outline">Saring</button>
                @if ($filter->active())
                    <a href="{{ route('locations.index', $world) }}" class="btn-outline">Reset</a>
                @endif
            </form>

            @if ($filter->active())
                <p class="text-sm text-ink-light">
                    <span class="badge-accent">{{ $filter->matchCount() }}</span>
                    lokasi cocok
                    @if ($filter->term())untuk “<strong class="text-ink">{{ $filter->term() }}</strong>”@endif
                    @if ($filter->tier())di tingkat <strong class="text-ink">{{ \App\Support\Hierarchy::label($filter->tier()) }}</strong>@endif
                    — induknya tetap ditampilkan agar hirarki terbaca.
                </p>
            @endif

            @if ($benuas->isEmpty())
                <div class="panel p-12 text-center">
                    <p class="font-display text-xl text-ink">Tidak ada lokasi yang cocok.</p>
                    <p class="text-ink-light mt-1">Coba kata kunci lain atau ubah tingkatnya.</p>
                    <a href="{{ route('locations.index', $world) }}" class="btn-outline mt-4">Tampilkan semua</a>
                </div>
            @else
                <div class="panel overflow-hidden">
                    @foreach ($benuas as $benua)
                        <x-location-tree-node :world="$world" :node="$benua" :filter="$filter" />
                    @endforeach
                </div>
                <p class="text-xs text-ink-light">
                    Hirarki: <span class="font-display">Benua › Negara › Provinsi › Kota › Desa</span> — tiap tingkat tabel DB terpisah.
                    Pakai <span class="badge-muted !py-0">✚</span> pada tiap baris untuk menambah sub-lokasi.
                </p>
            @endif
        @endif
    </div>
</x-app-layout>
