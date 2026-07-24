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
