<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provinsi extends LocationNode
{
    public const TIER = 'provinsi';

    protected $table = 'provinsis';

    public function negara(): BelongsTo
    {
        return $this->belongsTo(Negara::class);
    }

    public function kotas(): HasMany
    {
        return $this->hasMany(Kota::class);
    }
}
