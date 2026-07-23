<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genre extends Model
{
    /** @use HasFactory<\Database\Factories\GenreFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Genre describes the book, so it hangs off the novel — not its worlds. */
    public function novels(): BelongsToMany
    {
        return $this->belongsToMany(Novel::class);
    }
}
