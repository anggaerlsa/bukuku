<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-2xl text-ink">🔎 Cari</h1>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <form method="GET" action="{{ route('search.index') }}" class="panel p-4 flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-56">
                <x-input-label for="q" value="Kata kunci" />
                <x-text-input id="q" name="q" type="search" class="mt-1" :value="$q"
                              autofocus placeholder="cth. Luminaris, Prestia, darah murni…" />
                <p class="text-xs text-ink-light mt-1">
                    Menyusuri karakter, lokasi, organisasi, artikel lore, dan isi naskah bab.
                </p>
            </div>
            <x-primary-button>Cari</x-primary-button>
        </form>

        @if ($q === '')
            <div class="panel p-12 text-center text-ink-light">
                Ketik sesuatu untuk mulai mencari.
            </div>
        @elseif ($total === 0)
            <div class="panel p-12 text-center">
                <p class="font-display text-xl text-ink">Tidak ada yang cocok dengan “{{ $q }}”.</p>
                <p class="text-ink-light mt-1">Coba kata yang lebih pendek, atau sebagian namanya saja.</p>
            </div>
        @else
            <p class="text-sm text-ink-light">
                <strong class="text-ink">{{ $total }}</strong> hasil untuk “{{ $q }}”
                @if ($truncated)
                    · menampilkan sebagian dari tiap kelompok
                @endif
            </p>

            @foreach ($groups as $group)
                <section>
                    <div class="flex flex-wrap items-center gap-3 mb-3">
                        <h2 class="font-display text-lg text-ink">{{ $group['label'] }}</h2>
                        <span class="badge-muted">{{ $group['count'] }}</span>
                    </div>

                    <ul class="panel divide-y divide-line/30 overflow-hidden">
                        @foreach ($group['rows'] as $row)
                            <li>
                                <a href="{{ $group['link']($row) }}" class="block px-5 py-3 hover:bg-accent-soft">
                                    <p class="text-ink">{{ $row->name ?? $row->title }}</p>
                                    <p class="text-xs text-ink-light">
                                        @if ($row->world ?? null)
                                            {{ $row->world->name }}
                                        @elseif ($row->book ?? null)
                                            {{ $row->book->title }} · bab {{ $row->position }}
                                        @endif
                                    </p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        @endif
    </div>
</x-app-layout>
