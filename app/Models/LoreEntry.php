<?php

namespace App\Models;

use App\Models\Concerns\HasCustomFields;
use App\Models\Concerns\HasImages;
use App\Support\Uploads;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A free-form lore article: a magic system, a pantheon, a glossary, a piece of
 * history. Grouped by `category`, which is the author's own word rather than
 * one of ours — see App\Support\LoreCategories.
 */
class LoreEntry extends Model
{
    use HasCustomFields;
    use HasImages;

    protected $fillable = [
        'world_id',
        'category',
        'title',
        'summary',
        'body',
        'cover_image',
        'position',
    ];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    /** Column holding the record's cover picture (see HasImages). */
    public function coverColumn(): string
    {
        return 'cover_image';
    }

    /** @return list<string> */
    public function customFieldTargets(): array
    {
        return ['lore'];
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    /** Entries with no category still need somewhere to sit in the listing. */
    public function categoryLabel(): string
    {
        return filled($this->category) ? $this->category : 'Tanpa Kategori';
    }

    public function coverUrl(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        return Uploads::url($this->cover_image);
    }
}
