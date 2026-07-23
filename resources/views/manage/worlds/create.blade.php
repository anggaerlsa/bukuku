<x-app-layout>
    <x-slot name="header">
        <h1 class="font-display text-2xl text-ink">Buat Dunia Baru</h1>
        <p class="text-sm text-ink-light">Mulai sebuah universe baru untuk kisahmu.</p>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="panel p-6 sm:p-8">
            @include('manage.worlds._form')
        </div>
    </div>
</x-app-layout>
