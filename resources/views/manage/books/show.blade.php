<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('novels.show', $book->novel) }}" class="text-sm text-ink hover:text-accent-dark">← {{ $book->novel->title }}</a>
        <div class="flex flex-wrap items-center justify-between gap-3 mt-1">
            <h1 class="font-display text-2xl text-ink">{{ $book->title }}</h1>
            @can('update', $book->novel)
                <div class="flex items-center gap-2">
                    <a href="{{ route('chapters.create', $book) }}" class="btn-primary btn-sm">✚ Bab</a>
                    <a href="{{ route('books.edit', $book) }}" class="btn-outline btn-sm">Sunting</a>
                    <form method="POST" action="{{ route('books.destroy', $book) }}"
                          onsubmit="return confirm('Hapus buku “{{ $book->title }}”?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn-danger btn-sm">Hapus</button>
                    </form>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        @if (session('error'))
            <div class="panel border-l-4 border-danger px-4 py-3 text-sm text-ink">{{ session('error') }}</div>
        @endif

        <div class="flex flex-wrap items-center gap-2">
            <span class="badge-muted">{{ $book->statusLabel() }}</span>
            <span class="text-sm text-ink-light">
                {{ $chapters->count() }} bab · {{ number_format($chapters->sum('word_count')) }} kata
            </span>
        </div>

        @if ($book->synopsis)
            <div class="panel p-5">
                <p class="text-ink leading-relaxed whitespace-pre-line">{{ $book->synopsis }}</p>
            </div>
        @endif

        <section>
            <h2 class="font-display text-xl text-ink mb-4">Daftar Isi</h2>

            @if ($chapters->isEmpty())
                <div class="panel p-12 text-center">
                    <p class="font-display text-xl text-ink">Belum ada bab.</p>
                    <p class="text-ink-light mt-1">Bab adalah episode yang dibaca berurutan di dalam buku ini.</p>
                    @can('update', $book->novel)
                        <a href="{{ route('chapters.create', $book) }}" class="btn-primary mt-4">✚ Bab Pertama</a>
                    @endcan
                </div>
            @else
                <ol class="panel divide-y divide-line/30 overflow-hidden">
                    @foreach ($chapters as $chapter)
                        <li>
                            <a href="{{ route('chapters.show', [$book, $chapter]) }}"
                               class="flex flex-wrap items-baseline gap-x-3 gap-y-1 px-5 py-3 hover:bg-accent-soft">
                                <span class="text-xs text-ink-faint tabular-nums w-8 shrink-0">{{ $chapter->position }}</span>
                                <span class="text-ink flex-1 min-w-0">{{ $chapter->title }}</span>
                                <span class="text-xs text-ink-light shrink-0">
                                    {{ number_format($chapter->word_count) }} kata · {{ $chapter->readingMinutes() }} mnt
                                    @if ($chapter->published_at)
                                        · {{ $chapter->published_at->format('d M Y') }}
                                    @endif
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        {{-- Komentar buku, dengan satu tingkat balasan --}}
        <section>
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <h2 class="font-display text-xl text-ink">💬 Komentar</h2>
                <span class="badge-muted">{{ $book->comments()->count() }}</span>
            </div>

            @can('create', [\App\Models\Comment::class, $book])
                <form method="POST" action="{{ route('comments.store', $book) }}" class="panel p-4 mb-6">
                    @csrf
                    <textarea name="body" rows="3" required
                              placeholder="Tulis komentar tentang buku ini…"
                              class="w-full rounded-lg border-line bg-surface text-ink focus:border-accent focus:ring-accent">{{ old('body') }}</textarea>
                    <x-input-error :messages="$errors->get('body')" class="mt-1" />
                    <div class="flex justify-end mt-2">
                        <x-primary-button>Kirim Komentar</x-primary-button>
                    </div>
                </form>
            @else
                <div class="panel border-l-4 border-accent px-4 py-3 text-sm text-ink-light mb-6">
                    Bergabunglah membaca lewat novel yang dibagikan untuk ikut berkomentar.
                </div>
            @endcan

            @forelse ($comments as $comment)
                <div class="panel p-4 sm:p-5 mb-3">
                    @include('manage.comments._comment', ['comment' => $comment, 'book' => $book, 'isReply' => false])
                </div>
            @empty
                <div class="panel p-10 text-center text-ink-light">Belum ada komentar. Jadilah yang pertama.</div>
            @endforelse
        </section>
    </div>
</x-app-layout>
