<?php

namespace App\Policies;

use App\Models\Novel;
use App\Models\User;

class NovelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin', 'author']);
    }

    /**
     * Reading is also open to every member once the author shares the novel.
     * Only `view` widens — update/delete below stay with the owner, so a
     * shared novel is strictly read-only for everybody else.
     */
    public function view(User $user, Novel $novel): bool
    {
        return $user->can('manage novels')
            || $user->id === $novel->user_id
            || ($novel->is_shared && $user->hasAnyRole(['superadmin', 'admin', 'author']));
    }

    public function create(User $user): bool
    {
        return $user->can('create novels');
    }

    public function update(User $user, Novel $novel): bool
    {
        if ($user->can('manage novels')) {
            return true;
        }

        return $user->id === $novel->user_id && $user->can('edit own novels');
    }

    public function delete(User $user, Novel $novel): bool
    {
        if ($user->can('manage novels')) {
            return true;
        }

        return $user->id === $novel->user_id && $user->can('delete own novels');
    }
}
