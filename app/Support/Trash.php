<?php

namespace App\Support;

use App\Models\Book;
use App\Models\Chapter;
use App\Models\Character;
use App\Models\LoreEntry;
use App\Models\Novel;
use App\Models\Organization;
use App\Models\World;

/**
 * What the Sampah page knows how to hold.
 *
 * The `type` in a URL is one of these keys and never a class name, so a
 * request can only ever reach a model listed here.
 */
class Trash
{
    /**
     * key => [model, singular label, the relation naming its parent]
     *
     * `parent` is how we tell a deletion's ROOT from its fallout: a character
     * whose world is also in the bin went down with that world, so the bin
     * lists the world alone and restoring it brings the character back.
     */
    public const TYPES = [
        'novel' => [Novel::class, 'Novel', null],
        'dunia' => [World::class, 'Dunia', 'novel'],
        'buku' => [Book::class, 'Buku', 'novel'],
        'bab' => [Chapter::class, 'Bab', 'book'],
        'karakter' => [Character::class, 'Karakter', 'world'],
        'organisasi' => [Organization::class, 'Organisasi', 'world'],
        'lore' => [LoreEntry::class, 'Artikel Lore', 'world'],
    ];

    /** Location tiers share a shape, so they are folded in from Hierarchy. */
    public static function types(): array
    {
        $types = self::TYPES;

        foreach (Hierarchy::keys() as $tier) {
            $types[$tier] = [Hierarchy::model($tier), Hierarchy::label($tier), 'world'];
        }

        return $types;
    }

    public static function isType(string $type): bool
    {
        return array_key_exists($type, self::types());
    }

    /** @return class-string<\Illuminate\Database\Eloquent\Model> */
    public static function model(string $type): string
    {
        return self::types()[$type][0];
    }

    public static function label(string $type): string
    {
        return self::types()[$type][1];
    }

    /** Name of the relation holding this type's parent, or null at the top. */
    public static function parentRelation(string $type): ?string
    {
        return self::types()[$type][2];
    }

    /** The column carrying a record's display name, per type. */
    public static function titleColumn(string $type): string
    {
        return in_array($type, ['novel', 'buku', 'bab', 'lore'], true) ? 'title' : 'name';
    }
}
