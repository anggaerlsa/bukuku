<?php

namespace App\Support;

use App\Models\Benua;
use App\Models\Desa;
use App\Models\Kota;
use App\Models\Negara;
use App\Models\Provinsi;

/**
 * Central registry for the location hierarchy. Each tier is a separate table
 * and model; this class is the single source of truth for their order,
 * labels, tables, and parent/child relationships.
 */
class Hierarchy
{
    /** @var array<string, class-string> ordered top → bottom */
    public const MODELS = [
        'benua' => Benua::class,
        'negara' => Negara::class,
        'provinsi' => Provinsi::class,
        'kota' => Kota::class,
        'desa' => Desa::class,
    ];

    public const LABELS = [
        'benua' => 'Benua',
        'negara' => 'Negara',
        'provinsi' => 'Provinsi',
        'kota' => 'Kota',
        'desa' => 'Desa',
    ];

    public const TABLES = [
        'benua' => 'benuas',
        'negara' => 'negaras',
        'provinsi' => 'provinsis',
        'kota' => 'kotas',
        'desa' => 'desas',
    ];

    public const SUGGESTIONS = [
        'benua' => 'Benua, Daratan, Dunia…',
        'negara' => 'Kerajaan, Kekaisaran, Republik, Kesultanan…',
        'provinsi' => 'Kadipaten, Dukedom, Prefektur, Provinsi…',
        'kota' => 'Metropolis, Kota Pelabuhan, Kota Benteng…',
        'desa' => 'Dusun, Sub-distrik, Permukiman, Desa…',
    ];

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::MODELS);
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        return self::LABELS;
    }

    public static function model(string $tier): ?string
    {
        return self::MODELS[$tier] ?? null;
    }

    public static function label(string $tier): string
    {
        return self::LABELS[$tier] ?? ucfirst($tier);
    }

    public static function table(string $tier): ?string
    {
        return self::TABLES[$tier] ?? null;
    }

    public static function suggestion(string $tier): string
    {
        return self::SUGGESTIONS[$tier] ?? 'mis. Kadipaten, Kerajaan, Metropolis…';
    }

    public static function isTier(?string $tier): bool
    {
        return $tier !== null && isset(self::MODELS[$tier]);
    }

    /** The tier one step up (null for the top tier). */
    public static function parent(string $tier): ?string
    {
        $keys = self::keys();
        $i = array_search($tier, $keys, true);

        return ($i !== false && $i > 0) ? $keys[$i - 1] : null;
    }

    /** The tier one step down (null for the bottom tier). */
    public static function child(string $tier): ?string
    {
        $keys = self::keys();
        $i = array_search($tier, $keys, true);

        return ($i !== false && isset($keys[$i + 1])) ? $keys[$i + 1] : null;
    }

    /** Foreign-key column pointing at this tier's parent (e.g. negara → benua_id). */
    public static function parentForeignKey(string $tier): ?string
    {
        $parent = self::parent($tier);

        return $parent ? $parent . '_id' : null;
    }

    /**
     * Map of each tier to the tier its parent must be (top tier omitted).
     *
     * @return array<string, string>
     */
    public static function parentMap(): array
    {
        $map = [];
        foreach (self::keys() as $tier) {
            if ($parent = self::parent($tier)) {
                $map[$tier] = $parent;
            }
        }

        return $map;
    }
}
