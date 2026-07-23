<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kota extends LocationNode
{
    public const TIER = 'kota';

    protected $table = 'kotas';

    public function provinsi(): BelongsTo
    {
        return $this->belongsTo(Provinsi::class);
    }

    public function desas(): HasMany
    {
        return $this->hasMany(Desa::class);
    }
}
