<?php

namespace App\Models;

use App\Support\Hierarchy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * An attribute the author invented for one world — "Tingkat Mana", "Klearans
 * Keamanan", "Kasta". Each world defines its own, so a sci-fi world and a
 * fantasy world never share a schema.
 */
class CustomField extends Model
{
    protected $fillable = [
        'world_id',
        'name',
        'applies_to',
        'type',
        'options',
        'hint',
        'position',
    ];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    /** @var array<string, string> */
    public const TYPES = [
        'text' => 'Teks singkat',
        'textarea' => 'Teks panjang',
        'number' => 'Angka',
        'select' => 'Pilihan',
        'boolean' => 'Ya / Tidak',
    ];

    /**
     * What a field can be attached to: characters, every location, or one
     * specific tier. Keys double as morph aliases where they overlap.
     *
     * @return array<string, string>
     */
    public static function targets(): array
    {
        $targets = [
            'character' => 'Karakter',
            'organization' => 'Organisasi',
            'lore' => 'Artikel Lore',
            'location' => 'Lokasi (semua tingkat)',
        ];

        foreach (Hierarchy::labels() as $tier => $label) {
            $targets[$tier] = "Lokasi: {$label} saja";
        }

        return $targets;
    }

    public static function isTarget(?string $target): bool
    {
        return $target !== null && isset(static::targets()[$target]);
    }

    public static function targetLabel(?string $target): string
    {
        return static::targets()[$target] ?? (string) $target;
    }

    public function typeLabel(): string
    {
        return static::TYPES[$this->type] ?? $this->type;
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    /** Choices for a `select` field, one per line. */
    public function optionList(): Collection
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $this->options))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values();
    }

    /** Turn a stored value into something readable. */
    public function display(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->type === 'boolean' ? ($value === '1' ? 'Ya' : 'Tidak') : $value;
    }

    /** Fields of a world that apply to the given target, in author order. */
    public function scopeForTarget($query, string|array $targets)
    {
        return $query->whereIn('applies_to', (array) $targets)
            ->orderBy('position')
            ->orderBy('id');
    }
}
