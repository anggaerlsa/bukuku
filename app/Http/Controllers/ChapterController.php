<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Chapter;
use Illuminate\Http\Request;

/**
 * Bab — one chapter of a volume, and the page a novel is actually read on.
 *
 * Authorises against the book's NOVEL, so a shared novel can be read straight
 * through by any member while staying editable only by its owner.
 */
class ChapterController extends Controller
{
    public function index(Book $book)
    {
        // The book page already is the table of contents.
        return redirect()->route('books.show', $book);
    }

    public function create(Book $book)
    {
        $this->authorize('update', $book->novel);

        return view('manage.chapters.create', [
            'book' => $book,
            'chapter' => new Chapter(['position' => $book->nextPosition()]),
        ]);
    }

    public function store(Request $request, Book $book)
    {
        $this->authorize('update', $book->novel);

        $data = $this->validateChapter($request);
        $data['position'] = $data['position'] ?? $book->nextPosition();

        $chapter = $book->chapters()->create($data);

        return redirect()->route('chapters.show', [$book, $chapter])
            ->with('status', "Bab \"{$chapter->title}\" ditambahkan.");
    }

    public function show(Book $book, Chapter $chapter)
    {
        $this->authorize('view', $book->novel);

        $book->load('novel');

        return view('manage.chapters.show', [
            'book' => $book,
            'chapter' => $chapter,
            'previous' => $chapter->previous(),
            'next' => $chapter->next(),
        ]);
    }

    public function edit(Book $book, Chapter $chapter)
    {
        $this->authorize('update', $book->novel);

        return view('manage.chapters.edit', compact('book', 'chapter'));
    }

    public function update(Request $request, Book $book, Chapter $chapter)
    {
        $this->authorize('update', $book->novel);

        $data = $this->validateChapter($request);
        $data['position'] = $data['position'] ?? $chapter->position;

        $chapter->update($data);

        return redirect()->route('chapters.show', [$book, $chapter])
            ->with('status', "Bab \"{$chapter->title}\" diperbarui.");
    }

    public function destroy(Book $book, Chapter $chapter)
    {
        $this->authorize('update', $book->novel);

        $title = $chapter->title;
        $chapter->delete();

        return redirect()->route('books.show', $book)
            ->with('status', "Bab \"{$title}\" dihapus.");
    }

    private function validateChapter(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'position' => 'nullable|integer|min:1|max:100000',
            'published_at' => 'nullable|date',
            'source_url' => 'nullable|url|max:2048',
        ], [
            'title.required' => 'Bab perlu judul.',
            'position.integer' => 'Urutan harus berupa angka.',
        ]);
    }
}
