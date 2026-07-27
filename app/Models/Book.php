<?php

namespace App\Models;

use App\Models\Concerns\CascadesSoftDeletes;
use App\Support\Uploads;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A volume of a novel — the container the chapters are read in order within.
 * A novel that is only ever one book still has one, so the reading path is
 * the same shape everywhere.
 */
class Book extends Model
{
    use CascadesSoftDeletes;
    use SoftDeletes;

    /** A volume in the bin takes its chapters with it. @var list<string> */
    protected array $cascadeSoftDeletes = ['chapters'];

    protected $fillable = [
        'novel_id',
        'title',
        'slug',
        'synopsis',
        'cover_image',
        'status',
        'position',
    ];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    protected static function booted(): void
    {
        // Comments are polymorphic, so there is no FK to cascade them away.
        // Only clear them on a PERMANENT delete: a volume in the bin can still
        // be restored, and it must come back with its conversation intact.
        static::forceDeleted(function (Book $book) {
            $book->comments()->delete();
            Uploads::delete($book->cover_image);
        });
    }

    public static function statuses(): array
    {
        return [
            'draft' => 'Draf',
            'ongoing' => 'Berjalan',
            'completed' => 'Tamat',
            'hiatus' => 'Hiatus',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function novel(): BelongsTo
    {
        return $this->belongsTo(Novel::class);
    }

    /** Chapters in reading order — never insertion order. */
    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class)->orderBy('position');
    }

    /** Every comment on the book itself, replies included. */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /** Top-level comments, oldest first, each with its replies loaded. */
    public function topLevelComments(): MorphMany
    {
        return $this->comments()
            ->whereNull('parent_id')
            ->with(['author', 'replies.author'])
            ->oldest();
    }

    public function statusLabel(): string
    {
        return static::statuses()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function coverUrl(): string
    {
        if (! $this->cover_image) {
            return 'https://placehold.co/800x1120/4f46e5/ffffff?text='
                . urlencode(Str::limit($this->title, 24, '…'));
        }

        return (string) Uploads::url($this->cover_image);
    }

    /** Total words across the whole volume, read from the cached counts. */
    public function wordCount(): int
    {
        return (int) $this->chapters()->sum('word_count');
    }

    /** The next free slot, so a new chapter lands at the end. */
    public function nextPosition(): int
    {
        return (int) $this->chapters()->max('position') + 1;
    }
}
