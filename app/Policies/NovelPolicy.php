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

    public function view(User $user, Novel $novel): bool
    {
        return $user->can('manage novels') || $user->id === $novel->user_id;
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
