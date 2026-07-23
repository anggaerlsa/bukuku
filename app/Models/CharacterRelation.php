<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One tie between two characters, read as:
 * "{relatedCharacter} is the {type} of {character}".
 *
 * Only one row is stored per tie. The other character's page shows the same
 * row through the inverse type (anak ⇄ orang-tua), so the two directions are
 * always consistent by construction.
 */
class CharacterRelation extends Model
{
    protected $fillable = [
        'world_id',
        'character_id',
        'related_character_id',
        'type',
        'note',
    ];

    /**
     * Every tie type, with the label seen from the other side and the group it
     * is offered under in the picker.
     *
     * @var array<string, array{label: string, inverse: string, group: string}>
     */
    public const TYPES = [
        'orang-tua' => ['label' => 'Orang Tua', 'inverse' => 'anak', 'group' => 'Keluarga'],
        'anak' => ['label' => 'Anak', 'inverse' => 'orang-tua', 'group' => 'Keluarga'],
        'saudara' => ['label' => 'Saudara', 'inverse' => 'saudara', 'group' => 'Keluarga'],
        'pasangan' => ['label' => 'Pasangan', 'inverse' => 'pasangan', 'group' => 'Keluarga'],
        'kerabat' => ['label' => 'Kerabat', 'inverse' => 'kerabat', 'group' => 'Keluarga'],
        'mentor' => ['label' => 'Mentor', 'inverse' => 'murid', 'group' => 'Hubungan'],
        'murid' => ['label' => 'Murid', 'inverse' => 'mentor', 'group' => 'Hubungan'],
        'teman' => ['label' => 'Teman', 'inverse' => 'teman', 'group' => 'Hubungan'],
        'sekutu' => ['label' => 'Sekutu', 'inverse' => 'sekutu', 'group' => 'Hubungan'],
        'musuh' => ['label' => 'Musuh', 'inverse' => 'musuh', 'group' => 'Hubungan'],
        'atasan' => ['label' => 'Atasan', 'inverse' => 'bawahan', 'group' => 'Hubungan'],
        'bawahan' => ['label' => 'Bawahan', 'inverse' => 'atasan', 'group' => 'Hubungan'],
    ];

    /** @return list<string> */
    public static function typeKeys(): array
    {
        return array_keys(self::TYPES);
    }

    public static function isType(?string $type): bool
    {
        return $type !== null && isset(self::TYPES[$type]);
    }

    public static function label(?string $type): string
    {
        return self::TYPES[$type]['label'] ?? ucfirst((string) $type);
    }

    /** The type as seen from the other character's page. */
    public static function inverse(?string $type): string
    {
        return self::TYPES[$type]['inverse'] ?? (string) $type;
    }

    public static function group(?string $type): string
    {
        return self::TYPES[$type]['group'] ?? 'Hubungan';
    }

    /**
     * Types keyed by their picker group, e.g. ['Keluarga' => ['anak' => 'Anak', …]].
     *
     * @return array<string, array<string, string>>
     */
    public static function grouped(): array
    {
        $out = [];
        foreach (self::TYPES as $key => $meta) {
            $out[$meta['group']][$key] = $meta['label'];
        }

        return $out;
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    /** The character whose page this tie was created from. */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /** The other character — the one holding the role named by `type`. */
    public function relatedCharacter(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'related_character_id');
    }

    public function typeLabel(): string
    {
        return self::label($this->type);
    }

    /** True when this row already records the given tie, in either direction. */
    public function scopeMatchingPair($query, int $characterId, int $otherId, string $type)
    {
        return $query->where(function ($q) use ($characterId, $otherId, $type) {
            $q->where(function ($inner) use ($characterId, $otherId, $type) {
                $inner->where('character_id', $characterId)
                    ->where('related_character_id', $otherId)
                    ->where('type', $type);
            })->orWhere(function ($inner) use ($characterId, $otherId, $type) {
                $inner->where('character_id', $otherId)
                    ->where('related_character_id', $characterId)
                    ->where('type', self::inverse($type));
            });
        });
    }
}
