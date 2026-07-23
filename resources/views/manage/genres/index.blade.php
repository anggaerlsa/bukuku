<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-display text-2xl text-ink">Genre</h1>
                <p class="text-sm text-ink-light">Label untuk mengelompokkan dunia.</p>
            </div>
            <a href="{{ route('genres.create') }}" class="btn-primary">✚ Genre Baru</a>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">
        <div class="panel overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left font-display text-xs uppercase tracking-wider text-ink-light border-b border-line/15">
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3 hidden sm:table-cell">Deskripsi</th>
                        <th class="px-4 py-3">Dunia</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/10">
                    @forelse ($genres as $genre)
                        <tr class="hover:bg-surface-muted/40 transition">
                            <td class="px-4 py-3 font-display text-ink">{{ $genre->name }}</td>
                            <td class="px-4 py-3 hidden sm:table-cell text-ink-light">{{ $genre->description ?? '—' }}</td>
                            <td class="px-4 py-3"><span class="badge-accent">{{ $genre->worlds_count }}</span></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('genres.edit', $genre) }}" class="btn-outline btn-sm">Sunting</a>
                                    <form method="POST" action="{{ route('genres.destroy', $genre) }}"
                                          onsubmit="return confirm('Hapus genre “{{ $genre->name }}”?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-ink-light">Belum ada genre.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $genres->links() }}</div>
    </div>
</x-app-layout>
