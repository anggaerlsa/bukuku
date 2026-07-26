<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Comments on a book, and one level of replies. Reading happens on the book
 * page itself; this controller only writes, and every action is authorised
 * through CommentPolicy.
 */
class CommentController extends Controller
{
    public function store(Request $request, Book $book)
    {
        $this->authorize('create', [Comment::class, $book]);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            // A reply must attach to a TOP-LEVEL comment of THIS book. That
            // keeps the thread one level deep (a reply cannot name another
            // reply) and stops a reply being smuggled onto someone else's book.
            'parent_id' => [
                'nullable',
                Rule::exists('comments', 'id')->where('book_id', $book->id)->whereNull('parent_id'),
            ],
        ], [
            'body.required' => 'Tulis dulu komentarnya.',
            'body.max' => 'Komentar maksimal 5000 karakter.',
            'parent_id.exists' => 'Komentar yang dibalas tidak ada.',
        ]);

        $book->comments()->create([
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
