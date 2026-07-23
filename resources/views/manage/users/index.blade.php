<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-display text-2xl text-ink">Pengguna</h1>
                <p class="text-sm text-ink-light">Kelola pengguna beserta perannya.</p>
            </div>
            <a href="{{ route('users.create') }}" class="btn-primary">✚ Pengguna Baru</a>
        </div>
    </x-slot>

    @php
        $actor = auth()->user();
        $roleMeta = [
            'superadmin' => ['Superadmin', 'badge-accent'],
            'admin' => ['Admin', 'badge-danger'],
            'author' => ['Penulis', 'badge-success'],
        ];
    @endphp

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">
        <div class="panel overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left font-display text-xs uppercase tracking-wider text-ink-light border-b border-line/15">
                        <th class="px-4 py-3">Pengguna</th>
                        <th class="px-4 py-3 hidden sm:table-cell">Email</th>
                        <th class="px-4 py-3">Peran</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/10">
                    @foreach ($users as $u)
                        @php
                            $role = $u->getRoleNames()->first();
                            [$rLabel, $rBadge] = $roleMeta[$role] ?? [($role ? ucfirst($role) : 'Tanpa Peran'), 'badge-muted'];
                            $canModify = $actor->hasRole('superadmin') || ! $u->hasRole('superadmin');
                            $isSelf = $u->id === $actor->id;
                        @endphp
                        <tr class="hover:bg-surface-muted/40 transition">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="grid place-items-center h-9 w-9 rounded-full bg-gradient-to-b from-accent-light to-accent-dark text-white font-display font-bold shrink-0">
                                        {{ strtoupper(substr($u->name, 0, 1)) }}
                                    </span>
                                    <div>
                                        <p class="font-display text-ink leading-tight">{{ $u->name }} @if ($isSelf)<span class="text-xs text-ink-light">(Anda)</span>@endif</p>
                                        <p class="text-xs text-ink-light">{{ '@' . ($u->username ?? '—') }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 hidden sm:table-cell text-ink-light">{{ $u->email }}</td>
                            <td class="px-4 py-3"><span class="{{ $rBadge }}">{{ $rLabel }}</span></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($canModify)
                                        <a href="{{ route('users.edit', $u) }}" class="btn-outline btn-sm">Sunting</a>
                                    @endif
                                    @if ($canModify && ! $isSelf)
                                        <form method="POST" action="{{ route('users.destroy', $u) }}"
                                              onsubmit="return confirm('Hapus pengguna “{{ $u->name }}”?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn-danger btn-sm">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div>{{ $users->links() }}</div>
    </div>
</x-app-layout>
