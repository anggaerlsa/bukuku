<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('worlds.show', $world) }}" class="text-sm text-ink hover:text-accent-dark">← {{ $world->name }}</a>
        <div class="flex flex-wrap items-center justify-between gap-3 mt-1">
            <h1 class="font-display text-2xl text-ink">📖 Lore</h1>
            @can('update', $world)
                <a href="{{ route('lore.create', $world) }}" class="btn-primary">✚ Artikel Baru</a>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <form method="GET" action="{{ route('lore.index', $world) }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[12rem]">
                <x-input-label for="q" value="Cari" />
                <x-text-input id="q" name="q" type="text" class="mt-1" :value="$search" placeholder="Judul, ringkasan, atau isi…" />
            </div>
            @if ($categories)
                <div>
                    <x-input-label for="kategori" value="Kategori" />
                    <select id="kategori" name="kategori" class="select mt-1">
                        <option value="">Semua</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c }}" @selected($category === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <button class="btn-outline">Saring</button>
            @if ($search || $category)<a href="{{ route('lore.index', $world) }}" class="btn-outline">Reset</a>@endif
        </form>

        @if ($total === 0)
            <div class="panel p-12 text-center space-y-3">
                <p class="font-display text-xl text-ink">Belum ada artikel lore.</p>
                <p class="text-ink-light">
                    Tempat untuk apa pun yang bukan orang, bukan tempat, dan bukan kelompok —
                    aturan sihir, panteon, glosarium, teknologi, doktrin.
                </p>
                <div class="pt-2">
                    <p class="text-xs text-ink-light mb-2">Kategori yang cocok untuk tema dunia ini:</p>
                    <div class="flex flex-wrap justify-center gap-1.5">
                        @foreach ($suggestions as $s)
                            @can('update', $world)
                                <a href="{{ route('lore.create', $world) }}?kategori={{ urlencode($s) }}" class="badge-accent hover:border-accent">{{ $s }}</a>
                            @else
                                <span class="badge-muted">{{ $s }}</span>
                            @endcan
                        @endforeach
                    </div>
                    <p class="text-xs text-ink-light mt-2">Cuma saran — kategori bisa kamu tulis sendiri sebebasnya.</p>
                </div>
                @can('update', $world)
                    <a href="{{ route('lore.create', $world) }}" class="btn-primary mt-2">✚ Artikel Pertama</a>
                @endcan
            </div>
        @elseif ($entries->isEmpty())
            <div class="panel p-12 text-center">
                <p class="font-display text-xl text-ink">Tak ada artikel yang cocok.</p>
                <a href="{{ route('lore.index', $world) }}" class="btn-outline mt-4">Tampilkan semua</a>
            </div>
        @else
            @foreach ($entries as $groupName => $group)
                <section>
                    <div class="flex flex-wrap items-center gap-3 mb-3">
                        <h2 class="font-display text-xl text-ink">{{ $groupName }}</h2>
                        <span class="badge-accent">{{ $group->count() }}</span>
                        <span class="h-px flex-1 bg-shell/20"></span>
                        @can('update', $world)
                            <a href="{{ route('lore.create', $world) }}?kategori={{ urlencode($group->first()->category ?? '') }}"
                               class="text-xs text-ink-light hover:text-accent-dark shrink-0">✚ di kategori ini</a>
                        @endcan
                    </div>
                    <div class="panel divide-y divide-line/20 overflow-hidden">
                        @foreach ($group as $entry)
                            <a href="{{ route('lore.show', [$world, $entry]) }}" class="flex items-start gap-3 p-4 hover:bg-surface-muted transition">
                                <span class="text-xl shrink-0">📄</span>
                                <span class="min-w-0">
                                    <span class="block font-display text-ink">{{ $entry->title }}</span>
                                    @if ($entry->summary)
                                        <span class="block text-sm text-ink-light line-clamp-2">{{ $entry->summary }}</span>
                                    @endif
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif
    </div>
</x-app-layout>
