<?php

namespace App\Models;

use App\Models\Novel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A comment on a Book or a Chapter (polymorphic `commentable`). Top-level when
 * `parent_id` is null; otherwise a reply attached to the top-level comment it
 * names. Replies never nest further — see the migration.
 */
class Comment extends Model
{
    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'user_id',
        'parent_id',
        'body',
        'edited_at',
    ];

    protected function casts(): array
    {
        return ['edited_at' => 'datetime'];
    }

    /** What the comment is attached to — a Book or a Chapter. */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The novel this comment ultimately belongs to, whichever surface it is
     * on — the anchor every permission check resolves against.
     */
    public function novel(): ?Novel
    {
        $on = $this->commentable;

        return $on instanceof Chapter ? $on->book?->novel : $on?->novel;
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** Replies to this comment, oldest first — reading order for a thread. */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->oldest();
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /** A top-level comment; a reply is not one. */
    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    /** Whether it has been changed since it was posted. */
    public function wasEdited(): bool
    {
        return $this->edited_at !== null;
    }
}
