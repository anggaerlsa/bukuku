<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('lore.index', $world) }}" class="text-sm text-ink hover:text-accent-dark">← Lore · {{ $world->name }}</a>
        <div class="flex flex-wrap items-center justify-between gap-3 mt-1">
            <h1 class="font-display text-2xl text-ink">{{ $lore->title }}</h1>
            @can('update', $world)
                <div class="flex items-center gap-2">
                    <a href="{{ route('lore.edit', [$world, $lore]) }}" class="btn-outline btn-sm">Sunting</a>
                    <form method="POST" action="{{ route('lore.destroy', [$world, $lore]) }}"
                          onsubmit="return confirm('Hapus artikel “{{ $lore->title }}”?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn-danger btn-sm">Hapus</button>
                    </form>
                </div>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <div class="flex flex-wrap items-center gap-2">
            @if ($lore->category)
                <a href="{{ route('lore.index', $world) }}?kategori={{ urlencode($lore->category) }}" class="badge-accent">{{ $lore->category }}</a>
            @else
                <span class="badge-muted">Tanpa Kategori</span>
            @endif
        </div>

        @if ($lore->summary)
            <p class="text-lg text-ink-light italic">{{ $lore->summary }}</p>
        @endif

        @if ($lore->coverUrl())
            <div class="panel overflow-hidden">
                <img src="{{ $lore->coverUrl() }}" alt="{{ $lore->title }}" class="w-full max-h-96 object-cover">
            </div>
        @endif

        @if ($lore->body)
            <div class="panel p-6 sm:p-8">
                <div class="text-ink leading-relaxed whitespace-pre-line">{{ $lore->body }}</div>
            </div>
        @else
            <div class="panel p-8 text-center text-ink-light">
                Isi artikel belum ditulis.
                @can('update', $world)<a href="{{ route('lore.edit', [$world, $lore]) }}" class="text-ink underline hover:text-accent-dark">Tulis sekarang</a>.@endcan
            </div>
        @endif

        <x-custom-field-list :owner="$lore" title="Atribut Artikel" />

        {{-- Artikel lain dalam kategori yang sama --}}
        @if ($siblings->isNotEmpty())
            <section class="panel p-6">
                <p class="label mb-3">Lainnya di {{ $lore->categoryLabel() }}</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($siblings as $sibling)
                        <a href="{{ route('lore.show', [$world, $sibling]) }}" class="badge-muted hover:border-accent/40">{{ $sibling->title }}</a>
                    @endforeach
                </div>
            </section>
        @endif

        <x-image-gallery :world="$world" :owner="$lore" type="lore"
                         title="Galeri Artikel"
                         hint="Gambar utama diatur di form Sunting; gambar di sini adalah tambahannya." />
    </div>
</x-app-layout>
