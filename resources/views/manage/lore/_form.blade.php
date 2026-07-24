@php($editing = $entry->exists)

<form method="POST"
      action="{{ $editing ? route('lore.update', [$world, $entry]) : route('lore.store', $world) }}"
      enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div>
        <x-input-label for="title" value="Judul Artikel" />
        <x-text-input id="title" name="title" type="text" class="mt-1" :value="old('title', $entry->title)"
                      required autofocus placeholder="cth. Sistem Sihir & Tingkatannya" />
        <x-input-error :messages="$errors->get('title')" />
    </div>

    {{--
        Kategori sengaja teks bebas: kosakatanya milik penulis, bukan milik
        aplikasi. Daftar di bawah hanya saran — yang sudah dipakai di dunia ini
        lebih dulu, disusul usulan yang cocok dengan tema novelnya.
    --}}
    <div class="grid sm:grid-cols-[1fr_8rem] gap-5">
        <div>
            <x-input-label for="category" value="Kategori" />
            <x-text-input id="category" name="category" type="text" class="mt-1" list="lore-categories"
                          :value="old('category', $entry->category)" placeholder="Ketik bebas, atau pilih dari daftar…" />
            <datalist id="lore-categories">
                @foreach ($categoryOptions as $option)
                    <option value="{{ $option }}"></option>
                @endforeach
            </datalist>
            <p class="text-xs text-ink-light mt-1">
                Boleh apa saja — kategori ini milikmu, bukan bawaan aplikasi. Kosongkan kalau belum yakin.
            </p>
            @if ($categoryOptions)
                <div class="flex flex-wrap gap-1.5 mt-2">
                    @foreach ($categoryOptions as $option)
                        <button type="button" class="badge-muted hover:border-accent/40"
                                onclick="document.getElementById('category').value = @js($option)">{{ $option }}</button>
                    @endforeach
                </div>
            @endif
            <x-input-error :messages="$errors->get('category')" />
        </div>
        <div>
            <x-input-label for="position" value="Urutan" />
            <x-text-input id="position" name="position" type="number" min="0" max="9999" class="mt-1"
                          :value="old('position', $entry->position ?? 0)" />
            <p class="text-xs text-ink-light mt-1">Dalam kategorinya.</p>
            <x-input-error :messages="$errors->get('position')" />
        </div>
    </div>

    <div>
        <x-input-label for="summary" value="Ringkasan (satu baris)" />
        <x-text-input id="summary" name="summary" type="text" class="mt-1" :value="old('summary', $entry->summary)" />
        <x-input-error :messages="$errors->get('summary')" />
    </div>

    <div>
        <x-input-label for="body" value="Isi Artikel" />
        <textarea id="body" name="body" rows="16" class="textarea mt-1"
                  placeholder="Tulis sepanjang yang perlu — aturan sihir, silsilah dewa, daftar istilah, doktrin taktik…">{{ old('body', $entry->body) }}</textarea>
        <x-input-error :messages="$errors->get('body')" />
    </div>

    <x-custom-field-inputs :fields="$customFields" :values="$customValues" />

    {{-- Gambar utama --}}
    <div class="grid sm:grid-cols-2 gap-5">
        <div>
            <x-input-label for="cover_image" value="Unggah Gambar (maks 2MB)" />
            <input id="cover_image" name="cover_image" type="file" accept="image/*"
                   class="mt-1 block w-full text-sm text-ink-light file:mr-3 file:rounded-md file:border-0 file:bg-accent/20 file:px-4 file:py-2 file:font-display file:text-xs file:uppercase file:tracking-wider file:text-ink hover:file:bg-accent/30">
            <x-input-error :messages="$errors->get('cover_image')" />
        </div>
        <div>
            <x-input-label for="cover_url" value="…atau tautan gambar (URL)" />
            <x-text-input id="cover_url" name="cover_url" type="url" class="mt-1" placeholder="https://…" :value="old('cover_url')" />
            <x-input-error :messages="$errors->get('cover_url')" />
        </div>
        @if ($editing && $entry->coverUrl())
            <div class="sm:col-span-2">
                <img src="{{ $entry->coverUrl() }}" alt="" class="h-28 rounded-lg shadow object-cover">
            </div>
        @endif
    </div>

    <div class="flex items-center gap-3 pt-1">
        <x-primary-button>{{ $editing ? 'Simpan Perubahan' : 'Tambahkan Artikel' }}</x-primary-button>
        <a href="{{ $editing ? route('lore.show', [$world, $entry]) : route('lore.index', $world) }}" class="btn-outline">Batal</a>
    </div>
</form>
