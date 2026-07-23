<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="font-display text-2xl text-ink">{{ $canManageAll ? 'Semua Dunia' : 'Dunia Saya' }}</h1>
                <p class="text-sm text-ink-light">Selamat datang kembali, {{ auth()->user()->name }} · <span class="badge-accent">{{ auth()->user()->primaryRoleLabel() }}</span></p>
            </div>
            <div class="flex items-center gap-2">
                @can('create novels')
                    <a href="{{ route('novels.create') }}" class="btn-primary">✚ Novel Baru</a>
                @endcan
                @can('create worlds')
                    <a href="{{ route('worlds.create') }}" class="btn-outline">✚ Dunia Baru</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        {{-- Stats --}}
        <div class="grid gap-4 grid-cols-2 lg:grid-cols-5">
            <div class="panel p-5 flex items-center gap-4">
                <div class="grid place-items-center h-12 w-12 rounded-lg bg-accent/15 text-2xl">📕</div>
                <div>
                    <p class="font-display text-3xl text-ink leading-none">{{ $stats['novels'] }}</p>
                    <p class="text-xs uppercase tracking-wider text-ink-light mt-1">Novel</p>
                </div>
            </div>
            <div class="panel p-5 flex items-center gap-4">
                <div class="grid place-items-center h-12 w-12 rounded-lg bg-accent/15 text-2xl">🌍</div>
                <div>
                    <p class="font-display text-3xl text-ink leading-none">{{ $stats['worlds'] }}</p>
                    <p class="text-xs uppercase tracking-wider text-ink-light mt-1">Dunia</p>
                </div>
            </div>
            <div class="panel p-5 flex items-center gap-4">
                <div class="grid place-items-center h-12 w-12 rounded-lg bg-success/15 text-2xl">👤</div>
                <div>
                    <p class="font-display text-3xl text-ink leading-none">{{ $stats['characters'] }}</p>
                    <p class="text-xs uppercase tracking-wider text-ink-light mt-1">Karakter</p>
                </div>
            </div>
            <div class="panel p-5 flex items-center gap-4">
                <div class="grid place-items-center h-12 w-12 rounded-lg bg-danger/10 text-2xl">🗺️</div>
                <div>
                    <p class="font-display text-3xl text-ink leading-none">{{ $stats['locations'] }}</p>
                    <p class="text-xs uppercase tracking-wider text-ink-light mt-1">Lokasi</p>
                </div>
            </div>
            @can('manage users')
                <div class="panel p-5 flex items-center gap-4">
                    <div class="grid place-items-center h-12 w-12 rounded-lg bg-shell/10 text-2xl"></div>
                    <div>
                        <p class="font-display text-3xl text-ink leading-none">{{ $stats['users'] }}</p>
                        <p class="text-xs uppercase tracking-wider text-ink-light mt-1">Pengguna</p>
                    </div>
                </div>
            @else
                <a href="{{ route('worlds.index') }}" class="panel p-5 flex items-center gap-4 hover:shadow-accent transition">
                    <div class="grid place-items-center h-12 w-12 rounded-lg bg-shell/10 text-2xl">📚</div>
                    <div>
                        <p class="font-display text-lg text-ink leading-tight">Semua Dunia</p>
                        <p class="text-xs uppercase tracking-wider text-ink-light mt-1">Buka semua dunia →</p>
                    </div>
                </a>
            @endcan
        </div>

        {{-- Quick actions (admin) --}}
        @canany(['manage genres', 'manage users'])
            <div class="flex flex-wrap gap-3">
                @can('manage genres')<a href="{{ route('genres.index') }}" class="btn-outline">Kelola Genre</a>@endcan
                @can('manage users')<a href="{{ route('users.index') }}" class="btn-outline">👤 Kelola Pengguna</a>@endcan
            </div>
        @endcanany

        {{-- Novel milik sendiri --}}
        <div>
            <div class="flex items-center gap-3 mb-5">
                <h2 class="font-display text-xl text-ink">📕 Novel Saya</h2>
                <span class="h-px flex-1 bg-shell/20"></span>
                <a href="{{ route('novels.index') }}" class="text-sm text-ink hover:text-accent-dark shrink-0">Semua →</a>
            </div>

            @if ($novels->isEmpty())
                <div class="panel p-12 text-center">
                    <p class="font-display text-xl text-ink">Kamu belum punya novel.</p>
                    <p class="text-ink-light mt-1">Novel adalah wadah paling atas — dunianya menyusul di dalamnya.</p>
                    @can('create novels')
                        <a href="{{ route('novels.create') }}" class="btn-primary mt-4">✚ Novel Pertama</a>
                    @endcan
                </div>
            @else
                <div class="grid gap-5 sm:grid-cols-2">
                    @foreach ($novels as $novel)
                        <x-novel-card :novel="$novel" />
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Novel yang dibagikan penulis lain — terpisah, dan hanya bisa dibaca --}}
        <div>
            <div class="flex items-center gap-3 mb-2">
                <h2 class="font-display text-xl text-ink">🔗 Novel Dibagikan</h2>
                @if ($sharedNovelsTotal)<span class="badge-success">{{ $sharedNovelsTotal }}</span>@endif
                <span class="h-px flex-1 bg-shell/20"></span>
                <a href="{{ route('novels.index') }}#dibagikan" class="text-sm text-ink hover:text-accent-dark shrink-0">Semua →</a>
            </div>
            <p class="text-sm text-ink-light mb-5">Dibagikan penulis lain sebagai referensi — hanya bisa dilihat.</p>

            @if ($sharedNovels->isEmpty())
                <div class="panel p-8 text-center text-ink-light">
                    Belum ada penulis lain yang membagikan novelnya.
                </div>
            @else
                <div class="grid gap-5 sm:grid-cols-2">
                    @foreach ($sharedNovels as $novel)
                        <x-novel-card :novel="$novel" />
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Worlds --}}
        <div>
            <div class="flex items-center gap-3 mb-5">
                <h2 class="font-display text-xl text-ink">{{ $canManageAll ? 'Dunia Terbaru' : 'Dunia-Duniaku' }}</h2>
                <span class="h-px flex-1 bg-shell/20"></span>
                <a href="{{ route('worlds.index') }}" class="text-sm text-ink hover:text-accent-dark shrink-0">Semua →</a>
            </div>

            @if ($worlds->isEmpty())
                <div class="panel p-12 text-center">
                    <p class="font-display text-xl text-ink">Belum ada dunia yang ditempa.</p>
                    <p class="text-ink-light mt-1">Mulailah membangun universe pertamamu.</p>
                    @can('create worlds')
                        <a href="{{ route('worlds.create') }}" class="btn-primary mt-4">✚ Buat Dunia Pertama</a>
                    @endcan
                </div>
            @else
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($worlds as $world)
                        <x-world-card :world="$world" />
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
