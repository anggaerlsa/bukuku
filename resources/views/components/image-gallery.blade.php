@props(['world', 'owner', 'type', 'title' => 'Galeri', 'hint' => null])

@php($images = $owner->images)

<section class="panel p-6 space-y-4">
    <div class="flex flex-wrap items-center gap-3">
        <h2 class="font-display text-xl text-ink">🖼️ {{ $title }}</h2>
        <span class="badge-accent">{{ $images->count() }}</span>
    </div>

    @if ($images->isEmpty())
        <p class="text-sm text-ink-light">Belum ada gambar tambahan.</p>
    @else
        <div class="grid gap-4 grid-cols-2 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($images as $image)
                <figure class="panel overflow-hidden flex flex-col">
                    <a href="{{ $image->url() }}" target="_blank" rel="noopener" class="block bg-surface-sunken">
                        <img src="{{ $image->url() }}" alt="{{ $image->caption ?: 'Gambar galeri' }}"
                             class="w-full aspect-[4/3] object-cover hover:opacity-90 transition" loading="lazy">
                    </a>
                    <figcaption class="p-2 text-xs text-ink-light flex-1">
                        {{ $image->caption ?: '—' }}
                    </figcaption>
                    @can('update', $world)
                        <div class="flex items-center gap-1 border-t border-line/20 px-2 py-1">
                            <form method="POST" action="{{ route('images.cover', [$world, $image]) }}">
                                @csrf
                                @method('PATCH')
                                <button class="btn-ghost btn-sm" title="Jadikan sampul">Jadikan sampul</button>
                            </form>
                            <span class="flex-1"></span>
                            <form method="POST" action="{{ route('images.destroy', [$world, $image]) }}"
                                  onsubmit="return confirm('Hapus gambar ini dari galeri?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn-ghost btn-sm text-danger">Hapus</button>
                            </form>
                        </div>
                    @endcan
                </figure>
            @endforeach
        </div>
    @endif

    @can('update', $world)
        <form method="POST" action="{{ route('images.store', [$world, $type, $owner->id]) }}"
              enctype="multipart/form-data" class="border-t border-line/40 pt-4 space-y-3">
            @csrf
            <p class="label">Tambah gambar</p>
            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <x-input-label for="images-{{ $type }}-{{ $owner->id }}" value="Unggah (bisa banyak, maks 4MB tiap gambar)" />
                    <input id="images-{{ $type }}-{{ $owner->id }}" name="images[]" type="file" accept="image/*" multiple
                           class="mt-1 block w-full text-sm text-ink-light file:mr-3 file:rounded-md file:border-0 file:bg-accent/20 file:px-4 file:py-2 file:font-display file:text-xs file:uppercase file:tracking-wider file:text-ink hover:file:bg-accent/30">
                    <x-input-error :messages="$errors->get('images')" />
                    <x-input-error :messages="$errors->get('images.0')" />
                </div>
                <div>
                    <x-input-label for="image_url-{{ $type }}-{{ $owner->id }}" value="…atau tautan gambar (URL)" />
                    <x-text-input id="image_url-{{ $type }}-{{ $owner->id }}" name="image_url" type="url" class="mt-1"
                                  placeholder="https://…" :value="old('image_url')" />
                    <x-input-error :messages="$errors->get('image_url')" />
                </div>
            </div>
            <div>
                <x-input-label for="caption-{{ $type }}-{{ $owner->id }}" value="Keterangan (opsional)" />
                <x-text-input id="caption-{{ $type }}-{{ $owner->id }}" name="caption" type="text" class="mt-1"
                              :value="old('caption')" placeholder="mis. sketsa wajah, peta kota bagian selatan…" />
                <x-input-error :messages="$errors->get('caption')" />
            </div>
            @if ($hint)
                <p class="text-xs text-ink-light">{{ $hint }}</p>
            @endif
            <x-primary-button class="btn-sm">Unggah</x-primary-button>
        </form>
    @endcan
</section>
