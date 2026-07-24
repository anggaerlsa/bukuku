<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('books.index') }}" class="text-sm text-ink hover:text-accent-dark">← Buku</a>
        <h1 class="font-display text-2xl text-ink mt-1">Buku Baru</h1>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="panel p-6 sm:p-8">
            @include('manage.books._form')
        </div>
    </div>
</x-app-layout>
