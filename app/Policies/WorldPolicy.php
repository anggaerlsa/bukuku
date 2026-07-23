<?php

namespace App\Policies;

use App\Models\User;
use App\Models\World;

class WorldPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin', 'author']);
    }

    public function view(User $user, World $world): bool
    {
        return $user->can('manage worlds') || $user->id === $world->user_id;
    }

    public function create(User $user): bool
    {
        return $user->can('create worlds');
    }

    public function update(User $user, World $world): bool
    {
        if ($user->can('manage worlds')) {
            return true;
        }

        return $user->id === $world->user_id && $user->can('edit own worlds');
    }

    public function delete(User $user, World $world): bool
    {
        if ($user->can('manage worlds')) {
            return true;
        }

        return $user->id === $world->user_id && $user->can('delete own worlds');
    }
}
