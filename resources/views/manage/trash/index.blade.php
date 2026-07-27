<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="font-display text-2xl text-ink">🗑 Sampah</h1>
            @if ($total > 0)
                <span class="badge-muted">{{ $total }} item</span>
            @endif
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        @if ($total === 0)
            <div class="panel p-12 text-center">
                <p class="font-display text-xl text-ink">Sampah kosong.</p>
                <p class="text-ink-light mt-1">
                    Apa pun yang kamu hapus mendarat di sini dulu, jadi salah klik masih bisa dibatalkan.
                </p>
            </div>
        @else
            <div class="panel border-l-4 border-accent px-4 py-3 text-sm text-ink-light">
                Yang ikut terhapus bersama induknya tidak didaftar sendiri — pulihkan induknya,
                semua isinya ikut kembali. <strong class="text-ink">Hapus permanen tidak bisa dibatalkan.</strong>
            </div>

            @foreach ($groups as $type => $rows)
                <section>
                    <div class="flex flex-wrap items-center gap-3 mb-3">
                        <h2 class="font-display text-lg text-ink">{{ \App\Support\Trash::label($type) }}</h2>
                        <span class="badge-muted">{{ $rows->count() }}</span>
                    </div>

                    <ul class="panel divide-y divide-line/30 overflow-hidden">
                        @foreach ($rows as $record)
                            @php($title = $record->{\App\Support\Trash::titleColumn($type)})
                            <li class="flex flex-wrap items-center gap-3 px-5 py-3">
                                <div class="min-w-0 flex-1">
                                    <p class="font-display text-ink truncate">{{ $title }}</p>
                                    <p class="text-xs text-ink-light">
                                        Dihapus {{ $record->deleted_at?->diffForHumans() }}
                                        @if ($record->deleted_at)
                                            · {{ $record->deleted_at->format('d M Y, H:i') }}
                                        @endif
                                    </p>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <form method="POST" action="{{ route('trash.restore', [$type, $record->id]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn-primary btn-sm">Pulihkan</button>
                                    </form>
                                    <form method="POST" action="{{ route('trash.destroy', [$type, $record->id]) }}"
                                          onsubmit="return confirm('Hapus PERMANEN “{{ $title }}” beserta seluruh isinya? Tindakan ini tidak bisa dibatalkan.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-outline btn-sm text-danger">Hapus permanen</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        @endif
    </div>
</x-app-layout>
