@php($editing = $world->exists)

<form method="POST"
      action="{{ $editing ? route('worlds.update', $world) : route('worlds.store') }}"
      enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($editing) @method('PUT') @endif

    {{-- A world always belongs to a novel — that is the layer above it. --}}
    <div>
        <x-input-label for="novel_id" value="Novel" />
        @if ($novels->isEmpty())
            <div class="input mt-1 flex items-center bg-surface-sunken text-sm text-ink-light">
                Belum ada novel.
            </div>
            <p class="text-xs text-danger mt-1">
                Dunia bernaung di bawah sebuah novel —
                <a href="{{ route('novels.create') }}" class="underline">buat novelnya dulu</a>.
            </p>
        @else
            <select id="novel_id" name="novel_id" class="select mt-1" required>
                <option value="">— pilih novel —</option>
                @foreach ($novels as $novel)
                    <option value="{{ $novel->id }}" @selected((string) old('novel_id', $world->novel_id) === (string) $novel->id)>{{ $novel->title }}</option>
                @endforeach
            </select>
            <p class="text-xs text-ink-light mt-1">Satu novel bisa menaungi banyak dunia.</p>
        @endif
        <x-input-error :messages="$errors->get('novel_id')" />
    </div>

    <div>
        <x-input-label for="name" value="Nama Dunia" />
        <x-text-input id="name" name="name" type="text" class="mt-1" :value="old('name', $world->name)" required autofocus placeholder="cth. Aswanagari" />
        <x-input-error :messages="$errors->get('name')" />
    </div>

    <div class="grid sm:grid-cols-3 gap-5">
        <div class="sm:col-span-2">
            <x-input-label for="tagline" value="Tagline (kalimat singkat)" />
            <x-text-input id="tagline" name="tagline" type="text" class="mt-1" :value="old('tagline', $world->tagline)" placeholder="Negeri tujuh wangsa di bawah naungan naga…" />
            <x-input-error :messages="$errors->get('tagline')" />
        </div>
        <div>
            <x-input-label for="status" value="Status" />
            <select id="status" name="status" class="select mt-1">
                @foreach (\App\Models\World::statuses() as $key => $label)
                    <option value="{{ $key }}" @selected(old('status', $world->status) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <x-input-label for="premise" value="Premis / Gambaran Dunia" />
        <textarea id="premise" name="premise" rows="5" class="textarea mt-1" placeholder="Ceritakan inti dunia ini: sejarahnya, konfliknya, apa yang membuatnya unik…">{{ old('premise', $world->premise) }}</textarea>
        <x-input-error :messages="$errors->get('premise')" />
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
        @if ($editing && $world->cover_image)
            <div class="sm:col-span-2">
                <img src="{{ $world->coverUrl() }}" alt="" class="h-28 w-full object-cover rounded-lg shadow">
            </div>
        @endif
    </div>

    {{-- Genres --}}
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
        <x-input-error :messages="$errors->get('genres')" />
    </div>

    <div class="flex items-center gap-3 pt-1">
        <x-primary-button>{{ $editing ? 'Simpan Perubahan' : 'Buat Dunia' }}</x-primary-button>
        <a href="{{ $editing ? route('worlds.show', $world) : route('worlds.index') }}" class="btn-outline">Batal</a>
    </div>
</form>
