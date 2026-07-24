<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Novel;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Buku — a volume of a novel, holding the chapters that are actually read.
 *
 * Everything here authorises against the parent NOVEL, never against the book
 * itself: `view` widens once the author shares the novel, while update and
 * delete stay with the owner. A shared novel is therefore readable end to end
 * and editable by nobody else, with no extra guards of its own.
 */
class BookController extends Controller
{
    public function index(Request $request)
    {
        $novels = $this->availableNovels($request);

        $query = Book::with('novel')->withCount('chapters')
            ->whereIn('novel_id', $this->readableNovelIds($request))
            ->orderBy('novel_id')->orderBy('position');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where('title', 'like', "%{$search}%");
        }

        $novelId = $request->query('novel');
        if ($novelId && $novels->contains('id', (int) $novelId)) {
            $query->where('novel_id', (int) $novelId);
        } else {
            $novelId = null;
        }

        $books = $query->paginate(12)->withQueryString();

        return view('manage.books.index', compact('books', 'search', 'novels', 'novelId'));
    }

    public function create(Request $request)
    {
        $novels = $this->availableNovels($request);

        abort_if($novels->isEmpty(), 403, 'Belum ada novel yang bisa diisi buku.');

        return view('manage.books.create', [
            'book' => new Book(['status' => 'draft', 'novel_id' => $request->query('novel')]),
            'novels' => $novels,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateBook($request);

        $novel = Novel::findOrFail($data['novel_id']);
        $this->authorize('update', $novel);

        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['cover_image'] = $this->resolveImage($request, null);
        $data['position'] = (int) Book::where('novel_id', $novel->id)->max('position') + 1;

        $book = Book::create($data);

        return redirect()->route('books.show', $book)
            ->with('status', "Buku \"{$book->title}\" dibuat. Sekarang tambahkan babnya.");
    }

    public function show(Book $book)
    {
        $this->authorize('view', $book->novel);

        $book->load('novel');

        // The table of contents: bodies are deliberately left out, a volume
        // can run to hundreds of thousands of words.
        $chapters = $book->chapters()
            ->get(['id', 'book_id', 'title', 'position', 'word_count', 'published_at']);

        return view('manage.books.show', compact('book', 'chapters'));
    }

    public function edit(Book $book)
    {
        $this->authorize('update', $book->novel);

        return view('manage.books.edit', [
            'book' => $book,
            'novels' => $this->availableNovels(request()),
        ]);
    }

    public function update(Request $request, Book $book)
    {
        $this->authorize('update', $book->novel);

        $data = $this->validateBook($request);

        $novel = Novel::findOrFail($data['novel_id']);
        $this->authorize('update', $novel);

        $data['slug'] = $this->uniqueSlug($data['title'], $book->id);
        $data['cover_image'] = $this->resolveImage($request, $book->cover_image);

        $book->update($data);

        return redirect()->route('books.show', $book)
            ->with('status', "Buku \"{$book->title}\" diperbarui.");
    }

    public function destroy(Book $book)
    {
        $this->authorize('update', $book->novel);

        // Deleting a volume takes every chapter in it, which is the author's
        // written work. Make that an explicit choice, the same way a novel
        // refuses to vanish while it still has worlds.
        if (($count = $book->chapters()->count()) > 0) {
            return back()->with('error', "Buku ini masih berisi {$count} bab. Hapus babnya lebih dahulu kalau memang mau dilenyapkan.");
        }

        Uploads::delete($book->cover_image);

        $novel = $book->novel;
        $title = $book->title;
        $book->delete();

        return redirect()->route('novels.show', $novel)
            ->with('status', "Buku \"{$title}\" dihapus.");
    }

    /** Novels this user may edit — the ones a book may be filed under. */
    private function availableNovels(Request $request)
    {
        return Novel::query()
            ->when(! $request->user()->can('manage novels'), fn ($q) => $q->where('user_id', $request->user()->id))
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    /** Novels this user may read, which includes the ones shared with them. */
    private function readableNovelIds(Request $request): array
    {
        return Novel::query()
            ->when(
                ! $request->user()->can('manage novels'),
                fn ($q) => $q->where('user_id', $request->user()->id)->orWhere('is_shared', true)
            )
            ->pluck('id')->all();
    }

    private function validateBook(Request $request): array
    {
        $allowed = $this->availableNovels($request)->pluck('id')->all();

        return $request->validate([
            'novel_id' => ['required', Rule::in($allowed)],
            'title' => 'required|string|max:255',
            'synopsis' => 'nullable|string',
            'status' => 'required|in:' . implode(',', array_keys(Book::statuses())),
            'cover_image' => 'nullable|image|max:4096',
            'cover_url' => 'nullable|url|max:2048',
        ], [
            'novel_id.required' => 'Pilih novel tempat buku ini bernaung.',
            'novel_id.in' => 'Novel itu bukan milikmu.',
            'cover_image.image' => 'Sampul harus berupa gambar.',
            'cover_image.max' => 'Sampul maksimal 4MB.',
        ]);
    }

    private function resolveImage(Request $request, ?string $current): ?string
    {
        if ($request->hasFile('cover_image')) {
            Uploads::delete($current);

            return Uploads::store($request->file('cover_image'), 'buku');
        }

        if ($url = trim((string) $request->input('cover_url', ''))) {
            return $url;
        }

        return $current;
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'buku';
        $slug = $base;
        $i = 1;

        while (
            Book::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }
}
