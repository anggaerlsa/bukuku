<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('novels.index') }}" class="text-sm text-ink hover:text-accent-dark">← Semua Novel</a>
            @can('update', $novel)
                <div class="flex flex-wrap items-center gap-2">
                    <form method="POST" action="{{ route('novels.share', $novel) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="share" value="{{ $novel->is_shared ? 0 : 1 }}">
                        <button class="{{ $novel->is_shared ? 'btn-outline' : 'btn-primary' }} btn-sm"
                                title="{{ $novel->is_shared ? 'Hentikan berbagi' : 'Izinkan semua member membaca novel ini' }}">
                            {{ $novel->is_shared ? '🔒 Jadikan Privat' : '🔗 Bagikan ke Member' }}
                        </button>
                    </form>
                    <a href="{{ route('novels.edit', $novel) }}" class="btn-outline btn-sm">Sunting Novel</a>
                </div>
            @endcan
        </div>
    </x-slot>

    @php($statusBadge = ['active' => 'badge-success', 'concept' => 'badge-accent', 'archived' => 'badge-muted'][$novel->status] ?? 'badge-muted')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        {{-- Sharing state: one line for the owner, one for a visiting member --}}
        @can('update', $novel)
            @if ($novel->is_shared)
                <div class="panel border-l-4 border-success px-4 py-3 text-sm text-ink">
                    🔗 <strong>Dibagikan.</strong> Semua member yang login bisa membaca novel ini beserta dunia,
                    lokasi, dan karakternya — <strong>hanya lihat</strong>, tidak bisa menyunting atau menghapus apa pun.
                    @if ($novel->shared_at)<span class="text-ink-light">Sejak {{ $novel->shared_at->format('d M Y') }}.</span>@endif
                </div>
            @endif
        @else
            <div class="panel border-l-4 border-accent px-4 py-3 text-sm text-ink">
                👁️ <strong>Mode baca.</strong> Novel ini dibagikan oleh
                <strong>{{ $novel->user?->name ?? 'penulis lain' }}</strong> sebagai referensi. Kamu bisa menjelajahi
                dunia, lokasi, dan karakternya, tapi tidak bisa mengubah apa pun.
            </div>
        @endcan

        {{-- Novel header --}}
        <div class="grid sm:grid-cols-[10rem_1fr] gap-6">
            <div class="panel overflow-hidden">
                <img src="{{ $novel->coverUrl() }}" alt="Sampul {{ $novel->title }}" class="w-full aspect-[5/7] object-cover">
            </div>
            <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="{{ $statusBadge }}">{{ $novel->statusLabel() }}</span>
                    @if ($novel->is_shared)<span class="badge-success">🔗 Dibagikan</span>@endif
                    <span class="badge-muted">🎨 {{ $novel->themeLabel() }}</span>
                    @foreach ($novel->genres as $genre)
                        <span class="badge-accent">{{ $genre->name }}</span>
                    @endforeach
                    <span class="text-xs text-ink-light font-display uppercase tracking-wider">Novel</span>
                </div>
                <h1 class="font-display text-3xl sm:text-4xl text-ink leading-tight">{{ $novel->title }}</h1>
                @if ($novel->tagline)
                    <p class="text-lg text-ink-light italic">{{ $novel->tagline }}</p>
                @endif
                <p class="text-xs text-ink-light font-display uppercase tracking-wider">
                    Ditulis oleh {{ $novel->user?->name ?? 'sistem' }} ·
                    {{ $worlds->count() }} dunia · {{ $novel->charactersCount() }} karakter
                </p>
                @if ($novel->synopsis)
                    <div class="panel p-5">
                        <h2 class="label">Sinopsis</h2>
                        <p class="mt-1 text-ink leading-relaxed whitespace-pre-line">{{ $novel->synopsis }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Daftar isi: buku novel ini beserta babnya, dalam urutan baca --}}
        <section>
            <div class="flex flex-wrap items-center gap-3 mb-5">
                <h2 class="font-display text-2xl text-ink">📖 Buku</h2>
                <span class="badge-accent">{{ $books->count() }}</span>
                @if ($chaptersTotal > 0)
                    <span class="text-sm text-ink-light">{{ $chaptersTotal }} bab · {{ number_format($wordsTotal) }} kata</span>
                @endif
                <span class="h-px flex-1 bg-shell/20"></span>
                @can('update', $novel)
                    <a href="{{ route('books.create') }}?novel={{ $novel->id }}" class="btn-primary btn-sm shrink-0">✚ Buku</a>
                @endcan
            </div>

            @if ($books->isEmpty())
                <div class="panel p-12 text-center">
                    <p class="font-display text-xl text-ink">Novel ini belum punya buku.</p>
                    <p class="text-ink-light mt-1">
                        Buku adalah jilid tempat bab-babnya dibaca berurutan — satu novel bisa terbagi jadi beberapa jilid.
                    </p>
                    @can('update', $novel)
                        <a href="{{ route('books.create') }}?novel={{ $novel->id }}" class="btn-primary mt-4">✚ Buku Pertama</a>
                    @endcan
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($books as $book)
                        <div class="panel overflow-hidden" x-data="{ buka: {{ $books->count() === 1 ? 'true' : 'false' }} }">
                            <div class="flex flex-wrap items-center gap-3 px-5 py-4">
                                <a href="{{ route('books.show', $book) }}" class="font-display text-lg text-ink hover:text-accent-dark">
                                    {{ $book->title }}
                                </a>
                                <span class="badge-muted">{{ $book->statusLabel() }}</span>
                                <span class="text-sm text-ink-light">{{ $book->chapters_count }} bab</span>
                                <span class="h-px flex-1 bg-shell/20"></span>
                                @if ($book->chapters_count > 0)
                                    <button type="button" @click="buka = !buka" class="btn-ghost btn-sm">
                                        <span x-show="!buka">Lihat daftar isi</span>
                                        <span x-show="buka" x-cloak>Sembunyikan</span>
                                    </button>
                                @endif
                                @can('update', $novel)
                                    <a href="{{ route('chapters.create', $book) }}" class="btn-outline btn-sm">✚ Bab</a>
                                @endcan
                            </div>

                            @if ($book->chapters_count > 0)
                                <ol x-show="buka" x-cloak class="divide-y divide-line/30 border-t border-line/30">
                                    @foreach ($book->chapters as $chapter)
                                        <li>
                                            <a href="{{ route('chapters.show', [$book, $chapter]) }}"
                                               class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-5 py-3 hover:bg-accent-soft">
                                                <span class="text-xs text-ink-faint tabular-nums w-8 shrink-0">{{ $chapter->position }}</span>
                                                <span class="text-ink flex-1 min-w-0">{{ $chapter->title }}</span>
                                                <span class="text-xs text-ink-light shrink-0">
                                                    {{ number_format($chapter->word_count) }} kata · {{ $chapter->readingMinutes() }} mnt
                                                </span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Worlds belonging to this novel --}}
        <section>
            <div class="flex flex-wrap items-center gap-3 mb-5">
                <h2 class="font-display text-2xl text-ink">🌍 Dunia</h2>
                <span class="badge-accent">{{ $worlds->count() }}</span>
                <span class="h-px flex-1 bg-shell/20"></span>
                {{-- Only the owner may add worlds here; a visiting member reads only. --}}
                @can('update', $novel)
                    @can('create worlds')
                        <a href="{{ route('worlds.create') }}?novel={{ $novel->id }}" class="btn-primary btn-sm shrink-0">✚ Dunia</a>
                    @endcan
                @endcan
            </div>

            @if ($worlds->isEmpty())
                <div class="panel p-12 text-center">
                    <p class="font-display text-xl text-ink">Novel ini belum punya dunia.</p>
                    <p class="text-ink-light mt-1">
                        Satu novel bisa menaungi banyak dunia — misalnya tiap planet, kota, atau dimensi yang disinggahi ceritanya.
                    </p>
                    @can('update', $novel)
                        @can('create worlds')
                            <a href="{{ route('worlds.create') }}?novel={{ $novel->id }}" class="btn-primary mt-4">✚ Dunia Pertama</a>
                        @endcan
                    @endcan
                </div>
            @else
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($worlds as $world)
                        <x-world-card :world="$world" :show-novel="false" />
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
