@php($editing = $novel->exists)

<form method="POST"
      action="{{ $editing ? route('novels.update', $novel) : route('novels.store') }}"
      enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div>
        <x-input-label for="title" value="Judul Novel" />
        <x-text-input id="title" name="title" type="text" class="mt-1" :value="old('title', $novel->title)"
                      required autofocus placeholder="cth. Bajak Laut Rasi Selatan" />
        <x-input-error :messages="$errors->get('title')" />
    </div>

    <div class="grid sm:grid-cols-3 gap-5">
        <div class="sm:col-span-2">
            <x-input-label for="tagline" value="Tagline (kalimat singkat)" />
            <x-text-input id="tagline" name="tagline" type="text" class="mt-1" :value="old('tagline', $novel->tagline)"
                          placeholder="Perompak antarbintang yang mencuri peta menuju gerbang kuno…" />
            <x-input-error :messages="$errors->get('tagline')" />
        </div>
        <div>
            <x-input-label for="status" value="Status" />
            <select id="status" name="status" class="select mt-1">
                @foreach (\App\Models\Novel::statuses() as $key => $label)
                    <option value="{{ $key }}" @selected(old('status', $novel->status) === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('status')" />
        </div>
    </div>

    <div>
        <x-input-label for="synopsis" value="Sinopsis" />
        <textarea id="synopsis" name="synopsis" rows="5" class="textarea mt-1"
                  placeholder="Garis besar ceritanya: siapa tokohnya, apa taruhannya, ke mana ia bergerak…">{{ old('synopsis', $novel->synopsis) }}</textarea>
        <x-input-error :messages="$errors->get('synopsis')" />
    </div>

    {{-- Cover --}}
    <div class="grid sm:grid-cols-2 gap-5">
        <div>
            <x-input-label for="cover_image" value="Unggah Sampul (maks 2MB)" />
            <input id="cover_image" name="cover_image" type="file" accept="image/*"
                   class="mt-1 block w-full text-sm text-ink-light file:mr-3 file:rounded-md file:border-0 file:bg-accent/20 file:px-4 file:py-2 file:font-display file:text-xs file:uppercase file:tracking-wider file:text-ink hover:file:bg-accent/30">
            <x-input-error :messages="$errors->get('cover_image')" />
        </div>
        <div>
            <x-input-label for="cover_url" value="…atau tautan gambar (URL)" />
            <x-text-input id="cover_url" name="cover_url" type="url" class="mt-1" placeholder="https://…" :value="old('cover_url')" />
            <x-input-error :messages="$errors->get('cover_url')" />
        </div>
        @if ($editing && $novel->cover_image)
            <div class="sm:col-span-2">
                <img src="{{ $novel->coverUrl() }}" alt="" class="h-32 rounded-lg shadow object-cover">
            </div>
        @endif
    </div>

    {{-- Theme: repaints this novel and every page under it --}}
    <div>
        <x-input-label value="Tema Tampilan" />
        <p class="text-xs text-ink-light mt-0.5 mb-2">
            Mengubah font dan warna saat novel ini dan seluruh isinya dibuka — dunia, lokasi, karakter, galeri.
            Tidak memengaruhi novel lain.
        </p>
        @php($chosen = old('theme', $novel->theme ?? \App\Support\NovelTheme::DEFAULT))
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach (\App\Support\NovelTheme::THEMES as $key => $t)
                <label class="panel p-4 cursor-pointer block transition hover:shadow-accent
                              {{ $chosen === $key ? 'ring-2 ring-accent' : '' }}">
                    <div class="flex items-start gap-3">
                        <input type="radio" name="theme" value="{{ $key }}" @checked($chosen === $key)
                               class="mt-1 border-line/60 text-accent focus:ring-accent/50">
                        <div class="min-w-0 flex-1">
                            <span class="font-display text-ink" style="font-family: '{{ $t['display'] }}', serif;">
                                {{ $t['label'] }}
                            </span>
                            <p class="text-xs text-ink-light mt-0.5">{{ $t['blurb'] }}</p>
                            <div class="flex items-center gap-1.5 mt-2">
                                @foreach ($t['swatches'] as $swatch)
                                    <span class="h-5 w-5 rounded-full border border-black/10" style="background: {{ $swatch }}"></span>
                                @endforeach
                                <span class="text-xs text-ink-faint ml-1">{{ $t['display'] }} · {{ $t['body'] }}</span>
                            </div>
                        </div>
                    </div>
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('theme')" />
    </div>

    {{-- Genres describe the book, so they live here rather than on each world. --}}
    <div>
        <x-input-label value="Genre" />
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-1 panel p-4 !shadow-none">
            @forelse ($genres as $genre)
                <label class="flex items-center gap-2 text-sm text-ink">
                    <input type="checkbox" name="genres[]" value="{{ $genre->id }}"
                           @checked(in_array($genre->id, old('genres', $selectedGenres)))
                           class="rounded border-line/40 text-accent focus:ring-accent/50">
                    {{ $genre->name }}
                </label>
            @empty
                <p class="col-span-full text-sm text-ink-light">
                    Belum ada genre.
                    @can('manage genres')<a href="{{ route('genres.create') }}" class="text-ink underline hover:text-accent-dark">Tambah genre</a> lebih dulu bila perlu.@endcan
                </p>
            @endforelse
        </div>
        <p class="text-xs text-ink-light mt-1">Genre menempel pada novel — dunianya mewarisi dari sini.</p>
        <x-input-error :messages="$errors->get('genres')" />
    </div>

    <div class="flex items-center gap-3 pt-1">
        <x-primary-button>{{ $editing ? 'Simpan Perubahan' : 'Buat Novel' }}</x-primary-button>
        <a href="{{ $editing ? route('novels.show', $novel) : route('novels.index') }}" class="btn-outline">Batal</a>
    </div>
</form>
