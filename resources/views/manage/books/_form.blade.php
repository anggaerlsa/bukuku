@php($editing = $book->exists)

<form method="POST"
      action="{{ $editing ? route('books.update', $book) : route('books.store') }}"
      enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div>
        <x-input-label for="novel_id" value="Novel" />
        <select id="novel_id" name="novel_id" required
                class="mt-1 w-full rounded-lg border-line bg-surface text-ink focus:border-accent focus:ring-accent">
            <option value="">— Pilih novel —</option>
            @foreach ($novels as $novel)
                <option value="{{ $novel->id }}" @selected((int) old('novel_id', $book->novel_id) === $novel->id)>
                    {{ $novel->title }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('novel_id')" />
    </div>

    <div>
        <x-input-label for="title" value="Judul Buku" />
        <x-text-input id="title" name="title" type="text" class="mt-1" :value="old('title', $book->title)"
                      required autofocus placeholder="cth. Jilid 1: Dunia Baru" />
        <p class="text-xs text-ink-light mt-1">Satu novel bisa terbagi jadi beberapa jilid; kalau cuma satu, namai saja seperti novelnya.</p>
        <x-input-error :messages="$errors->get('title')" />
    </div>

    <div>
        <x-input-label for="synopsis" value="Sinopsis" />
        <textarea id="synopsis" name="synopsis" rows="4"
                  class="mt-1 w-full rounded-lg border-line bg-surface text-ink focus:border-accent focus:ring-accent"
                  placeholder="Ringkasan jilid ini — boleh dikosongkan.">{{ old('synopsis', $book->synopsis) }}</textarea>
        <x-input-error :messages="$errors->get('synopsis')" />
    </div>

    <div>
        <x-input-label for="status" value="Status" />
        <select id="status" name="status" required
                class="mt-1 w-full rounded-lg border-line bg-surface text-ink focus:border-accent focus:ring-accent">
            @foreach (\App\Models\Book::statuses() as $key => $label)
                <option value="{{ $key }}" @selected(old('status', $book->status ?? 'draft') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" />
    </div>

    <div class="grid sm:grid-cols-2 gap-5">
        <div>
            <x-input-label for="cover_image" value="Sampul (unggah)" />
            <input id="cover_image" name="cover_image" type="file" accept="image/*"
                   class="mt-1 w-full text-sm text-ink-light file:mr-3 file:rounded-lg file:border-0 file:bg-shell/20 file:px-3 file:py-2 file:text-ink" />
            <x-input-error :messages="$errors->get('cover_image')" />
        </div>
        <div>
            <x-input-label for="cover_url" value="…atau tautan gambar" />
            <x-text-input id="cover_url" name="cover_url" type="url" class="mt-1"
                          :value="old('cover_url', \App\Support\Uploads::isExternal($book->cover_image) ? $book->cover_image : '')"
                          placeholder="https://…" />
            <x-input-error :messages="$errors->get('cover_url')" />
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 pt-2">
        <a href="{{ $editing ? route('books.show', $book) : route('books.index') }}" class="btn-ghost">Batal</a>
        <x-primary-button>{{ $editing ? 'Simpan Perubahan' : 'Buat Buku' }}</x-primary-button>
    </div>
</form>
