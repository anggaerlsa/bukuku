<?php

namespace App\Support;

use App\Models\LocationNode;
use App\Models\World;
use Illuminate\Support\Collection;

/**
 * Locations live in five separate tables, so anything that needs to point at
 * "a location" (character origin/residence, …) refers to it by a `tier:id`
 * token. This class builds those tokens, resolves them back to a model, and
 * assembles the grouped picker options — loading each tier exactly once.
 */
class LocationLookup
{
    /**
     * Every location of a world, one Collection per tier keyed by id.
     * Only the columns needed to render a picker are selected.
     *
     * @return array<string, Collection<int, LocationNode>>
     */
    public static function tree(World $world): array
    {
        $out = [];

        foreach (Hierarchy::keys() as $tier) {
            $columns = ['id', 'world_id', 'name', 'type'];
            if ($fk = Hierarchy::parentForeignKey($tier)) {
                $columns[] = $fk;
            }

            $out[$tier] = $world->{Hierarchy::table($tier)}()
                ->orderBy('name')
                ->get($columns)
                ->keyBy('id');
        }

        return $out;
    }

    /**
     * Picker options grouped by tier: [tier => [['value' => 'kota:3',
     * 'label' => 'Aethel › Valdoria › Baruna', 'name' => 'Baruna'], …]].
     * Tiers with no rows are omitted.
     *
     * @return array<string, list<array{value: string, label: string, name: string}>>
     */
    public static function options(World $world): array
    {
        $tree = self::tree($world);
        $out = [];

        foreach ($tree as $tier => $nodes) {
            if ($nodes->isEmpty()) {
                continue;
            }

            $out[$tier] = $nodes->map(fn (LocationNode $node) => [
                'value' => self::token($tier, $node->id),
                'label' => self::pathIn($tree, $tier, $node->id),
                'name' => $node->name,
            ])->values()->all();
        }

        return $out;
    }

    /** Fetch the node a token points at, or null when it is empty/invalid/foreign. */
    public static function resolve(World $world, ?string $token): ?LocationNode
    {
        [$tier, $id] = self::parse($token);

        if ($tier === null) {
            return null;
        }

        $model = Hierarchy::model($tier);

        return $model::where('world_id', $world->id)->find($id);
    }

    /**
     * Split a `tier:id` token; returns [null, null] when malformed.
     *
     * @return array{0: ?string, 1: ?int}
     */
    public static function parse(?string $token): array
    {
        $token = trim((string) $token);

        if (! str_contains($token, ':')) {
            return [null, null];
        }

        [$tier, $id] = explode(':', $token, 2);

        if (! Hierarchy::isTier($tier) || ! ctype_digit($id) || (int) $id < 1) {
            return [null, null];
        }

        return [$tier, (int) $id];
    }

    public static function token(?string $tier, int|string|null $id): ?string
    {
        return ($tier && $id) ? "{$tier}:{$id}" : null;
    }

    /** Token for an already-loaded node, e.g. to preselect it in a picker. */
    public static function tokenFor(?LocationNode $node): ?string
    {
        return $node ? self::token($node->tierKey(), $node->id) : null;
    }

    /** Ancestor trail built from an in-memory tree — no extra queries. */
    private static function pathIn(array $tree, string $tier, int|string $id): string
    {
        $names = [];
        $cursorTier = $tier;
        $cursor = $tree[$tier][$id] ?? null;

        while ($cursor) {
            array_unshift($names, $cursor->name);

            $parentTier = Hierarchy::parent($cursorTier);
            $fk = Hierarchy::parentForeignKey($cursorTier);
            $parentId = $fk ? $cursor->{$fk} : null;

            $cursor = ($parentTier && $parentId) ? ($tree[$parentTier][$parentId] ?? null) : null;
            $cursorTier = $parentTier;
        }

        return implode(' › ', $names);
    }
}
