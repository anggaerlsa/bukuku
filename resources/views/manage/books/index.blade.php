<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="font-display text-2xl text-ink">📖 Buku</h1>
            @can('create novels')
                <a href="{{ route('books.create') }}" class="btn-primary btn-sm">✚ Buku</a>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <form method="GET" class="panel p-4 flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-48">
                <x-input-label for="q" value="Cari judul" />
                <x-text-input id="q" name="q" type="search" class="mt-1" :value="$search" placeholder="cth. Jilid 1" />
            </div>
            <div class="min-w-48">
                <x-input-label for="novel" value="Novel" />
                <select id="novel" name="novel"
                        class="mt-1 w-full rounded-lg border-line bg-surface text-ink focus:border-accent focus:ring-accent">
                    <option value="">Semua novel</option>
                    @foreach ($novels as $n)
                        <option value="{{ $n->id }}" @selected((int) $novelId === $n->id)>{{ $n->title }}</option>
                    @endforeach
                </select>
            </div>
            <x-primary-button>Saring</x-primary-button>
        </form>

        @if ($books->isEmpty())
            <div class="panel p-12 text-center">
                <p class="font-display text-xl text-ink">Belum ada buku.</p>
                <p class="text-ink-light mt-1">Buku adalah jilid tempat bab-bab novelmu dibaca berurutan.</p>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($books as $book)
                    <a href="{{ route('books.show', $book) }}" class="lore-card p-5 block">
                        <p class="font-display text-lg text-ink">{{ $book->title }}</p>
                        <p class="text-xs text-ink-light mt-1">{{ $book->novel->title }}</p>
                        <div class="flex flex-wrap items-center gap-2 mt-3">
                            <span class="badge-muted">{{ $book->statusLabel() }}</span>
                            <span class="text-xs text-ink-light">{{ $book->chapters_count }} bab</span>
                        </div>
                    </a>
                @endforeach
            </div>

            <div>{{ $books->links() }}</div>
        @endif
    </div>
</x-app-layout>
