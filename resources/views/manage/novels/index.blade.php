<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-display text-2xl text-ink">Novel</h1>
                <p class="text-sm text-ink-light">Tiap novel menaungi dunia-dunia tempat ceritanya berlangsung.</p>
            </div>
            @can('create novels')
                <a href="{{ route('novels.create') }}" class="btn-primary">✚ Novel Baru</a>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">
        <form method="GET" action="{{ route('novels.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[12rem]">
                <x-input-label for="q" value="Cari novel" />
                <x-text-input id="q" name="q" type="text" class="mt-1" :value="$search" placeholder="Judul atau tagline…" />
            </div>
            <button class="btn-outline">Cari</button>
            @if ($search)<a href="{{ route('novels.index') }}" class="btn-outline">Reset</a>@endif
        </form>

        {{-- Milik sendiri --}}
        <section>
            <div class="flex flex-wrap items-center gap-3 mb-5">
                <h2 class="font-display text-xl text-ink">📕 Novel Saya</h2>
                <span class="badge-accent">{{ $novels->total() }}</span>
                <span class="h-px flex-1 bg-shell/20"></span>
            </div>

            @if ($novels->isEmpty())
                <div class="panel p-12 text-center">
                    <p class="font-display text-xl text-ink">
                        {{ $search ? 'Tak ada novelmu yang cocok.' : 'Kamu belum punya novel.' }}
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
                <div class="mt-4">{{ $novels->links() }}</div>
            @endif
        </section>

        {{-- Dibagikan penulis lain — baca saja --}}
        <section id="dibagikan" class="scroll-mt-6">
            <div class="flex flex-wrap items-center gap-3 mb-2">
                <h2 class="font-display text-xl text-ink">🔗 Novel Dibagikan</h2>
                <span class="badge-success">{{ $shared->total() }}</span>
                <span class="h-px flex-1 bg-shell/20"></span>
            </div>
            <p class="text-sm text-ink-light mb-5">
                Dibagikan penulis lain sebagai referensi — kamu bisa menjelajahi dunia, lokasi, dan karakternya,
                tapi <strong>hanya lihat</strong>.
            </p>

            @if ($shared->isEmpty())
                <div class="panel p-8 text-center text-ink-light">
                    {{ $search ? 'Tak ada novel dibagikan yang cocok.' : 'Belum ada penulis lain yang membagikan novelnya.' }}
                </div>
            @else
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($shared as $novel)
                        <x-novel-card :novel="$novel" />
                    @endforeach
                </div>
                <div class="mt-4">{{ $shared->links() }}</div>
            @endif
        </section>

        {{-- Sisanya, hanya untuk yang boleh mengelola semua novel --}}
        @if ($others)
            <section>
                <div class="flex flex-wrap items-center gap-3 mb-2">
                    <h2 class="font-display text-xl text-ink">🗂️ Novel Penulis Lain</h2>
                    <span class="badge-muted">{{ $others->total() }}</span>
                    <span class="h-px flex-1 bg-shell/20"></span>
                </div>
                <p class="text-sm text-ink-light mb-5">
                    Novel yang belum dibagikan, terlihat karena kamu punya wewenang mengelola semua novel.
                </p>

                @if ($others->isEmpty())
                    <div class="panel p-8 text-center text-ink-light">Tidak ada.</div>
                @else
                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($others as $novel)
                            <x-novel-card :novel="$novel" />
                        @endforeach
                    </div>
                    <div class="mt-4">{{ $others->links() }}</div>
                @endif
            </section>
        @endif
    </div>
</x-app-layout>
