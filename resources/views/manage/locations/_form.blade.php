@php($editing = (bool) $node)

<form method="POST"
      action="{{ $editing ? route('locations.update', [$world, $tier, $node->id]) : route('locations.store', $world) }}"
      enctype="multipart/form-data" class="space-y-6"
      x-data="{
          tier: @js(old('tier', $tier)),
          parentId: @js((string) old('parent_id', $parentId)),
          parentsByTier: @js($parentsByTier),
          parentMap: @js(\App\Support\Hierarchy::parentMap()),
          labels: @js(\App\Support\Hierarchy::labels()),
          suggestions: @js(\App\Support\Hierarchy::SUGGESTIONS),
          get parentTier() { return this.parentMap[this.tier] || null; },
          get needsParent() { return !! this.parentTier; },
          get parentOptions() { return this.parentTier ? (this.parentsByTier[this.parentTier] || []) : []; },
          label(k) { return this.labels[k] || k; },
      }">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div>
        <x-input-label for="name" value="Nama Lokasi" />
        <x-text-input id="name" name="name" type="text" class="mt-1" :value="old('name', $node?->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" />
    </div>

    <div class="grid sm:grid-cols-2 gap-5">
        {{-- Tier (immutable on edit — each tier is its own table) --}}
        <div>
            <x-input-label value="Tingkat (hirarki)" />
            @if ($editing)
                <div class="input flex items-center justify-between bg-surface-muted/40 mt-1">
                    <span>{{ \App\Support\Hierarchy::label($tier) }}</span>
                    <span class="text-xs text-ink-light">🔒 tetap</span>
                </div>
                <input type="hidden" name="tier" value="{{ $tier }}">
                <p class="text-xs text-ink-light mt-1">Tingkat tak bisa diubah — tiap tingkat tabel DB terpisah.</p>
            @else
                <select name="tier" x-model="tier" @change="parentId = ''" class="select mt-1">
                    @foreach (\App\Support\Hierarchy::labels() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-ink-light mt-1">Benua → Negara → Provinsi → Kota → Desa.</p>
            @endif
            <x-input-error :messages="$errors->get('tier')" />
        </div>

        {{-- Parent (from the tier directly above, in its own table) --}}
        <div x-show="needsParent" x-cloak>
            <x-input-label value="Berada di dalam (induk)" />
            <select name="parent_id" x-model="parentId" :disabled="! needsParent" class="select mt-1">
                <option value="">— pilih induk —</option>
                <template x-for="p in parentOptions" :key="p.id">
                    <option :value="String(p.id)" x-text="p.name"></option>
                </template>
            </select>
            <p class="text-xs mt-1" :class="parentOptions.length ? 'text-ink-light' : 'text-danger'">
                <span x-show="parentOptions.length">Induk harus berupa <span class="font-semibold" x-text="label(parentTier)"></span>.</span>
                <span x-show="! parentOptions.length" x-cloak>Belum ada <span class="font-semibold" x-text="label(parentTier)"></span> di dunia ini — buat dulu sebelum menambah tingkat ini.</span>
            </p>
            <x-input-error :messages="$errors->get('parent_id')" />
        </div>
    </div>

    {{-- Free-text label (display identity only) --}}
    <div>
        <x-input-label for="type" value="Sebutan / Tipe (opsional)" />
        <x-text-input id="type" name="type" type="text" class="mt-1" :value="old('type', $node?->type)"
                      x-bind:placeholder="suggestions[tier] || 'mis. Kadipaten, Kerajaan, Metropolis…'" />
        <p class="text-xs text-ink-light mt-1">
            Hanya sebutan tampilan — <strong>tidak mengubah tingkat maupun tabelnya</strong>.
            Mis. tingkat <em>Provinsi</em> ditampilkan sebagai <em>Dukedom</em>, atau <em>Kota</em> jadi <em>Metropolis</em>.
        </p>
        <x-input-error :messages="$errors->get('type')" />
    </div>

    <div>
        <x-input-label for="summary" value="Ringkasan (satu baris)" />
        <x-text-input id="summary" name="summary" type="text" class="mt-1" :value="old('summary', $node?->summary)" />
    </div>

    <div>
        <x-input-label for="description" value="Deskripsi" />
        <textarea id="description" name="description" rows="4" class="textarea mt-1">{{ old('description', $node?->description) }}</textarea>
    </div>

    <div>
        <x-input-label for="geography" value="Geografi" />
        <textarea id="geography" name="geography" rows="3" class="textarea mt-1">{{ old('geography', $node?->geography) }}</textarea>
    </div>

    <div class="grid sm:grid-cols-3 gap-5">
        <div>
            <x-input-label for="climate" value="Iklim" />
            <x-text-input id="climate" name="climate" type="text" class="mt-1" :value="old('climate', $node?->climate)" />
        </div>
        <div>
            <x-input-label for="population" value="Populasi" />
            <x-text-input id="population" name="population" type="text" class="mt-1" :value="old('population', $node?->population)" placeholder="± 200.000 jiwa" />
        </div>
        <div>
            <x-input-label for="government" value="Pemerintahan / Penguasa" />
            <x-text-input id="government" name="government" type="text" class="mt-1" :value="old('government', $node?->government)" />
        </div>
    </div>

    <div>
        <x-input-label for="points_of_interest" value="Tempat Menarik" />
        <textarea id="points_of_interest" name="points_of_interest" rows="3" class="textarea mt-1">{{ old('points_of_interest', $node?->points_of_interest) }}</textarea>
    </div>

    <x-custom-field-inputs :fields="$customFields" :values="$customValues" :tier-aware="true" />

    {{-- Map --}}
    <div class="grid sm:grid-cols-2 gap-5">
        <div>
            <x-input-label for="map_image" value="Unggah Peta/Gambar (maks 2MB)" />
            <input id="map_image" name="map_image" type="file" accept="image/*"
                   class="mt-1 block w-full text-sm text-ink-light file:mr-3 file:rounded-md file:border-0 file:bg-accent/20 file:px-4 file:py-2 file:font-display file:text-xs file:uppercase file:tracking-wider file:text-ink hover:file:bg-accent/30">
            <x-input-error :messages="$errors->get('map_image')" />
        </div>
        <div>
            <x-input-label for="map_url" value="…atau tautan gambar (URL)" />
            <x-text-input id="map_url" name="map_url" type="url" class="mt-1" placeholder="https://…" :value="old('map_url')" />
            <x-input-error :messages="$errors->get('map_url')" />
        </div>
        @if ($editing && $node->mapUrl())
            <div class="sm:col-span-2">
                <img src="{{ $node->mapUrl() }}" alt="" class="h-28 rounded-lg shadow object-cover">
            </div>
        @endif
    </div>

    <div class="flex items-center gap-3 pt-1">
        <x-primary-button>{{ $editing ? 'Simpan Perubahan' : 'Tambahkan Lokasi' }}</x-primary-button>
        <a href="{{ $editing ? route('locations.show', [$world, $tier, $node->id]) : route('locations.index', $world) }}" class="btn-outline">Batal</a>
    </div>
</form>
