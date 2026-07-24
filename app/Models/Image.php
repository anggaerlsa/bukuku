<?php

namespace App\Models;

use App\Support\Uploads;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One picture in a character's or location's gallery. `path` is either a
 * path on the uploads disk (see config/uploads.php) or an external
 * http(s) URL.
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
            Uploads::delete($image->path);
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
        return Uploads::isStored($this->path);
    }

    public function url(): string
    {
        return (string) Uploads::url($this->path);
    }
}
