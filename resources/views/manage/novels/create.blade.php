<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('novels.index') }}" class="text-sm text-ink hover:text-accent-dark">← Semua Novel</a>
        <h1 class="font-display text-2xl text-ink mt-1">Novel Baru</h1>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="panel p-6 sm:p-8">
            @include('manage.novels._form')
        </div>
    </div>
</x-app-layout>
