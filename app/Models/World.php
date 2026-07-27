<?php

namespace App\Models;

use App\Models\Concerns\CascadesSoftDeletes;
use App\Support\Uploads;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class World extends Model
{
    use CascadesSoftDeletes;
    /** @use HasFactory<\Database\Factories\WorldFactory> */
    use HasFactory;
    use SoftDeletes;

    /**
     * Binning a world takes its whole lore with it — that is the one delete in
     * this app that reaches the furthest, and the reason the bin exists.
     * Locations are listed flat: all five tiers are scoped to the world, so
     * there is no need to walk the hierarchy.
     *
     * @var list<string>
     */
    protected array $cascadeSoftDeletes = [
        'characters',
        'organizations',
        'loreEntries',
        'benuas',
        'negaras',
        'provinsis',
        'kotas',
        'desas',
    ];

    protected $fillable = [
        'user_id',
        'novel_id',
        'name',
        'slug',
        'tagline',
        'premise',
        'cover_image',
        'status',
    ];

    protected static function booted(): void
    {
        // On a permanent delete only. purge() walks the children first, so each
        // has already cleaned up its own files by the time this runs; what is
        // left is the world's own cover and any gallery rows still scoped to it.
        static::forceDeleted(function (World $world) {
            Image::where('world_id', $world->id)->get()->each->delete();
            Uploads::delete($world->cover_image);
        });
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'concept' => 'Konsep',
            'active' => 'Aktif',
            'archived' => 'Arsip',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The novel this world is a setting for. */
    public function novel(): BelongsTo
    {
        return $this->belongsTo(Novel::class);
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    public function loreEntries(): HasMany
    {
        return $this->hasMany(LoreEntry::class);
    }

    /** Author-defined attributes belonging to this world only. */
    public function customFields(): HasMany
    {
        return $this->hasMany(CustomField::class);
    }

    public function benuas(): HasMany
    {
        return $this->hasMany(Benua::class);
    }

    public function negaras(): HasMany
    {
        return $this->hasMany(Negara::class);
    }

    public function provinsis(): HasMany
    {
        return $this->hasMany(Provinsi::class);
    }

    public function kotas(): HasMany
    {
        return $this->hasMany(Kota::class);
    }

    public function desas(): HasMany
    {
        return $this->hasMany(Desa::class);
    }

    /**
     * Total locations across every tier.
     */
    public function locationsCount(): int
    {
        return $this->benuas()->count()
            + $this->negaras()->count()
            + $this->provinsis()->count()
            + $this->kotas()->count()
            + $this->desas()->count();
    }

    public function statusLabel(): string
    {
        return static::statuses()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function coverUrl(): string
    {
        if (! $this->cover_image) {
            return 'https://placehold.co/1000x420/2b1d0e/c9a227?font=playfair-display&text='
                . urlencode(Str::limit($this->name, 28, '…'));
        }

        return Uploads::url($this->cover_image);
    }
}
