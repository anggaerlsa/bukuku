<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-2xl text-ink">Daftar</h1>
        <p class="text-sm text-ink-light mt-1">
            Akun baru menunggu persetujuan superadmin sebelum bisa dipakai.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="name" value="Nama" />
            <x-text-input id="name" name="name" type="text" class="mt-1" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="username" value="Nama Pengguna" />
            <x-text-input id="username" name="username" type="text" class="mt-1" :value="old('username')" required autocomplete="username" />
            <p class="text-xs text-ink-light mt-1">Dipakai untuk masuk, selain surel. Huruf, angka, strip, garis bawah.</p>
            <x-input-error :messages="$errors->get('username')" />
        </div>

        <div>
            <x-input-label for="email" value="Surel" />
            <x-text-input id="email" name="email" type="email" class="mt-1" :value="old('email')" required autocomplete="email" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" value="Kata Sandi" />
            <x-text-input id="password" name="password" type="password" class="mt-1" required autocomplete="new-password" />
            <p class="text-xs text-ink-light mt-1">Minimal 8 karakter.</p>
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Ulangi Kata Sandi" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <div class="flex items-center justify-between gap-3 pt-1">
            <a href="{{ route('login') }}" class="text-sm text-ink-light hover:text-accent-dark underline">
                Sudah punya akun?
            </a>
            <x-primary-button>Daftar</x-primary-button>
        </div>
    </form>
</x-guest-layout>
