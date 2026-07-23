@php($editing = $genre->exists)

<form method="POST" action="{{ $editing ? route('genres.update', $genre) : route('genres.store') }}" class="space-y-5">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div>
        <x-input-label for="name" value="Nama Genre" />
        <x-text-input id="name" name="name" type="text" class="mt-1" :value="old('name', $genre->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="description" value="Deskripsi (opsional)" />
        <x-text-input id="description" name="description" type="text" class="mt-1" :value="old('description', $genre->description)" />
        <x-input-error :messages="$errors->get('description')" />
    </div>

    <div class="flex items-center gap-3 pt-1">
        <x-primary-button>{{ $editing ? 'Simpan' : 'Tambahkan Genre' }}</x-primary-button>
        <a href="{{ route('genres.index') }}" class="btn-outline">Batal</a>
    </div>
</form>
