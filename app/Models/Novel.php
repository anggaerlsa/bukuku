<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The book being written. A novel holds the worlds its story moves through —
 * one setting for a single-location story, several for something that travels.
 */
class Novel extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'tagline',
        'synopsis',
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

    public function worlds(): HasMany
    {
        return $this->hasMany(World::class);
    }

    public function statusLabel(): string
    {
        return static::statuses()[$this->status] ?? ucfirst((string) $this->status);
    }

    /** Characters across every world of this novel. */
    public function charactersCount(): int
    {
        return Character::whereIn('world_id', $this->worlds()->select('id'))->count();
    }

    public function coverUrl(): string
    {
        if (! $this->cover_image) {
            return 'https://placehold.co/800x1120/4f46e5/ffffff?text='
                . urlencode(Str::limit($this->title, 24, '…'));
        }

        if (Str::startsWith($this->cover_image, ['http://', 'https://'])) {
            return $this->cover_image;
        }

        return Storage::disk('public')->url($this->cover_image);
    }
}
