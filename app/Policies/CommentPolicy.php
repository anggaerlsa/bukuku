<?php

namespace App\Policies;

use App\Models\Chapter;
use App\Models\Comment;
use App\Models\Novel;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Who may do what to a comment.
 *
 * Reading is not gated here — the book/chapter page already authorises `view`
 * on the novel, so anyone who can open the page sees its comments. What this
 * class decides is writing:
 *
 *  - anyone who can READ the surface may post (so members of a shared novel
 *    can talk to it, and a private novel's comments stay between the owner and
 *    nobody),
 *  - only the author may edit their own comment,
 *  - the author may delete their own, and the novel's owner may delete ANY
 *    comment on their work — as may an admin who manages novels.
 *
 * Every rule ultimately anchors on the same novel, whether the comment sits
 * on a Book or a Chapter.
 */
class CommentPolicy
{
    /** Posting a comment or a reply: gated on being able to read the surface. */
    public function create(User $user, Model $commentable): bool
    {
        $novel = $this->novelOf($commentable);

        return $novel !== null && $user->can('view', $novel);
    }

    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        if ($user->id === $comment->user_id) {
            return true;
        }

        $novel = $this->novelOf($comment->commentable);

        return $novel !== null && $user->can('update', $novel);
    }

    /** The novel a Book or Chapter belongs to. */
    private function novelOf(?Model $commentable): ?Novel
    {
        if ($commentable instanceof Chapter) {
            return $commentable->book?->novel;
        }

        // A Book (or anything else exposing ->novel).
        return $commentable?->novel;
    }
}
