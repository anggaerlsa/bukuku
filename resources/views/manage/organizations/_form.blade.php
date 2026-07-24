@php($editing = $organization->exists)

<form method="POST"
      action="{{ $editing ? route('organizations.update', [$world, $organization]) : route('organizations.store', $world) }}"
      enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div>
        <x-input-label for="name" value="Nama Organisasi" />
        <x-text-input id="name" name="name" type="text" class="mt-1" :value="old('name', $organization->name)"
                      required autofocus placeholder="cth. Pasukan Mawar Merah" />
        <x-input-error :messages="$errors->get('name')" />
    </div>

    <div class="grid sm:grid-cols-3 gap-5">
        <div>
            <x-input-label for="aliases" value="Nama Lain / Julukan" />
            <x-text-input id="aliases" name="aliases" type="text" class="mt-1" :value="old('aliases', $organization->aliases)" />
        </div>
        <div>
            <x-input-label for="type" value="Sebutan / Tipe" />
            <x-text-input id="type" name="type" type="text" class="mt-1" :value="old('type', $organization->type)"
                          placeholder="Wangsa, Divisi, Sekte, Guild, Ordo…" />
            <x-input-error :messages="$errors->get('type')" />
        </div>
        <div>
            <x-input-label for="status" value="Status" />
            <select id="status" name="status" class="select mt-1">
                @foreach (\App\Models\Organization::statuses() as $key => $label)
                    <option value="{{ $key }}" @selected(old('status', $organization->status) === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('status')" />
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-5">
        {{-- Sub-organisasi: sebuah divisi bernaung di bawah pasukannya --}}
        <div>
            <x-input-label for="parent_id" value="Bernaung di bawah (opsional)" />
            @if ($parents->isEmpty())
                <div class="input mt-1 flex items-center bg-surface-sunken text-sm text-ink-light">
                    Belum ada organisasi lain.
                </div>
            @else
                <select id="parent_id" name="parent_id" class="select mt-1">
                    <option value="">— berdiri sendiri —</option>
                    @foreach ($parents as $parent)
                        <option value="{{ $parent->id }}" @selected((string) old('parent_id', $organization->parent_id) === (string) $parent->id)>{{ $parent->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-ink-light mt-1">Mis. sebuah Divisi di bawah Pasukan Kekaisaran.</p>
            @endif
            <x-input-error :messages="$errors->get('parent_id')" />
        </div>

        <x-location-select
            :world="$world"
            name="headquarters"
            label="Markas (opsional)"
            :options="$locationOptions"
            :selected="old('headquarters', \App\Support\LocationLookup::tokenFor($organization->headquarters))"
            hint="Tempat organisasi ini berpusat." />
    </div>

    <div>
        <x-input-label for="motto" value="Moto / Semboyan" />
        <x-text-input id="motto" name="motto" type="text" class="mt-1" :value="old('motto', $organization->motto)" />
    </div>

    <div>
        <x-input-label for="summary" value="Ringkasan (satu baris)" />
        <x-text-input id="summary" name="summary" type="text" class="mt-1" :value="old('summary', $organization->summary)" />
    </div>

    <div>
        <x-input-label for="description" value="Deskripsi" />
        <textarea id="description" name="description" rows="4" class="textarea mt-1">{{ old('description', $organization->description) }}</textarea>
    </div>

    <div>
        <x-input-label for="purpose" value="Tujuan / Doktrin" />
        <textarea id="purpose" name="purpose" rows="3" class="textarea mt-1">{{ old('purpose', $organization->purpose) }}</textarea>
    </div>

    <div>
        <x-input-label for="history" value="Sejarah" />
        <textarea id="history" name="history" rows="4" class="textarea mt-1">{{ old('history', $organization->history) }}</textarea>
    </div>

    <x-custom-field-inputs :fields="$customFields" :values="$customValues" />

    {{-- Lambang --}}
    <div class="grid sm:grid-cols-2 gap-5">
        <div>
            <x-input-label for="emblem_image" value="Unggah Lambang (maks 2MB)" />
            <input id="emblem_image" name="emblem_image" type="file" accept="image/*"
                   class="mt-1 block w-full text-sm text-ink-light file:mr-3 file:rounded-md file:border-0 file:bg-accent/20 file:px-4 file:py-2 file:font-display file:text-xs file:uppercase file:tracking-wider file:text-ink hover:file:bg-accent/30">
            <x-input-error :messages="$errors->get('emblem_image')" />
        </div>
        <div>
            <x-input-label for="emblem_url" value="…atau tautan gambar (URL)" />
            <x-text-input id="emblem_url" name="emblem_url" type="url" class="mt-1" placeholder="https://…" :value="old('emblem_url')" />
            <x-input-error :messages="$errors->get('emblem_url')" />
        </div>
        @if ($editing && $organization->emblem_image)
            <div class="sm:col-span-2">
                <img src="{{ $organization->emblemUrl() }}" alt="" class="h-24 w-24 rounded-lg shadow object-cover">
            </div>
        @endif
    </div>

    <div class="flex items-center gap-3 pt-1">
        <x-primary-button>{{ $editing ? 'Simpan Perubahan' : 'Tambahkan Organisasi' }}</x-primary-button>
        <a href="{{ $editing ? route('organizations.show', [$world, $organization]) : route('organizations.index', $world) }}" class="btn-outline">Batal</a>
    </div>
</form>
