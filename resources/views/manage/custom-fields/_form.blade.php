@php($editing = $field->exists)

<form method="POST"
      action="{{ $editing ? route('custom-fields.update', [$world, $field]) : route('custom-fields.store', $world) }}"
      class="space-y-6"
      x-data="{ type: @js(old('type', $field->type ?? 'text')) }">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div>
        <x-input-label for="name" value="Nama Atribut" />
        <x-text-input id="name" name="name" type="text" class="mt-1" :value="old('name', $field->name)"
                      required autofocus placeholder="Tingkat Mana, Klearans Keamanan, Kasta…" />
        <x-input-error :messages="$errors->get('name')" />
    </div>

    <div class="grid sm:grid-cols-2 gap-5">
        <div>
            <x-input-label for="applies_to" value="Dipasang di" />
            <select id="applies_to" name="applies_to" class="select mt-1">
                @foreach (\App\Models\CustomField::targets() as $key => $label)
                    <option value="{{ $key }}" @selected(old('applies_to', $field->applies_to) === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="text-xs text-ink-light mt-1">“Lokasi (semua tingkat)” berlaku dari Benua sampai Desa.</p>
            <x-input-error :messages="$errors->get('applies_to')" />
        </div>

        <div>
            <x-input-label for="type" value="Jenis Isian" />
            <select id="type" name="type" x-model="type" class="select mt-1">
                @foreach (\App\Models\CustomField::TYPES as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('type')" />
        </div>
    </div>

    <div x-show="type === 'select'" x-cloak>
        <x-input-label for="options" value="Daftar Pilihan (satu per baris)" />
        <textarea id="options" name="options" rows="4" class="textarea mt-1"
                  placeholder="Rendah&#10;Sedang&#10;Tinggi">{{ old('options', $field->options) }}</textarea>
        <x-input-error :messages="$errors->get('options')" />
    </div>

    <div class="grid sm:grid-cols-[1fr_8rem] gap-5">
        <div>
            <x-input-label for="hint" value="Penjelasan singkat (opsional)" />
            <x-text-input id="hint" name="hint" type="text" class="mt-1" :value="old('hint', $field->hint)"
                          placeholder="Ditampilkan kecil di bawah kolom isian." />
            <x-input-error :messages="$errors->get('hint')" />
        </div>
        <div>
            <x-input-label for="position" value="Urutan" />
            <x-text-input id="position" name="position" type="number" min="0" max="999" class="mt-1"
                          :value="old('position', $field->position ?? 0)" />
            <x-input-error :messages="$errors->get('position')" />
        </div>
    </div>

    <div class="flex items-center gap-3 pt-1">
        <x-primary-button>{{ $editing ? 'Simpan Perubahan' : 'Tambahkan Atribut' }}</x-primary-button>
        <a href="{{ route('custom-fields.index', $world) }}" class="btn-outline">Batal</a>
    </div>
</form>
