<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Novel;
use App\Models\World;
use App\Support\Trash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Sampah — everything the author has deleted but not yet destroyed.
 *
 * Only the ROOT of each deletion is listed. Binning a world puts its 42
 * characters in the bin too, but showing all 43 rows would bury the one entry
 * that matters and invite restoring a character into a world that is still
 * gone. So a record whose parent is also in the bin is hidden here: restore
 * the world and everything comes back with it.
 *
 * Every action re-checks the owning novel, so the bin can never become a way
 * around the permissions on the records themselves.
 */
class TrashController extends Controller
{
    public function index(Request $request)
    {
        $groups = [];

        foreach (array_keys(Trash::types()) as $type) {
            $rows = $this->rootsOf($request, $type);

            if ($rows->isNotEmpty()) {
                $groups[$type] = $rows;
            }
        }

        return view('manage.trash.index', [
            'groups' => $groups,
            'total' => collect($groups)->sum(fn ($g) => $g->count()),
        ]);
    }

    public function restore(Request $request, string $type, int $id)
    {
        $record = $this->findTrashed($request, $type, $id);

        $record->restore();

        return back()->with('status', Trash::label($type) . " \"{$this->titleOf($record, $type)}\" dipulihkan.");
    }

    public function destroy(Request $request, string $type, int $id)
    {
        $record = $this->findTrashed($request, $type, $id);
        $title = $this->titleOf($record, $type);

        // purge() walks children in PHP so every forceDeleted hook runs and
        // cleans up its own uploads; a bare DB cascade would strand the files.
        method_exists($record, 'purge') ? $record->purge() : $record->forceDelete();

        return back()->with('status', Trash::label($type) . " \"{$title}\" dihapus permanen.");
    }

    /**
     * Trashed records of one type that this user may act on, excluding any
     * whose parent is also in the bin.
     */
    private function rootsOf(Request $request, string $type)
    {
        $model = Trash::model($type);

        return $model::onlyTrashed()
            ->latest('deleted_at')
            ->get()
            ->filter(fn (Model $record) => $this->novelOf($record) !== null
                && $request->user()->can('update', $this->novelOf($record)))
            ->filter(fn (Model $record) => ! $this->parentIsTrashed($record, $type))
            ->values();
    }

    private function parentIsTrashed(Model $record, string $type): bool
    {
        $relation = Trash::parentRelation($type);

        if ($relation === null) {
            return false;
        }

        $parent = $record->{$relation}()->withTrashed()->first();

        return $parent !== null && $parent->trashed();
    }

    /** The novel a trashed record belongs to — the anchor for permissions. */
    private function novelOf(Model $record): ?Novel
    {
        if ($record instanceof Novel) {
            return $record;
        }

        if ($record instanceof Chapter) {
            return $record->book()->withTrashed()->first()?->novel()->withTrashed()->first();
        }

        if ($record instanceof World) {
            return $record->novel()->withTrashed()->first();
        }

        // Books hang off a novel; everything else hangs off a world.
        if (method_exists($record, 'novel')) {
            return $record->novel()->withTrashed()->first();
        }

        return $record->world()->withTrashed()->first()?->novel()->withTrashed()->first();
    }

    private function findTrashed(Request $request, string $type, int $id): Model
    {
        abort_unless(Trash::isType($type), 404);

        $record = Trash::model($type)::onlyTrashed()->findOrFail($id);

        $novel = $this->novelOf($record);
        abort_if($novel === null, 404);

        $this->authorize('update', $novel);

        return $record;
    }

    private function titleOf(Model $record, string $type): string
    {
        return (string) $record->{Trash::titleColumn($type)};
    }
}
