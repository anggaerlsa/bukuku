<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Novel;
use App\Support\NovelTheme;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
        $search = trim((string) $request->query('q', ''));

        // Own novels and other people's shared ones are listed separately, each
        // with its own page cursor so paging one never disturbs the other.
        $base = fn () => Novel::withCount('worlds')
            ->with('user', 'genres')
            ->when($search, fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$search}%")
                ->orWhere('tagline', 'like', "%{$search}%")))
            ->latest();

        $novels = $base()->where('user_id', $user->id)
            ->paginate(12, ['*'], 'halaman')->withQueryString();

        $shared = $base()->shared()->where('user_id', '!=', $user->id)
            ->paginate(12, ['*'], 'dibagikan')->withQueryString();

        // Someone who can manage novels also sees the rest — the ones nobody
        // shared. Kept apart so it never blurs with "shared with me".
        $others = $user->can('manage novels')
            ? $base()->where('user_id', '!=', $user->id)->where('is_shared', false)
                ->paginate(12, ['*'], 'lain')->withQueryString()
            : null;

        return view('manage.novels.index', compact('novels', 'shared', 'others', 'search'));
    }

    public function create()
    {
        $this->authorize('create', Novel::class);

        return view('manage.novels.create', [
            'novel' => new Novel(['status' => 'active', 'theme' => NovelTheme::DEFAULT]),
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
        $data['theme'] = NovelTheme::key($data['theme'] ?? null);
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

        // The table of contents. Chapter bodies are left out deliberately —
        // a novel of 73 episodes is well over half a million characters, and
        // none of it is shown on this page.
        $books = $novel->books()->withCount('chapters')->with(['chapters' => fn ($q) => $q->select(
            'id', 'book_id', 'title', 'position', 'word_count'
        )])->get();

        $chaptersTotal = $books->sum('chapters_count');
        $wordsTotal = $books->sum(fn ($book) => $book->chapters->sum('word_count'));

        return view('manage.novels.show', compact('novel', 'worlds', 'books', 'chaptersTotal', 'wordsTotal'));
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
        $data['theme'] = NovelTheme::key($data['theme'] ?? $novel->theme);
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

        Uploads::delete($novel->cover_image);

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
            // Optional on purpose: anything that does not pick a theme simply
            // gets the neutral default rather than failing validation.
            'theme' => ['nullable', Rule::in(NovelTheme::keys())],
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
            Uploads::delete($current);

            return Uploads::store($request->file('cover_image'), 'novels');
        }

        if ($url = trim((string) $request->input('cover_url', ''))) {
            return $url;
        }

        return $current;
    }
}
