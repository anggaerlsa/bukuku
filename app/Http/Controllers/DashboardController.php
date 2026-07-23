<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Novel;
use App\Models\User;
use App\Models\World;
use App\Support\Hierarchy;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $canManageAll = $user->can('manage worlds');

        $worldIds = World::query()
            ->when(! $canManageAll, fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $locations = 0;
        foreach (Hierarchy::keys() as $tier) {
            $locations += Hierarchy::model($tier)::whereIn('world_id', $worldIds)->count();
        }

        $novelQuery = Novel::query()
            ->when(! $user->can('manage novels'), fn ($q) => $q->where('user_id', $user->id));

        $stats = [
            'novels' => (clone $novelQuery)->count(),
            'worlds' => $worldIds->count(),
            'characters' => Character::whereIn('world_id', $worldIds)->count(),
            'locations' => $locations,
            'users' => User::count(),
        ];

        $novels = (clone $novelQuery)->withCount('worlds')->latest()->take(4)->get();

        $worlds = World::query()
            ->when(! $canManageAll, fn ($q) => $q->where('user_id', $user->id))
            ->withCount(['characters', 'benuas', 'negaras', 'provinsis', 'kotas', 'desas'])
            ->with('genres', 'user', 'novel')
            ->latest()
            ->take(6)
            ->get();

        return view('dashboard', compact('stats', 'novels', 'worlds', 'canManageAll'));
    }
}
