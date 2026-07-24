<?php

namespace App\Support;

use App\Models\Character;
use App\Models\LoreEntry;
use App\Models\Organization;
use App\Models\World;
use Illuminate\Database\Eloquent\Model;

/**
 * Everything that can own a gallery, keyed by the same short alias used in the
 * morph map: "character" plus one key per location tier. Both kinds of record
 * carry `world_id`, so lookups scope cleanly to a single world.
 */
class ImageOwners
{
    /** @return array<string, class-string<Model>> */
    public static function types(): array
    {
        return [
            'character' => Character::class,
            'organization' => Organization::class,
            'lore' => LoreEntry::class,
        ] + Hierarchy::MODELS;
    }

    public static function isType(?string $type): bool
    {
        return $type !== null && isset(self::types()[$type]);
    }

    /** Look up a gallery owner inside a world; 404s on anything unknown. */
    public static function resolve(World $world, string $type, string $id): Model
    {
        abort_unless(self::isType($type), 404);

        $model = self::types()[$type];

        return $model::where('world_id', $world->id)->findOrFail($id);
    }

    /** Where the owner's own page lives, so uploads can redirect back to it. */
    public static function showRoute(World $world, Model $owner): string
    {
        return match (true) {
            $owner instanceof Character => route('characters.show', [$world, $owner]),
            $owner instanceof Organization => route('organizations.show', [$world, $owner]),
            $owner instanceof LoreEntry => route('lore.show', [$world, $owner]),
            default => route('locations.show', [$world, $owner->tierKey(), $owner->id]),
        };
    }
}
