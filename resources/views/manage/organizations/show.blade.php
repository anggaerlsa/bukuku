<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('organizations.index', $world) }}" class="text-sm text-ink hover:text-accent-dark">← Organisasi · {{ $world->name }}</a>
        <div class="flex flex-wrap items-center justify-between gap-3 mt-1">
            <h1 class="font-display text-2xl text-ink">{{ $organization->name }}</h1>
            @can('update', $world)
                <div class="flex items-center gap-2">
                    <a href="{{ route('organizations.edit', [$world, $organization]) }}" class="btn-outline btn-sm">Sunting</a>
                    <form method="POST" action="{{ route('organizations.destroy', [$world, $organization]) }}"
                          onsubmit="return confirm('Hapus organisasi “{{ $organization->name }}”?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn-danger btn-sm">Hapus</button>
                    </form>
                </div>
            @endcan
        </div>
    </x-slot>

    @php($statusBadge = ['aktif' => 'badge-success', 'bubar' => 'badge-muted', 'rahasia' => 'badge-danger'][$organization->status] ?? 'badge-muted')

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {{-- Identitas --}}
        <div class="grid sm:grid-cols-[9rem_1fr] gap-6">
            <div class="panel overflow-hidden">
                <img src="{{ $organization->emblemUrl() }}" alt="Lambang {{ $organization->name }}" class="w-full aspect-square object-cover">
            </div>
            <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="badge-accent">{{ $organization->displayLabel() }}</span>
                    <span class="{{ $statusBadge }}">{{ $organization->statusLabel() }}</span>
                    @if ($organization->parent)
                        <span class="text-sm text-ink-light">di bawah</span>
                        <a href="{{ route('organizations.show', [$world, $organization->parent]) }}"
                           class="text-sm text-ink hover:text-accent-dark font-display">{{ $organization->parent->name }}</a>
                    @endif
                </div>
                @if ($organization->aliases)
                    <p class="text-sm text-ink-light">Dikenal juga sebagai {{ $organization->aliases }}</p>
                @endif
                @if ($organization->motto)
                    <p class="text-lg text-ink-light italic">“{{ $organization->motto }}”</p>
                @endif
                @if ($organization->summary)
                    <p class="text-ink">{{ $organization->summary }}</p>
                @endif
                @if ($organization->headquarters)
                    <p class="text-sm text-ink-light">
                        🗺️ Markas:
                        <a href="{{ route('locations.show', [$world, $organization->headquarters->tierKey(), $organization->headquarters->id]) }}"
                           class="text-ink hover:text-accent-dark underline">{{ $organization->headquarters->name }}</a>
                        <span class="badge-muted ml-1">{{ $organization->headquarters->displayLabel() }}</span>
                    </p>
                @endif
            </div>
        </div>

        <x-custom-field-list :owner="$organization" />

        @foreach (['Deskripsi' => $organization->description, 'Tujuan & Doktrin' => $organization->purpose, 'Sejarah' => $organization->history] as $title => $body)
            @if ($body)
                <div class="panel p-6">
                    <h2 class="label">{{ $title }}</h2>
                    <p class="mt-1 text-ink leading-relaxed whitespace-pre-line">{{ $body }}</p>
                </div>
            @endif
        @endforeach

        {{-- Anggota, dengan jabatannya masing-masing --}}
        <section class="panel p-6 space-y-4">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="font-display text-xl text-ink">👤 Anggota</h2>
                <span class="badge-accent">{{ $members->count() }}</span>
            </div>

            @if ($members->isEmpty())
                <p class="text-sm text-ink-light">Belum ada anggota terdaftar.</p>
            @else
                <ul class="divide-y divide-line/40">
                    @foreach ($members as $member)
                        <li class="flex flex-wrap items-center gap-3 py-2">
                            <a href="{{ route('characters.show', [$world, $member->character]) }}"
                               class="font-display text-ink hover:text-accent-dark">{{ $member->character->name }}</a>
                            @if ($member->role)
                                <span class="badge-muted">{{ $member->role }}</span>
                            @endif
                            @if ($member->status !== 'aktif')
                                <span class="text-xs text-ink-light italic">{{ $member->statusLabel() }}</span>
                            @endif
                            @if ($member->note)
                                <span class="text-sm text-ink-light">— {{ $member->note }}</span>
                            @endif
                            @can('update', $world)
                                <form method="POST" class="ml-auto"
                                      action="{{ route('organization-members.destroy', [$world, $member->id]) }}"
                                      onsubmit="return confirm('Lepas {{ $member->character->name }} dari organisasi ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn-ghost btn-sm text-danger">Lepas</button>
                                </form>
                            @endcan
                        </li>
                    @endforeach
                </ul>
            @endif

            @can('update', $world)
                @if ($candidates->isEmpty())
                    <p class="text-sm text-ink-light border-t border-line/40 pt-4">
                        Semua karakter di dunia ini sudah terdaftar di sini.
                        @if ($world->characters()->count() === 0)
                            <a href="{{ route('characters.create', $world) }}" class="underline">Buat karakter</a> dulu.
                        @endif
                    </p>
                @else
                    <form method="POST" action="{{ route('organization-members.store', $world) }}"
                          class="border-t border-line/40 pt-4 space-y-3">
                        @csrf
                        <input type="hidden" name="organization_id" value="{{ $organization->id }}">
                        <p class="label">Tambah anggota</p>
                        <div class="grid sm:grid-cols-3 gap-3">
                            <div>
                                <x-input-label for="character_id" value="Karakter" />
                                <select id="character_id" name="character_id" class="select mt-1">
                                    <option value="">— pilih karakter —</option>
                                    @foreach ($candidates as $candidate)
                                        <option value="{{ $candidate->id }}" @selected(old('character_id') == $candidate->id)>{{ $candidate->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('character_id')" />
                            </div>
                            <div>
                                <x-input-label for="role" value="Jabatan / Pangkat" />
                                <x-text-input id="role" name="role" type="text" class="mt-1" :value="old('role')"
                                              placeholder="Laksamana Tertinggi…" />
                                <x-input-error :messages="$errors->get('role')" />
                            </div>
                            <div>
                                <x-input-label for="member_status" value="Status" />
                                <select id="member_status" name="status" class="select mt-1">
                                    @foreach (\App\Models\OrganizationMember::statuses() as $key => $label)
                                        <option value="{{ $key }}" @selected(old('status', 'aktif') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <x-input-label for="note" value="Catatan (opsional)" />
                            <x-text-input id="note" name="note" type="text" class="mt-1" :value="old('note')"
                                          placeholder="mis. mengundurkan diri setelah perang…" />
                        </div>
                        <x-primary-button class="btn-sm">Tambahkan</x-primary-button>
                    </form>
                @endif
            @endcan
        </section>

        {{-- Sub-organisasi --}}
        <section>
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <h2 class="font-display text-xl text-ink">Sub-organisasi</h2>
                <span class="badge-accent">{{ $organization->children->count() }}</span>
                <span class="h-px flex-1 bg-shell/20"></span>
                @can('update', $world)
                    <a href="{{ route('organizations.create', $world) }}?parent={{ $organization->id }}" class="btn-primary btn-sm shrink-0">✚ Sub-organisasi</a>
                @endcan
            </div>

            @if ($organization->children->isEmpty())
                <div class="panel p-8 text-center text-ink-light">
                    Tidak ada organisasi lain yang bernaung di bawah {{ $organization->name }}.
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($organization->children as $child)
                        <x-organization-card :world="$world" :organization="$child" />
                    @endforeach
                </div>
            @endif
        </section>

        <x-image-gallery :world="$world" :owner="$organization" type="organization"
                         title="Galeri Organisasi"
                         hint="Lambang utama diatur di form Sunting; gambar di sini adalah tambahannya." />
    </div>
</x-app-layout>
