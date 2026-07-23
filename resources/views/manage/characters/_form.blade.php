@php($editing = $character->exists)

<form method="POST"
      action="{{ $editing ? route('characters.update', [$world, $character]) : route('characters.store', $world) }}"
      enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div>
        <x-input-label for="name" value="Nama Karakter" />
        <x-text-input id="name" name="name" type="text" class="mt-1" :value="old('name', $character->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" />
    </div>

    <div class="grid sm:grid-cols-2 gap-5">
        <div>
            <x-input-label for="aliases" value="Nama Lain / Julukan" />
            <x-text-input id="aliases" name="aliases" type="text" class="mt-1" :value="old('aliases', $character->aliases)" placeholder="Sang Pewaris, Si Bayangan…" />
        </div>
        <div>
            <x-input-label for="role" value="Peran" />
            <select id="role" name="role" class="select mt-1">
                <option value="">—</option>
                @foreach (\App\Models\Character::roles() as $key => $label)
                    <option value="{{ $key }}" @selected(old('role', $character->role) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid sm:grid-cols-3 gap-5">
        <div>
            <x-input-label for="species" value="Ras / Spesies" />
            <x-text-input id="species" name="species" type="text" class="mt-1" :value="old('species', $character->species)" placeholder="Manusia, Elf…" />
        </div>
        <div>
            <x-input-label for="gender" value="Jenis Kelamin" />
            <x-text-input id="gender" name="gender" type="text" class="mt-1" :value="old('gender', $character->gender)" />
        </div>
        <div>
            <x-input-label for="age" value="Usia" />
            <x-text-input id="age" name="age" type="text" class="mt-1" :value="old('age', $character->age)" placeholder="19 tahun" />
        </div>
    </div>

    <div class="grid sm:grid-cols-3 gap-5">
        <div>
            <x-input-label for="status" value="Status" />
            <select id="status" name="status" class="select mt-1">
                <option value="">—</option>
                @foreach (\App\Models\Character::statuses() as $key => $label)
                    <option value="{{ $key }}" @selected(old('status', $character->status) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="occupation" value="Pekerjaan / Gelar" />
            <x-text-input id="occupation" name="occupation" type="text" class="mt-1" :value="old('occupation', $character->occupation)" />
        </div>
        <div>
            <x-input-label for="affiliation" value="Afiliasi / Kelompok" />
            <x-text-input id="affiliation" name="affiliation" type="text" class="mt-1" :value="old('affiliation', $character->affiliation)" placeholder="Wangsa Naga…" />
        </div>
    </div>

    {{-- Ties to real places in this world (locations span five tier tables) --}}
    <div class="grid sm:grid-cols-2 gap-5">
        <x-location-select
            :world="$world"
            name="origin"
            label="Asal (tempat kelahiran)"
            :options="$locationOptions"
            :selected="old('origin', \App\Support\LocationLookup::tokenFor($character->origin))"
            hint="Tempat karakter ini berasal." />

        <x-location-select
            :world="$world"
            name="residence"
            label="Domisili (tempat tinggal saat ini)"
            :options="$locationOptions"
            :selected="old('residence', \App\Support\LocationLookup::tokenFor($character->residence))"
            hint="Boleh berbeda dari asal." />
    </div>

    <div>
        <x-input-label for="appearance" value="Penampilan" />
        <textarea id="appearance" name="appearance" rows="3" class="textarea mt-1">{{ old('appearance', $character->appearance) }}</textarea>
    </div>
    <div>
        <x-input-label for="personality" value="Kepribadian" />
        <textarea id="personality" name="personality" rows="3" class="textarea mt-1">{{ old('personality', $character->personality) }}</textarea>
    </div>
    <div>
        <x-input-label for="backstory" value="Latar Belakang" />
        <textarea id="backstory" name="backstory" rows="4" class="textarea mt-1">{{ old('backstory', $character->backstory) }}</textarea>
    </div>
    <div>
        <x-input-label for="goals" value="Tujuan / Motivasi" />
        <textarea id="goals" name="goals" rows="3" class="textarea mt-1">{{ old('goals', $character->goals) }}</textarea>
    </div>

    <x-custom-field-inputs :fields="$customFields" :values="$customValues" />

    {{-- Portrait --}}
    <div class="grid sm:grid-cols-2 gap-5">
        <div>
            <x-input-label for="portrait_image" value="Unggah Potret (maks 2MB)" />
            <input id="portrait_image" name="portrait_image" type="file" accept="image/*"
                   class="mt-1 block w-full text-sm text-ink-light file:mr-3 file:rounded-md file:border-0 file:bg-accent/20 file:px-4 file:py-2 file:font-display file:text-xs file:uppercase file:tracking-wider file:text-ink hover:file:bg-accent/30">
            <x-input-error :messages="$errors->get('portrait_image')" />
        </div>
        <div>
            <x-input-label for="portrait_url" value="…atau tautan gambar (URL)" />
            <x-text-input id="portrait_url" name="portrait_url" type="url" class="mt-1" placeholder="https://…" :value="old('portrait_url')" />
            <x-input-error :messages="$errors->get('portrait_url')" />
        </div>
        @if ($editing && $character->portrait_image)
            <div class="sm:col-span-2 flex items-center gap-3 text-sm text-ink-light">
                <img src="{{ $character->portraitUrl() }}" alt="" class="h-20 w-16 object-cover rounded shadow">
                <span>Potret saat ini dipertahankan jika tidak diganti.</span>
            </div>
        @endif
    </div>

    <div class="flex items-center gap-3 pt-1">
        <x-primary-button>{{ $editing ? 'Simpan Perubahan' : 'Tambahkan Karakter' }}</x-primary-button>
        <a href="{{ $editing ? route('characters.show', [$world, $character]) : route('characters.index', $world) }}" class="btn-outline">Batal</a>
    </div>
</form>
