<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('characters.index', $world) }}" class="text-sm text-ink hover:text-accent-dark">← Karakter · {{ $world->name }}</a>
        <div class="flex flex-wrap items-center justify-between gap-3 mt-1">
            <h1 class="font-display text-2xl text-ink">{{ $character->name }}</h1>
            @can('update', $world)
                <div class="flex items-center gap-2">
                    <a href="{{ route('characters.edit', [$world, $character]) }}" class="btn-outline btn-sm">Sunting</a>
                    <form method="POST" action="{{ route('characters.destroy', [$world, $character]) }}"
                          onsubmit="return confirm('Hapus karakter “{{ $character->name }}”?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn-danger btn-sm">Hapus</button>
                    </form>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid md:grid-cols-[16rem_1fr] gap-6">
            {{-- Portrait + quick facts --}}
            <div class="space-y-4">
                <div class="panel overflow-hidden">
                    <img src="{{ $character->portraitUrl() }}" alt="Potret {{ $character->name }}" class="w-full aspect-[4/5] object-cover">
                </div>
                <div class="panel p-4 space-y-2 text-sm">
                    @php
                        $facts = [
                            'Peran' => $character->roleLabel(),
                            'Status' => $character->statusLabel(),
                            'Ras' => $character->species,
                            'Jenis Kelamin' => $character->gender,
                            'Usia' => $character->age,
                            'Pekerjaan' => $character->occupation,
                            'Afiliasi' => $character->affiliation,
                            'Julukan' => $character->aliases,
                        ];
                    @endphp
                    @foreach (array_filter($facts) as $label => $value)
                        <div class="flex justify-between gap-3 border-b border-line/10 pb-2 last:border-0 last:pb-0">
                            <span class="text-ink-light font-display text-xs uppercase tracking-wider">{{ $label }}</span>
                            <span class="text-ink text-right">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>

                <x-custom-field-list :owner="$character" />

                {{-- Places this character is tied to --}}
                @if ($character->origin || $character->residence)
                    <div class="panel p-4 space-y-3 text-sm">
                        <p class="label">Tempat</p>
                        @foreach (['Asal' => $character->origin, 'Domisili' => $character->residence] as $label => $place)
                            @if ($place)
                                <div>
                                    <span class="text-ink-light font-display text-xs uppercase tracking-wider">{{ $label }}</span>
                                    <a href="{{ route('locations.show', [$world, $place->tierKey(), $place->id]) }}"
                                       class="block text-ink hover:text-accent-dark underline">
                                        {{ $place->name }}
                                    </a>
                                    <span class="badge-muted mt-1">{{ $place->displayLabel() }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Narrative --}}
            <div class="space-y-5">
                @foreach (['Penampilan' => $character->appearance, 'Kepribadian' => $character->personality, 'Latar Belakang' => $character->backstory, 'Tujuan & Motivasi' => $character->goals] as $title => $body)
                    @if ($body)
                        <div class="panel p-6">
                            <h2 class="label">{{ $title }}</h2>
                            <p class="mt-1 text-ink leading-relaxed whitespace-pre-line">{{ $body }}</p>
                        </div>
                    @endif
                @endforeach

                @if (! $character->appearance && ! $character->personality && ! $character->backstory && ! $character->goals)
                    <div class="panel p-8 text-center text-ink-light">
                        Detail naratif belum diisi.
                        @can('update', $world)<a href="{{ route('characters.edit', [$world, $character]) }}" class="text-ink underline hover:text-accent-dark">Lengkapi sekarang</a>.@endcan
                    </div>
                @endif

                <x-image-gallery :world="$world" :owner="$character" type="character"
                                 title="Galeri Karakter"
                                 hint="Potret utama diatur di form Sunting; gambar di sini adalah tambahannya." />

                {{-- Organisasi tempat karakter ini bernaung, beserta jabatannya --}}
                <div class="panel p-6 space-y-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="font-display text-xl text-ink">🛡️ Organisasi</h2>
                        <span class="badge-accent">{{ $memberships->count() }}</span>
                    </div>

                    @if ($memberships->isEmpty())
                        <p class="text-sm text-ink-light">Belum tergabung di organisasi mana pun.</p>
                    @else
                        <ul class="divide-y divide-line/40">
                            @foreach ($memberships as $member)
                                <li class="flex flex-wrap items-center gap-3 py-2">
                                    <a href="{{ route('organizations.show', [$world, $member->organization]) }}"
                                       class="font-display text-ink hover:text-accent-dark">{{ $member->organization->name }}</a>
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
                                              onsubmit="return confirm('Lepas dari “{{ $member->organization->name }}”?');">
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
                        @if ($joinableOrganizations->isEmpty())
                            <p class="text-sm text-ink-light border-t border-line/40 pt-4">
                                @if ($world->organizations()->count() === 0)
                                    Dunia ini belum punya organisasi —
                                    <a href="{{ route('organizations.create', $world) }}" class="underline">buat dulu</a>.
                                @else
                                    Sudah tergabung di semua organisasi dunia ini.
                                @endif
                            </p>
                        @else
                            <form method="POST" action="{{ route('organization-members.store', $world) }}"
                                  class="border-t border-line/40 pt-4 space-y-3">
                                @csrf
                                <input type="hidden" name="character_id" value="{{ $character->id }}">
                                <p class="label">Gabung organisasi</p>
                                <div class="grid sm:grid-cols-3 gap-3">
                                    <div>
                                        <x-input-label for="organization_id" value="Organisasi" />
                                        <select id="organization_id" name="organization_id" class="select mt-1">
                                            <option value="">— pilih organisasi —</option>
                                            @foreach ($joinableOrganizations as $org)
                                                <option value="{{ $org->id }}" @selected(old('organization_id') == $org->id)>{{ $org->name }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('organization_id')" />
                                    </div>
                                    <div>
                                        <x-input-label for="role" value="Jabatan / Pangkat" />
                                        <x-text-input id="role" name="role" type="text" class="mt-1" :value="old('role')" />
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
                                <x-input-error :messages="$errors->get('character_id')" />
                                <x-primary-button class="btn-sm">Gabungkan</x-primary-button>
                            </form>
                        @endif
                    @endcan
                </div>

                {{-- Ties to other characters. Stored once; shown from both sides. --}}
                <div class="panel p-6 space-y-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="font-display text-xl text-ink">🔗 Relasi</h2>
                        <span class="badge-accent">{{ $relationEntries->count() }}</span>
                    </div>

                    @if ($relationEntries->isEmpty())
                        <p class="text-sm text-ink-light">Belum ada relasi dengan karakter lain.</p>
                    @else
                        <ul class="divide-y divide-line/40">
                            @foreach ($relationEntries->groupBy('group') as $group => $entries)
                                <li class="pt-3 first:pt-0 pb-1">
                                    <p class="label">{{ $group }}</p>
                                </li>
                                @foreach ($entries as $entry)
                                    <li class="flex flex-wrap items-center gap-3 py-2">
                                        <span class="badge-muted shrink-0">{{ $entry['label'] }}</span>
                                        <a href="{{ route('characters.show', [$world, $entry['other']]) }}"
                                           class="text-ink hover:text-accent-dark font-display">{{ $entry['other']->name }}</a>
                                        @if ($entry['note'])
                                            <span class="text-sm text-ink-light">— {{ $entry['note'] }}</span>
                                        @endif
                                        @can('update', $world)
                                            <form method="POST" class="ml-auto"
                                                  action="{{ route('character-relations.destroy', [$world, $character, $entry['id']]) }}"
                                                  onsubmit="return confirm('Lepas relasi dengan “{{ $entry['other']->name }}”?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn-ghost btn-sm text-danger">Lepas</button>
                                            </form>
                                        @endcan
                                    </li>
                                @endforeach
                            @endforeach
                        </ul>
                    @endif

                    @can('update', $world)
                        @if ($otherCharacters->isEmpty())
                            <p class="text-sm text-ink-light border-t border-line/40 pt-4">
                                Butuh minimal dua karakter di dunia ini untuk membuat relasi.
                            </p>
                        @else
                            <form method="POST" action="{{ route('character-relations.store', [$world, $character]) }}"
                                  class="border-t border-line/40 pt-4 space-y-3">
                                @csrf
                                <p class="label">Tambah relasi</p>
                                <div class="grid sm:grid-cols-2 gap-3">
                                    <div>
                                        <x-input-label for="related_character_id" value="Karakter" />
                                        <select id="related_character_id" name="related_character_id" class="select mt-1">
                                            <option value="">— pilih karakter —</option>
                                            @foreach ($otherCharacters as $other)
                                                <option value="{{ $other->id }}" @selected(old('related_character_id') == $other->id)>{{ $other->name }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('related_character_id')" />
                                    </div>
                                    <div>
                                        <x-input-label for="type" value="…berperan sebagai" />
                                        <select id="type" name="type" class="select mt-1">
                                            @foreach (\App\Models\CharacterRelation::grouped() as $group => $types)
                                                <optgroup label="{{ $group }}">
                                                    @foreach ($types as $key => $label)
                                                        <option value="{{ $key }}" @selected(old('type') === $key)>{{ $label }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('type')" />
                                    </div>
                                </div>
                                <div>
                                    <x-input-label for="note" value="Catatan (opsional)" />
                                    <x-text-input id="note" name="note" type="text" class="mt-1" :value="old('note')"
                                                  placeholder="mis. terpisah sejak perang, hubungan rahasia…" />
                                    <x-input-error :messages="$errors->get('note')" />
                                </div>
                                <p class="text-xs text-ink-light">
                                    Dibaca: <strong>karakter yang dipilih</strong> adalah <strong>peran tersebut</strong> bagi {{ $character->name }}.
                                    Relasi otomatis muncul di halaman kedua karakter.
                                </p>
                                <x-primary-button class="btn-sm">Tautkan</x-primary-button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
