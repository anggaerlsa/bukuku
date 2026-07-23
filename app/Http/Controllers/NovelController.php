<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Novel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Novels — the layer above worlds. A novel is the book; its worlds are the
 * settings that book travels through.
 */
class NovelController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Novel::class);

        $user = $request->user();
        $query = Novel::withCount('worlds')->with('user', 'genres')->latest();

        // "milik" = punyaku · "dibagikan" = dibagikan penulis lain · "" = keduanya
        $scope = in_array($request->query('scope'), ['milik', 'dibagikan'], true)
            ? $request->query('scope') : null;

        if ($scope === 'milik') {
            $query->where('user_id', $user->id);
        } elseif ($scope === 'dibagikan') {
            $query->shared()->where('user_id', '!=', $user->id);
        } elseif (! $user->can('manage novels')) {
            // Everyone sees their own plus whatever other authors have shared.
            $query->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('is_shared', true));
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")->orWhere('tagline', 'like', "%{$search}%");
            });
        }

        $novels = $query->paginate(12)->withQueryString();
        $sharedCount = Novel::shared()->where('user_id', '!=', $user->id)->count();

        return view('manage.novels.index', compact('novels', 'search', 'scope', 'sharedCount'));
    }

    public function create()
    {
        $this->authorize('create', Novel::class);

        return view('manage.novels.create', [
            'novel' => new Novel(['status' => 'active']),
            'genres' => Genre::orderBy('name')->get(),
            'selectedGenres' => [],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Novel::class);

        $data = $this->validateNovel($request);
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['user_id'] = $request->user()->id;
        $data['cover_image'] = $this->resolveImage($request, null);

        $novel = Novel::create($data);
        $novel->genres()->sync($request->input('genres', []));

        return redirect()->route('novels.show', $novel)
            ->with('status', "Novel \"{$novel->title}\" dibuat. Tambahkan dunia tempat ceritanya berlangsung.");
    }

    public function show(Novel $novel)
    {
        $this->authorize('view', $novel);

        $novel->load('user', 'genres');
        $worlds = $novel->worlds()
            ->withCount(['characters', 'benuas', 'negaras', 'provinsis', 'kotas', 'desas'])
            ->latest()
            ->get();

        return view('manage.novels.show', compact('novel', 'worlds'));
    }

    public function edit(Novel $novel)
    {
        $this->authorize('update', $novel);

        return view('manage.novels.edit', [
            'novel' => $novel,
            'genres' => Genre::orderBy('name')->get(),
            'selectedGenres' => $novel->genres->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, Novel $novel)
    {
        $this->authorize('update', $novel);

        $data = $this->validateNovel($request, $novel);
        $data['slug'] = $this->uniqueSlug($data['title'], $novel->id);
        $data['cover_image'] = $this->resolveImage($request, $novel->cover_image);

        $novel->update($data);
        $novel->genres()->sync($request->input('genres', []));

        return redirect()->route('novels.show', $novel)->with('status', "Novel \"{$novel->title}\" diperbarui.");
    }

    /**
     * Turn member-wide read access on or off. Only the owner (or someone who
     * can manage novels) may flip it — it is an `update`, not a `view`.
     */
    public function share(Request $request, Novel $novel)
    {
        $this->authorize('update', $novel);

        $on = $request->boolean('share');

        $novel->update([
            'is_shared' => $on,
            'shared_at' => $on ? now() : null,
        ]);

        return back()->with('status', $on
            ? "Novel \"{$novel->title}\" kini bisa dibaca semua member — hanya lihat, tanpa bisa disunting."
            : "Novel \"{$novel->title}\" kembali privat.");
    }

    public function destroy(Novel $novel)
    {
        $this->authorize('delete', $novel);

        // Refuse while worlds remain: deleting here would take every world of
        // this novel and all their lore with it. Make that an explicit choice.
        if (($count = $novel->worlds()->count()) > 0) {
            return back()->with('error', "Novel ini masih memiliki {$count} dunia. Pindahkan atau hapus dunianya lebih dahulu.");
        }

        if ($novel->cover_image && ! Str::startsWith($novel->cover_image, ['http://', 'https://'])) {
            Storage::disk('public')->delete($novel->cover_image);
        }

        $title = $novel->title;
        $novel->delete();

        return redirect()->route('novels.index')->with('status', "Novel \"{$title}\" dihapus.");
    }

    private function validateNovel(Request $request, ?Novel $novel = null): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'synopsis' => 'nullable|string',
            'status' => 'required|in:' . implode(',', array_keys(Novel::statuses())),
            'cover_image' => 'nullable|image|max:2048',
            'cover_url' => 'nullable|url|max:2048',
            'genres' => 'nullable|array',
            'genres.*' => 'exists:genres,id',
        ]);
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'novel';
        $slug = $base;
        $i = 1;

        while (
            Novel::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    private function resolveImage(Request $request, ?string $current): ?string
    {
        if ($request->hasFile('cover_image')) {
            if ($current && ! Str::startsWith($current, ['http://', 'https://'])) {
                Storage::disk('public')->delete($current);
            }

            return $request->file('cover_image')->store('novels', 'public');
        }

        if ($url = trim((string) $request->input('cover_url', ''))) {
            return $url;
        }

        return $current;
    }
}
