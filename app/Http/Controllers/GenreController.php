<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GenreController extends Controller
{
    public function index()
    {
        $genres = Genre::withCount('worlds')->orderBy('name')->paginate(20);

        return view('manage.genres.index', compact('genres'));
    }

    public function create()
    {
        return view('manage.genres.create', ['genre' => new Genre()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:genres,name',
            'description' => 'nullable|string|max:255',
        ]);

        $data['slug'] = $this->uniqueSlug($data['name']);
        Genre::create($data);

        return redirect()->route('genres.index')->with('status', "Genre \"{$data['name']}\" ditambahkan.");
    }

    public function edit(Genre $genre)
    {
        return view('manage.genres.edit', compact('genre'));
    }

    public function update(Request $request, Genre $genre)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('genres', 'name')->ignore($genre->id)],
            'description' => 'nullable|string|max:255',
        ]);

        $data['slug'] = $this->uniqueSlug($data['name'], $genre->id);
        $genre->update($data);

        return redirect()->route('genres.index')->with('status', "Genre \"{$genre->name}\" diperbarui.");
    }

    public function destroy(Genre $genre)
    {
        $name = $genre->name;
        $genre->delete();

        return redirect()->route('genres.index')->with('status', "Genre \"{$name}\" dihapus.");
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'genre';
        $slug = $base;
        $i = 1;

        while (
            Genre::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }
}
