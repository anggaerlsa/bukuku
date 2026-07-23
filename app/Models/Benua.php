<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Benua extends LocationNode
{
    public const TIER = 'benua';

    protected $table = 'benuas';

    public function negaras(): HasMany
    {
        return $this->hasMany(Negara::class);
    }
}
