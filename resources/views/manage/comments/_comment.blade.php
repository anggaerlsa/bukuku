{{--
    Satu komentar. Dipakai untuk komentar utama maupun balasan; $isReply
    menandai balasan (tanpa tombol "Balas", tanpa daftar balasan di bawahnya),
    sebab threadnya hanya satu tingkat.
--}}
@php($isReply = $isReply ?? false)

<div class="flex gap-3" x-data="{ sunting: false, balas: false }">
    <span class="grid place-items-center h-9 w-9 rounded-full bg-surface-sunken text-ink font-display font-bold shrink-0">
        {{ strtoupper(mb_substr($comment->author?->name ?? '?', 0, 1)) }}
    </span>

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
            <span class="font-display text-ink">{{ $comment->author?->name ?? 'Pengguna dihapus' }}</span>
            {{-- $book->novel sudah dimuat di controller; jangan sentuh
                 $comment->book (itu lazy-load per komentar → N+1). --}}
            @if ($book->novel->user_id === $comment->user_id)
                <span class="badge-accent">Penulis</span>
            @endif
            <span class="text-xs text-ink-faint">{{ $comment->created_at->diffForHumans() }}</span>
            @if ($comment->wasEdited())
                <span class="text-xs text-ink-faint italic">· diedit</span>
            @endif
        </div>

        {{-- Tampilan biasa --}}
        <div x-show="!sunting">
            <p class="text-ink leading-relaxed whitespace-pre-line break-words mt-1">{{ $comment->body }}</p>

            <div class="flex items-center gap-3 mt-1.5 text-xs">
                @unless ($isReply)
                    @can('create', [\App\Models\Comment::class, $book])
                        <button type="button" @click="balas = !balas" class="text-ink-light hover:text-accent-dark">Balas</button>
                    @endcan
                @endunless
                @can('update', $comment)
                    <button type="button" @click="sunting = true" class="text-ink-light hover:text-accent-dark">Sunting</button>
                @endcan
                @can('delete', $comment)
                    <form method="POST" action="{{ route('comments.destroy', $comment) }}"
                          onsubmit="return confirm('{{ $isReply ? 'Hapus balasan ini?' : 'Hapus komentar ini beserta semua balasannya?' }}');">
                        @csrf
                        @method('DELETE')
                        <button class="text-ink-light hover:text-danger">Hapus</button>
                    </form>
                @endcan
            </div>
        </div>

        {{-- Form sunting sebaris --}}
        @can('update', $comment)
            <form method="POST" action="{{ route('comments.update', $comment) }}" x-show="sunting" x-cloak class="mt-1" @submit="sunting = false">
                @csrf
                @method('PATCH')
                <textarea name="body" rows="3" required
                          class="w-full rounded-lg border-line bg-surface text-ink text-sm focus:border-accent focus:ring-accent">{{ $comment->body }}</textarea>
                <div class="flex items-center gap-2 mt-1.5">
                    <x-primary-button class="!py-1.5 text-xs">Simpan</x-primary-button>
                    <button type="button" @click="sunting = false" class="btn-ghost btn-sm">Batal</button>
                </div>
            </form>
        @endcan

        {{-- Form balas (hanya di komentar utama) --}}
        @unless ($isReply)
            @can('create', [\App\Models\Comment::class, $book])
                <form method="POST" action="{{ route('comments.store', $book) }}" x-show="balas" x-cloak class="mt-3" @submit="balas = false">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                    <textarea name="body" rows="2" required placeholder="Tulis balasan…"
                              class="w-full rounded-lg border-line bg-surface text-ink text-sm focus:border-accent focus:ring-accent"></textarea>
                    <div class="flex items-center gap-2 mt-1.5">
                        <x-primary-button class="!py-1.5 text-xs">Kirim Balasan</x-primary-button>
                        <button type="button" @click="balas = false" class="btn-ghost btn-sm">Batal</button>
                    </div>
                </form>
            @endcan

            {{-- Balasan, menjorok sedikit --}}
            @if ($comment->replies->isNotEmpty())
                <div class="mt-4 space-y-4 border-l-2 border-line/40 pl-4">
                    @foreach ($comment->replies as $reply)
                        @include('manage.comments._comment', ['comment' => $reply, 'book' => $book, 'isReply' => true])
                    @endforeach
                </div>
            @endif
        @endunless
    </div>
</div>
