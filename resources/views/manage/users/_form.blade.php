@php
    $editing = $user->exists;
    $roleLabels = [
        'superadmin' => 'Superadmin',
        'admin' => 'Admin',
        'author' => 'Penulis (Author)',
    ];
@endphp

<form method="POST" action="{{ $editing ? route('users.update', $user) : route('users.store') }}" class="space-y-5">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="grid sm:grid-cols-2 gap-5">
        <div class="sm:col-span-2">
            <x-input-label for="name" value="Nama Lengkap" />
            <x-text-input id="name" name="name" type="text" class="mt-1" :value="old('name', $user->name)" required autofocus />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="username" value="Username" />
            <x-text-input id="username" name="username" type="text" class="mt-1" :value="old('username', $user->username)" placeholder="cth. user" />
            <x-input-error :messages="$errors->get('username')" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="mt-1" :value="old('email', $user->email)" required />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="sm:col-span-2">
            <x-input-label for="role" value="Peran / Peran" />
            <select id="role" name="role" class="select mt-1" required>
                @foreach ($roles as $role)
                    <option value="{{ $role }}" @selected(old('role', $currentRole) === $role)>{{ $roleLabels[$role] ?? ucfirst($role) }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('role')" />
        </div>

        <div>
            <x-input-label for="password" value="Kata Sandi" />
            <x-text-input id="password" name="password" type="password" class="mt-1" autocomplete="new-password" />
            <p class="text-xs text-ink-light mt-1">{{ $editing ? 'Kosongkan bila tak ingin mengubah sandi.' : 'Minimal 8 karakter.' }}</p>
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Ulangi Kata Sandi" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1" autocomplete="new-password" />
        </div>
    </div>

    <div class="flex items-center gap-3 pt-1">
        <x-primary-button>{{ $editing ? 'Simpan Perubahan' : 'Tambahkan Pengguna' }}</x-primary-button>
        <a href="{{ route('users.index') }}" class="btn-outline">Batal</a>
    </div>
</form>
