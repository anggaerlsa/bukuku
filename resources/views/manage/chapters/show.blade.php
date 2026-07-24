<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('books.show', $book) }}" class="text-sm text-ink hover:text-accent-dark">← Daftar Isi · {{ $book->title }}</a>
        <div class="flex flex-wrap items-center justify-between gap-3 mt-1">
            <h1 class="font-display text-2xl text-ink">{{ $chapter->title }}</h1>
            @can('update', $book->novel)
                <div class="flex items-center gap-2">
                    <a href="{{ route('chapters.edit', [$book, $chapter]) }}" class="btn-outline btn-sm">Sunting</a>
                    <form method="POST" action="{{ route('chapters.destroy', [$book, $chapter]) }}"
                          onsubmit="return confirm('Hapus bab “{{ $chapter->title }}”?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn-danger btn-sm">Hapus</button>
                    </form>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-ink-light">
            <span>Bab {{ $chapter->position }}</span>
            <span>·</span>
            <span>{{ number_format($chapter->word_count) }} kata</span>
            <span>·</span>
            <span>{{ $chapter->readingMinutes() }} menit baca</span>
            @if ($chapter->published_at)
                <span>·</span>
                <span>Tayang {{ $chapter->published_at->format('d M Y') }}</span>
            @endif
        </div>

        {{--
            Naskah dibaca, bukan dipindai: kolom sempit, jarak baris lega, dan
            ukuran huruf sedikit lebih besar daripada halaman lore.
        --}}
        @if (filled($chapter->body))
            <article class="panel p-6 sm:p-10">
                <div class="text-ink text-[1.0625rem] leading-8 whitespace-pre-line">{{ $chapter->body }}</div>
            </article>
        @else
            <div class="panel p-12 text-center text-ink-light">Bab ini belum ada isinya.</div>
        @endif

        @if ($chapter->source_url)
            <p class="text-xs text-ink-faint text-center">
                Sumber asli: <a href="{{ $chapter->source_url }}" rel="noopener noreferrer" target="_blank"
                                class="underline hover:text-accent-dark">{{ $chapter->source_url }}</a>
            </p>
        @endif

        {{-- Navigasi baca: bab sebelum & sesudah di buku yang sama --}}
        <nav class="flex items-stretch justify-between gap-3">
            @if ($previous)
                <a href="{{ route('chapters.show', [$book, $previous]) }}" class="lore-card p-4 flex-1 min-w-0">
                    <span class="text-xs text-ink-faint">← Sebelumnya</span>
                    <span class="block font-display text-ink truncate">{{ $previous->title }}</span>
                </a>
            @else
                <span class="flex-1"></span>
            @endif

            @if ($next)
                <a href="{{ route('chapters.show', [$book, $next]) }}" class="lore-card p-4 flex-1 min-w-0 text-right">
                    <span class="text-xs text-ink-faint">Selanjutnya →</span>
                    <span class="block font-display text-ink truncate">{{ $next->title }}</span>
                </a>
            @else
                <span class="flex-1"></span>
            @endif
        </nav>
    </div>
</x-app-layout>
