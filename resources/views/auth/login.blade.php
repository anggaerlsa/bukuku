<x-guest-layout>
    <h1 class="font-display text-2xl text-ink text-center">Masuk</h1>
    <p class="text-center text-sm text-ink-light mb-2">Tunjukkan tanda pengenalmu, perancang dunia.</p>
    <div class="divider"></div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="login" :value="__('Email atau Username')" />
            <x-text-input id="login" class="mt-1" type="text" name="login" :value="old('login')" required autofocus autocomplete="username" placeholder="cth. user" />
            <x-input-error :messages="$errors->get('login')" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Kata Sandi')" />
            <x-text-input id="password" class="mt-1" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-ink-light">
                <input id="remember_me" type="checkbox" class="rounded border-line/40 text-accent focus:ring-accent/50" name="remember">
                <span>Ingat saya</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-ink underline hover:text-accent-dark" href="{{ route('password.request') }}">
                    Lupa sandi?
                </a>
            @endif
        </div>

        <x-primary-button class="w-full justify-center !py-3 text-sm">
            Masuk
        </x-primary-button>

        @if (Route::has('register'))
            <p class="text-center text-sm text-ink-light">
                Belum punya akun?
                <a href="{{ route('register') }}" class="text-ink underline hover:text-accent-dark">Daftar</a>
                — perlu persetujuan superadmin sebelum aktif.
            </p>
        @endif
    </form>
</x-guest-layout>
