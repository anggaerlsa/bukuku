<?php

namespace App\Models;

use App\Support\Uploads;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'theme',
        'is_shared',
        'shared_at',
    ];

    protected function casts(): array
    {
        return [
            'is_shared' => 'boolean',
            'shared_at' => 'datetime',
        ];
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

    public function worlds(): HasMany
    {
        return $this->hasMany(World::class);
    }

    /** The manuscript: volumes in reading order. */
    public function books(): HasMany
    {
        return $this->hasMany(Book::class)->orderBy('position');
    }

    /** Every chapter of the novel, across all its volumes. */
    public function chaptersCount(): int
    {
        return Chapter::whereIn('book_id', $this->books()->select('id'))->count();
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    public function statusLabel(): string
    {
        return static::statuses()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function themeLabel(): string
    {
        return \App\Support\NovelTheme::label($this->theme);
    }

    /** Novels other members are allowed to read. */
    public function scopeShared($query)
    {
        return $query->where('is_shared', true);
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

        return Uploads::url($this->cover_image);
    }
}
