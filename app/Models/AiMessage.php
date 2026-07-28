<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One turn of a conversation. The dossier is never stored as a message — it is
 * rebuilt from the novel on every request, so an edited character or a new
 * chapter is reflected the next time the author asks something.
 */
class AiMessage extends Model
{
    protected $fillable = [
        'ai_conversation_id',
        'role',
        'content',
        'tokens_in',
        'tokens_out',
        'tokens_cached',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }
}
