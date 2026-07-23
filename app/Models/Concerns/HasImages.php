<?php

namespace App\Models\Concerns;

use App\Models\Image;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Gallery support for lore records (characters, every location tier).
 * The record keeps its own single cover column; these are the extra images.
 */
trait HasImages
{
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')
            ->orderBy('position')
            ->orderBy('id');
    }

    /** Deleting a record takes its gallery — and the uploaded files — with it. */
    public static function bootHasImages(): void
    {
        static::deleting(function ($model) {
            // Looped rather than mass-deleted so Image's own deleting hook runs
            // and removes each uploaded file from disk.
            $model->images()->get()->each->delete();
        });
    }

    /** Next free slot, so new uploads land at the end of the gallery. */
    public function nextImagePosition(): int
    {
        return (int) $this->images()->max('position') + 1;
    }
}
