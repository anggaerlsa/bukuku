<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-2xl text-ink">Pengguna Baru</h1>
        <p class="text-sm text-ink-light">Tambahkan pengguna baru dan tetapkan perannya.</p>
    </x-slot>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="panel p-6 sm:p-8">
            @include('manage.users._form')
        </div>
    </div>
</x-app-layout>
