<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Negara extends LocationNode
{
    public const TIER = 'negara';

    protected $table = 'negaras';

    public function benua(): BelongsTo
    {
        return $this->belongsTo(Benua::class);
    }

    public function provinsis(): HasMany
    {
        return $this->hasMany(Provinsi::class);
    }
}
