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

    <div class="flex items-center gap-3 pt-1">
        <x-primary-button>{{ $editing ? 'Simpan Perubahan' : 'Buat Novel' }}</x-primary-button>
        <a href="{{ $editing ? route('novels.show', $novel) : route('novels.index') }}" class="btn-outline">Batal</a>
    </div>
</form>
