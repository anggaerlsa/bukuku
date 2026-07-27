<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Chapter;
use App\Models\Character;
use App\Models\LoreEntry;
use App\Models\Novel;
use App\Models\Organization;
use App\Models\World;
use App\Support\Hierarchy;
use Illuminate\Http\Request;

/**
 * One search box across everything an author has written — characters, places,
 * factions, lore articles and the manuscript itself.
 *
 * Without this, finding "Luminaris" means opening each module in turn; there
 * are a few hundred records now and that stops being reasonable.
 *
 * Scoping is done IN the queries, never by filtering results afterwards: the
 * readable world/novel ids are resolved once and every query is constrained to
 * them, so a match in someone else's private novel can never be counted, let
 * alone rendered.
 */
class SearchController extends Controller
{
    /** Never render more than this per group; the count still tells the truth. */
    private const PER_GROUP = 20;

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return view('manage.search.index', [
                'q' => '', 'groups' => [], 'total' => 0, 'truncated' => false,
            ]);
        }

        $novelIds = $this->readableNovelIds($request);
        $worldIds = World::whereIn('novel_id', $novelIds)->pluck('id');
        $bookIds = Book::whereIn('novel_id', $novelIds)->pluck('id');

        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';

        $groups = [];
        $total = 0;
        $truncated = false;

        $add = function (string $key, string $label, $query, callable $link) use (&$groups, &$total, &$truncated) {
            $count = (clone $query)->count();

            if ($count === 0) {
                return;
            }

            $rows = $query->limit(self::PER_GROUP)->get();
            $truncated = $truncated || $count > $rows->count();
            $total += $count;

            $groups[$key] = ['label' => $label, 'count' => $count, 'rows' => $rows, 'link' => $link];
        };

        // Column lists are per-table on purpose: characters have no
        // summary/description, they have role, backstory and the rest.
        $add('karakter', 'Karakter',
            Character::with('world')->whereIn('world_id', $worldIds)
                ->where($this->matching($like, [
                    'name', 'aliases', 'role', 'species', 'occupation',
                    'affiliation', 'appearance', 'personality', 'backstory', 'goals',
                ]))
                ->orderBy('name'),
            fn ($r) => route('characters.show', [$r->world, $r]));

        $add('lore', 'Artikel Lore',
            LoreEntry::with('world')->whereIn('world_id', $worldIds)
                ->where($this->matching($like, ['title', 'category', 'summary', 'body']))
                ->orderBy('title'),
            fn ($r) => route('lore.show', [$r->world, $r]));

        $add('organisasi', 'Organisasi',
            Organization::with('world')->whereIn('world_id', $worldIds)
                ->where($this->matching($like, [
                    'name', 'aliases', 'type', 'motto', 'summary', 'description', 'purpose', 'history',
                ]))
                ->orderBy('name'),
            fn ($r) => route('organizations.show', [$r->world, $r]));

        foreach (Hierarchy::keys() as $tier) {
            $model = Hierarchy::model($tier);

            $add('lokasi-' . $tier, 'Lokasi · ' . Hierarchy::label($tier),
                $model::with('world')->whereIn('world_id', $worldIds)
                    ->where($this->matching($like, [
                        'name', 'type', 'summary', 'description',
                        'geography', 'government', 'points_of_interest',
                    ]))
                    ->orderBy('name'),
                fn ($r) => route('locations.show', [$r->world, $tier, $r->id]));
        }

        // The manuscript. Body is searched too, so a half-remembered line can
        // be found — that is often the whole reason to search.
        $add('bab', 'Bab',
            Chapter::with('book')->whereIn('book_id', $bookIds)
                ->where($this->matching($like, ['title', 'body']))
                ->orderBy('book_id')->orderBy('position'),
            fn ($r) => route('chapters.show', [$r->book, $r]));

        return view('manage.search.index', compact('q', 'groups', 'total', 'truncated'));
    }

    /**
     * An OR-match across the named columns, wrapped in its own group so it
     * cannot leak past the world/novel scoping alongside it.
     *
     * @param  list<string>  $columns
     */
    private function matching(string $like, array $columns): callable
    {
        return function ($query) use ($like, $columns) {
            foreach ($columns as $i => $column) {
                $i === 0
                    ? $query->where($column, 'like', $like)
                    : $query->orWhere($column, 'like', $like);
            }
        };
    }

    /** Novels this user may read: their own, plus any that were shared. */
    private function readableNovelIds(Request $request)
    {
        return Novel::query()
            ->when(
                ! $request->user()->can('manage novels'),
                fn ($q) => $q->where(fn ($w) => $w->where('user_id', $request->user()->id)->orWhere('is_shared', true))
            )
            ->pluck('id');
    }
}
