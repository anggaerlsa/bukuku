<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One chapter — an episode of the manuscript, the thing a reader actually
 * reads. Its `body` is the author's prose, stored as written.
 */
class Chapter extends Model
{
    protected $fillable = [
        'book_id',
        'title',
        'body',
        'position',
        'word_count',
        'published_at',
        'source_url',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'word_count' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Keep the cached word count honest. Every write goes through here, so a
     * chapter edited in the form and one created by an importer cannot end up
     * disagreeing with their own text.
     */
    protected static function booted(): void
    {
        static::saving(function (Chapter $chapter) {
            if ($chapter->isDirty('body')) {
                $chapter->word_count = static::countWords($chapter->body);
            }
        });
    }

    public static function countWords(?string $body): int
    {
        return $body === null || trim($body) === ''
            ? 0
            : count(preg_split('/\s+/u', trim($body), -1, PREG_SPLIT_NO_EMPTY));
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /** Roughly 200 words a minute, rounded up, never below one. */
    public function readingMinutes(): int
    {
        return max(1, (int) ceil($this->word_count / 200));
    }

    /** A few opening words, for the table of contents. */
    public function teaser(int $length = 120): string
    {
        return Str::limit(trim(preg_replace('/\s+/u', ' ', (string) $this->body)), $length, '…');
    }

    /** The chapter before this one in the same book, if any. */
    public function previous(): ?self
    {
        return static::where('book_id', $this->book_id)
            ->where('position', '<', $this->position)
            ->orderByDesc('position')
            ->first();
    }

    public function next(): ?self
    {
        return static::where('book_id', $this->book_id)
            ->where('position', '>', $this->position)
            ->orderBy('position')
            ->first();
    }
}
