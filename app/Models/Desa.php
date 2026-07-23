<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Desa extends LocationNode
{
    public const TIER = 'desa';

    protected $table = 'desas';

    public function kota(): BelongsTo
    {
        return $this->belongsTo(Kota::class);
    }
}
