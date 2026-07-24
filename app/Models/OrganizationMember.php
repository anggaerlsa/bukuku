<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One character's place in one organisation. `role` is the rank or title they
 * hold there — the thing that used to be crammed into `characters.occupation`.
 */
class OrganizationMember extends Model
{
    protected $fillable = [
        'organization_id',
        'character_id',
        'role',
        'status',
        'note',
    ];

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            'aktif' => 'Aktif',
            'mantan' => 'Mantan',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function statusLabel(): string
    {
        return static::statuses()[$this->status] ?? ucfirst((string) $this->status);
    }
}
