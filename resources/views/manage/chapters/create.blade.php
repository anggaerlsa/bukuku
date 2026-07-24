<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('books.show', $book) }}" class="text-sm text-ink hover:text-accent-dark">← {{ $book->title }}</a>
        <h1 class="font-display text-2xl text-ink mt-1">Bab Baru</h1>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="panel p-6 sm:p-8">
            @include('manage.chapters._form')
        </div>
    </div>
</x-app-layout>
