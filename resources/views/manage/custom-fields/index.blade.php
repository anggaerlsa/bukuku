<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('worlds.show', $world) }}" class="text-sm text-ink hover:text-accent-dark">← {{ $world->name }}</a>
        <div class="flex flex-wrap items-center justify-between gap-3 mt-1">
            <h1 class="font-display text-2xl text-ink">⚙️ Atribut Dunia</h1>
            @can('update', $world)
                <a href="{{ route('custom-fields.create', $world) }}" class="btn-primary">✚ Atribut Baru</a>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <p class="text-sm text-ink-light">
            Atribut bebas milik dunia ini saja — pakai untuk hal yang tak ada di form bawaan,
            misal <em>Tingkat Mana</em>, <em>Klearans Keamanan</em>, atau <em>Kasta</em>.
            Setiap atribut muncul di form Karakter atau Lokasi sesuai sasarannya.
        </p>

        @if ($fields->isEmpty())
            <div class="panel p-12 text-center">
                <p class="font-display text-xl text-ink">Dunia ini belum punya atribut khusus.</p>
                <p class="text-ink-light mt-1">Tambahkan yang sesuai tema dunianya.</p>
                @can('update', $world)
                    <a href="{{ route('custom-fields.create', $world) }}" class="btn-primary mt-4">✚ Atribut Baru</a>
                @endcan
            </div>
        @else
            @foreach ($fields->groupBy('applies_to') as $target => $group)
                <section class="panel overflow-hidden">
                    <div class="px-5 py-3 border-b border-line/20 bg-surface-sunken">
                        <p class="label">{{ \App\Models\CustomField::targetLabel($target) }}</p>
                    </div>
                    <ul class="divide-y divide-line/20">
                        @foreach ($group as $field)
                            <li class="flex flex-wrap items-center gap-3 px-5 py-3">
                                <span class="font-display text-ink">{{ $field->name }}</span>
                                <span class="badge-muted">{{ $field->typeLabel() }}</span>
                                @if ($field->type === 'select')
                                    <span class="text-xs text-ink-light truncate">{{ $field->optionList()->implode(' · ') }}</span>
                                @endif
                                @if ($field->hint)
                                    <span class="text-xs text-ink-light italic truncate">{{ $field->hint }}</span>
                                @endif
                                <span class="flex-1"></span>
                                @can('update', $world)
                                    <a href="{{ route('custom-fields.edit', [$world, $field]) }}" class="text-xs text-ink-light hover:text-accent-dark">Sunting</a>
                                    <form method="POST" action="{{ route('custom-fields.destroy', [$world, $field]) }}"
                                          onsubmit="return confirm('Hapus atribut “{{ $field->name }}”? Semua isian atribut ini di karakter/lokasi ikut terhapus.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-ghost btn-sm text-danger">Hapus</button>
                                    </form>
                                @endcan
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        @endif
    </div>
</x-app-layout>
