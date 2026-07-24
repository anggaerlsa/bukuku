@php($editing = $chapter->exists)

<form method="POST"
      action="{{ $editing ? route('chapters.update', [$book, $chapter]) : route('chapters.store', $book) }}"
      class="space-y-6">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="grid sm:grid-cols-[1fr_7rem] gap-5">
        <div>
            <x-input-label for="title" value="Judul Bab" />
            <x-text-input id="title" name="title" type="text" class="mt-1" :value="old('title', $chapter->title)"
                          required autofocus placeholder="cth. Chap 1: Dunia Baru" />
            <x-input-error :messages="$errors->get('title')" />
        </div>
        <div>
            <x-input-label for="position" value="Urutan" />
            <x-text-input id="position" name="position" type="number" min="1" class="mt-1"
                          :value="old('position', $chapter->position)" />
            <p class="text-xs text-ink-light mt-1">Urutan baca.</p>
            <x-input-error :messages="$errors->get('position')" />
        </div>
    </div>

    <div>
        <x-input-label for="body" value="Isi Bab" />
        <textarea id="body" name="body" rows="24"
                  class="mt-1 w-full rounded-lg border-line bg-surface text-ink leading-8 focus:border-accent focus:ring-accent"
                  placeholder="Tulis naskahnya di sini…">{{ old('body', $chapter->body) }}</textarea>
        <p class="text-xs text-ink-light mt-1">Jumlah kata dihitung otomatis saat disimpan.</p>
        <x-input-error :messages="$errors->get('body')" />
    </div>

    <div class="grid sm:grid-cols-2 gap-5">
        <div>
            <x-input-label for="published_at" value="Tanggal tayang" />
            <x-text-input id="published_at" name="published_at" type="date" class="mt-1"
                          :value="old('published_at', $chapter->published_at?->format('Y-m-d'))" />
            <p class="text-xs text-ink-light mt-1">Boleh dikosongkan.</p>
            <x-input-error :messages="$errors->get('published_at')" />
        </div>
        <div>
            <x-input-label for="source_url" value="Tautan sumber" />
            <x-text-input id="source_url" name="source_url" type="url" class="mt-1"
                          :value="old('source_url', $chapter->source_url)" placeholder="https://…" />
            <p class="text-xs text-ink-light mt-1">Kalau bab ini pernah tayang di tempat lain.</p>
            <x-input-error :messages="$errors->get('source_url')" />
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 pt-2">
        <a href="{{ $editing ? route('chapters.show', [$book, $chapter]) : route('books.show', $book) }}" class="btn-ghost">Batal</a>
        <x-primary-button>{{ $editing ? 'Simpan Perubahan' : 'Tambah Bab' }}</x-primary-button>
    </div>
</form>
