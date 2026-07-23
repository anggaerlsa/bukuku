<x-public-layout>
    <section class="bg-shell text-white">
        <div class="max-w-4xl mx-auto px-4 py-20 sm:py-28 text-center">
            <x-application-logo class="h-24 w-auto mx-auto mb-5 drop-shadow-[0_4px_16px_rgba(79,70,229,0.25)]" />
            <h1 class="font-display text-5xl sm:text-7xl text-accent-light">{{ config('app.name') }}</h1>
            <p class="mt-3 font-display tracking-[0.3em] uppercase text-xs sm:text-sm text-white/60">Meja Perancang Dunia &amp; Lore</p>
            <p class="mt-6 max-w-2xl mx-auto text-lg text-white/80">
                Bangun dunia novelmu dari nol: rancang <strong class="text-accent-light">karakter</strong>,
                petakan <strong class="text-accent-light">lokasi</strong>, dan rajut lore ceritamu dalam satu
                pustaka yang rapi — meja kerja worldbuilding milikmu sendiri.
            </p>
            <div class="mt-8 flex items-center justify-center gap-3">
                <a href="{{ route('login') }}" class="btn-primary !py-3 !px-7 text-sm">Masuk ke Meja Kerja</a>
            </div>
            <p class="mt-3 text-xs text-white/40">Akses khusus para perancang dunia — hubungi admin untuk dibuatkan akun.</p>
        </div>
    </section>

    <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="divider max-w-md mx-auto mb-10">Yang bisa kau bangun </div>
        <div class="grid gap-6 sm:grid-cols-3">
            <div class="panel p-7 text-center">
                <div class="text-4xl mb-3">🌍</div>
                <h3 class="font-display text-xl text-ink">Dunia &amp; Universe</h3>
                <p class="mt-2 text-ink-light">Kelola banyak dunia sekaligus. Tiap dunia adalah universe tersendiri untuk kisah-kisahmu.</p>
            </div>
            <div class="panel p-7 text-center">
                <div class="text-4xl mb-3">👤</div>
                <h3 class="font-display text-xl text-ink">Database Karakter</h3>
                <p class="mt-2 text-ink-light">Catat peran, ras, kepribadian, latar belakang, dan tujuan tiap tokoh dalam ceritamu.</p>
            </div>
            <div class="panel p-7 text-center">
                <div class="text-4xl mb-3">🗺️</div>
                <h3 class="font-display text-xl text-ink">Peta Lokasi</h3>
                <p class="mt-2 text-ink-light">Petakan kerajaan, kota, dan tempat-tempat penting — lengkap dengan hierarki wilayah.</p>
            </div>
        </div>
    </section>
</x-public-layout>
