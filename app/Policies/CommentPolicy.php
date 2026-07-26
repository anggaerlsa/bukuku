<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\Comment;
use App\Models\User;

/**
 * Who may do what to a comment.
 *
 * Reading is not gated here — the book page already authorises `view` on the
 * novel, so anyone who can open the page sees its comments. What this class
 * decides is writing:
 *
 *  - anyone who can READ the book may post (so members of a shared novel can
 *    talk to it, and a private novel's comments stay between the owner and
 *    nobody),
 *  - only the author may edit their own comment,
 *  - the author may delete their own, and the novel's owner may delete ANY
 *    comment on their book — it is their work — as may an admin who manages
 *    novels.
 */
class CommentPolicy
{
    /** Posting a comment or a reply: gated on being able to read the book. */
    public function create(User $user, Book $book): bool
    {
        return $user->can('view', $book->novel);
    }

    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id
            || $user->can('update', $comment->book->novel);
    }
}
