{{--
    Bagian komentar yang dipakai bersama halaman Buku dan halaman Bab.
    Parameter:
      $commentable  — Book atau Chapter yang dikomentari
      $storeUrl     — action form kirim komentar (route yang sesuai)
      $comments     — komentar utama beserta balasannya (sudah dimuat)
      $ownerId      — user_id pemilik novel, untuk lencana "Penulis"
      $placeholder  — teks placeholder textarea (opsional)
--}}
@php($placeholder = $placeholder ?? 'Tulis komentar…')
{{-- Hitung izin sekali di sini, lalu turunkan — jangan panggil ulang policy
     (yang menyentuh $comment->commentable) per komentar. --}}
@php($canComment = request()->user()?->can('create', [\App\Models\Comment::class, $commentable]) ?? false)

<section>
    <div class="flex flex-wrap items-center gap-3 mb-4">
        <h2 class="font-display text-xl text-ink">💬 Komentar</h2>
        <span class="badge-muted">{{ $commentable->comments()->count() }}</span>
    </div>

    @if ($canComment)
        <form method="POST" action="{{ $storeUrl }}" class="panel p-4 mb-6">
            @csrf
            <textarea name="body" rows="3" required
                      placeholder="{{ $placeholder }}"
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
    @endif

    @forelse ($comments as $comment)
        <div class="panel p-4 sm:p-5 mb-3">
            @include('manage.comments._comment', [
                'comment' => $comment,
                'storeUrl' => $storeUrl,
                'ownerId' => $ownerId,
                'canComment' => $canComment,
                'isReply' => false,
            ])
        </div>
    @empty
        <div class="panel p-10 text-center text-ink-light">Belum ada komentar. Jadilah yang pertama.</div>
    @endforelse
</section>
