<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class World extends Model
{
    /** @use HasFactory<\Database\Factories\WorldFactory> */
    use HasFactory;

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

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
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

        if (Str::startsWith($this->cover_image, ['http://', 'https://'])) {
            return $this->cover_image;
        }

        return Storage::disk('public')->url($this->cover_image);
    }
}
