<x-guest-layout>
    @if ($user->isRejected())
        <div class="text-center space-y-4">
            <div class="text-4xl">🚫</div>
            <h1 class="font-display text-2xl text-ink">Pendaftaran Ditolak</h1>
            <p class="text-ink-light">
                Maaf, pendaftaran akun <strong class="text-ink">{{ $user->name }}</strong> tidak disetujui.
                Kalau menurutmu ini keliru, hubungi pengelola Bukuku.
            </p>
        </div>
    @else
        <div class="text-center space-y-4">
            <div class="text-4xl">⏳</div>
            <h1 class="font-display text-2xl text-ink">Menunggu Persetujuan</h1>
            <p class="text-ink-light">
                Halo <strong class="text-ink">{{ $user->name }}</strong> — akunmu sudah terdaftar dan
                sedang menunggu persetujuan superadmin.
            </p>
            <div class="panel p-4 text-left text-sm text-ink-light space-y-1">
                <p><span class="text-ink-faint">Nama pengguna</span> · {{ $user->username }}</p>
                <p><span class="text-ink-faint">Surel</span> · {{ $user->email }}</p>
                <p><span class="text-ink-faint">Terdaftar</span> · {{ $user->created_at?->format('d M Y, H:i') }}</p>
                <p><span class="text-ink-faint">Status</span> · <span class="badge-muted">{{ $user->statusLabel() }}</span></p>
            </div>
            <p class="text-xs text-ink-light">
                Begitu disetujui, muat ulang halaman ini dan kamu langsung masuk ke dasbor.
            </p>
        </div>
    @endif

    <div class="flex items-center justify-center gap-3 mt-6">
        <a href="{{ route('pending') }}" class="btn-outline btn-sm">Muat Ulang</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn-outline btn-sm">Keluar</button>
        </form>
    </div>
</x-guest-layout>
