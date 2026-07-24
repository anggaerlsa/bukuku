<?php

namespace App\Models;

use App\Models\Concerns\HasCustomFields;
use App\Models\Concerns\HasImages;
use App\Support\Hierarchy;
use App\Support\Uploads;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * Shared behaviour for every location tier. Each concrete tier (Benua,
 * Negara, …) lives in its own table but shares these lore fields and helpers.
 */
abstract class LocationNode extends Model
{
    use HasCustomFields;
    use HasImages;

    protected $guarded = ['id'];

    /** Column holding the record's cover picture (see HasImages). */
    public function coverColumn(): string
    {
        return 'map_image';
    }

    /** Picks up fields defined for every location plus this tier's own. */
    public function customFieldTargets(): array
    {
        return ['location', static::TIER];
    }

    protected static function booted(): void
    {
        // A location that goes away must not leave characters pointing at a
        // row that no longer exists. Scoped to this exact node — never global.
        static::deleting(function (LocationNode $node) {
            $morph = $node->getMorphClass();

            Character::where('origin_type', $morph)->where('origin_id', $node->id)
                ->update(['origin_type' => null, 'origin_id' => null]);

            Character::where('residence_type', $morph)->where('residence_id', $node->id)
                ->update(['residence_type' => null, 'residence_id' => null]);

            Organization::where('headquarters_type', $morph)->where('headquarters_id', $node->id)
                ->update(['headquarters_type' => null, 'headquarters_id' => null]);
        });
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    /** Characters who hail from this place. */
    public function nativeCharacters(): MorphMany
    {
        return $this->morphMany(Character::class, 'origin');
    }

    /** Characters who currently live here. */
    public function residentCharacters(): MorphMany
    {
        return $this->morphMany(Character::class, 'residence');
    }

    /** Organisations based here. */
    public function basedOrganizations(): MorphMany
    {
        return $this->morphMany(Organization::class, 'headquarters');
    }

    public function tierKey(): string
    {
        return static::TIER;
    }

    public function tierLabel(): string
    {
        return Hierarchy::label(static::TIER);
    }

    public function parentTierKey(): ?string
    {
        return Hierarchy::parent(static::TIER);
    }

    public function childTierKey(): ?string
    {
        return Hierarchy::child(static::TIER);
    }

    /**
     * The label to show: the author's custom type (in-world identity) if set,
     * otherwise the canonical tier name. The tier itself never changes.
     */
    public function displayLabel(): string
    {
        return $this->type ?: $this->tierLabel();
    }

    /** Loaded child nodes (empty for the bottom tier). */
    public function nodeChildren(): Collection
    {
        $child = Hierarchy::child(static::TIER);

        return $child ? collect($this->{Hierarchy::table($child)}) : collect();
    }

    /** The parent node, or null for the top tier. */
    public function parentNode(): ?LocationNode
    {
        $parent = Hierarchy::parent(static::TIER);

        return $parent ? $this->{$parent} : null;
    }

    public function mapUrl(): ?string
    {
        if (! $this->map_image) {
            return null;
        }

        return Uploads::url($this->map_image);
    }
}
