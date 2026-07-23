<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * One picture in a character's or location's gallery. `path` is either a
 * storage path on the public disk or an external http(s) URL.
 */
class Image extends Model
{
    protected $fillable = [
        'world_id',
        'imageable_type',
        'imageable_id',
        'path',
        'caption',
        'position',
    ];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    protected static function booted(): void
    {
        // Uploaded files are ours to clean up; linked URLs are not.
        static::deleting(function (Image $image) {
            if ($image->isUploaded()) {
                Storage::disk('public')->delete($image->path);
            }
        });
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isUploaded(): bool
    {
        return ! Str::startsWith($this->path, ['http://', 'https://']);
    }

    public function url(): string
    {
        return $this->isUploaded() ? Storage::disk('public')->url($this->path) : $this->path;
    }
}
