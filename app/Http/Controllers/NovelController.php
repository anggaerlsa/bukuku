<?php

namespace App\Http\Controllers;

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
        $query = Novel::withCount('worlds')->with('user')->latest();

        if (! $user->can('manage novels')) {
            $query->where('user_id', $user->id);
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")->orWhere('tagline', 'like', "%{$search}%");
            });
        }

        $novels = $query->paginate(12)->withQueryString();

        return view('manage.novels.index', compact('novels', 'search'));
    }

    public function create()
    {
        $this->authorize('create', Novel::class);

        return view('manage.novels.create', ['novel' => new Novel(['status' => 'active'])]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Novel::class);

        $data = $this->validateNovel($request);
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['user_id'] = $request->user()->id;
        $data['cover_image'] = $this->resolveImage($request, null);

        $novel = Novel::create($data);

        return redirect()->route('novels.show', $novel)
            ->with('status', "Novel \"{$novel->title}\" dibuat. Tambahkan dunia tempat ceritanya berlangsung.");
    }

    public function show(Novel $novel)
    {
        $this->authorize('view', $novel);

        $novel->load('user');
        $worlds = $novel->worlds()
            ->withCount(['characters', 'benuas', 'negaras', 'provinsis', 'kotas', 'desas'])
            ->with('genres')
            ->latest()
            ->get();

        return view('manage.novels.show', compact('novel', 'worlds'));
    }

    public function edit(Novel $novel)
    {
        $this->authorize('update', $novel);

        return view('manage.novels.edit', compact('novel'));
    }

    public function update(Request $request, Novel $novel)
    {
        $this->authorize('update', $novel);

        $data = $this->validateNovel($request, $novel);
        $data['slug'] = $this->uniqueSlug($data['title'], $novel->id);
        $data['cover_image'] = $this->resolveImage($request, $novel->cover_image);

        $novel->update($data);

        return redirect()->route('novels.show', $novel)->with('status', "Novel \"{$novel->title}\" diperbarui.");
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
