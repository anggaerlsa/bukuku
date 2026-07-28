<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A chat thread about one novel.
 *
 * The novel is not a filter applied to a general-purpose conversation — it is
 * part of the row's identity. Loading a thread and building its context both
 * start from `novel_id`, so there is no code path where a thread could be
 * answered against anything else.
 */
class AiConversation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'novel_id',
        'user_id',
        'title',
        'provider',
        'model',
        'with_manuscript',
    ];

    protected function casts(): array
    {
        return [
            'with_manuscript' => 'boolean',
        ];
    }

    public function novel(): BelongsTo
    {
        return $this->belongsTo(Novel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The transcript in the order it was written. */
    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class)->orderBy('id');
    }

    public function displayTitle(): string
    {
        return $this->title ?: 'Percakapan baru';
    }

    /**
     * Name the thread after its opening question, once. Re-titling it on every
     * message would make the sidebar shift under the author as they type.
     */
    public function titleFrom(string $question): void
    {
        if (filled($this->title)) {
            return;
        }

        // The ?: guards a preg_replace() that returns null on a PCRE failure —
        // this project has been bitten by exactly that before.
        $collapsed = preg_replace('/\s+/', ' ', $question) ?: $question;

        $this->update(['title' => Str::limit(trim($collapsed), 60)]);
    }
}
