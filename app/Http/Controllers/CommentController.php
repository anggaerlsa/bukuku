<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Chapter;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Comments on a Book or a Chapter, and one level of replies. Reading happens
 * on the book/chapter page itself; this controller only writes, and every
 * action is authorised through CommentPolicy.
 */
class CommentController extends Controller
{
    /** A comment on a whole volume. */
    public function store(Request $request, Book $book)
    {
        return $this->post($request, $book);
    }

    /** A comment on a single chapter — the per-episode reading page. */
    public function storeChapter(Request $request, Chapter $chapter)
    {
        return $this->post($request, $chapter);
    }

    /** Shared write path; $commentable is a Book or a Chapter. */
    private function post(Request $request, Model $commentable)
    {
        $this->authorize('create', [Comment::class, $commentable]);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            // A reply must attach to a TOP-LEVEL comment of THIS SAME surface.
            // That keeps the thread one level deep (a reply cannot name another
            // reply) and stops a reply being smuggled onto a different book or
            // chapter.
            'parent_id' => [
                'nullable',
                Rule::exists('comments', 'id')
                    ->where('commentable_type', $commentable->getMorphClass())
                    ->where('commentable_id', $commentable->getKey())
                    ->whereNull('parent_id'),
            ],
        ], [
            'body.required' => 'Tulis dulu komentarnya.',
            'body.max' => 'Komentar maksimal 5000 karakter.',
            'parent_id.exists' => 'Komentar yang dibalas tidak ada.',
        ]);

        $commentable->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body' => $data['body'],
        ]);

        return back()->with('status', filled($data['parent_id'] ?? null) ? 'Balasan terkirim.' : 'Komentar terkirim.');
    }

    public function update(Request $request, Comment $comment)
    {
        $this->authorize('update', $comment);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ], [
            'body.required' => 'Komentar tidak boleh kosong.',
            'body.max' => 'Komentar maksimal 5000 karakter.',
        ]);

        $comment->update([
            'body' => $data['body'],
            'edited_at' => now(),
        ]);

        return back()->with('status', 'Komentar diperbarui.');
    }

    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);

        // A top-level comment carries its replies down with it (DB cascade);
        // the view warns before this is reached.
        $comment->delete();

        return back()->with('status', 'Komentar dihapus.');
    }
}
